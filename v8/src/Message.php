<?php
/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 2020-03-31
 * Time: 17:20
 */
namespace App\Mq;

class Message
{
    /**
     * @var int 消息体大小限制.
     */
    private static $_MESSAGE_LENGTH_LIMIT = 16384;//16384=16KB

    /**
     * @var string 消息消费者的Class Name.
     */
    private $_className;

    /**
     * @var string 消息消费者的Function Name.
     */
    private $_functionName;

    /**
     * @var array 消息参数(业务参数).
     */
    private $_params;

    /**
     * @var int 时间戳.
     */
    private $_timestamp;

    /**
     * @var string 消息发送所在的host.
     */
    private $_host;

    /**
     * @var string 消息发送所在的domain(开发环境用来标识回调docker环境).
     */
    private $_domain;

    /**
     * @var bool 消费失败是否重试.
     */
    private $_retry;

    /**
     * @var string 消息ID
     */
    private $_messageID;

    /**
     * @var string 消息topic(发布/订阅模式才有这个字段)
     */
    private $_topicName;

    /**
     * @var string 父消息ID(用来标识消息是否是通过PUB/SUB模式进行消息).
     */
    private $_parentMessageID;

    /**
     * @var string 消息的routing_key
     */
    private $_routingKey;

    // 版本只能为整数 。。。 因为日志的es索引字段一开始被定为integer了。。。
    private static $_VERSION = "2";


    /**
     * @var string
     */
    private $_callableStringValue;

    public function __construct($class, $function, $params, $retry, $routing_key='')
    {
        if (!$routing_key) {
            $routing_key = '';
        }
        $this->_className = ltrim($class, '\\');
        $this->_functionName = $function;
        $this->_callableStringValue = $this->_className.'::'.$this->_functionName;
        $this->_params = $params;
        $this->_timestamp = time();
        $this->_host = gethostname();
        $this->_domain = $this->_getDomain();
        $this->_retry = boolval($retry);
        $this->_messageID = $this->_createGuid();
        $this->_parentMessageID = "";
        $this->_topicName = "";
        $this->_routingKey = $routing_key;
    }

    /**
     * @param $body
     * @return array
     */
    public static function bodyToMessage($body)
    {
        $messageBody = json_decode($body, true);
        return [
            'class' => $messageBody['c'],
            'function' => $messageBody['f'],
            'topic' => $messageBody['tp'],
            'version' => $messageBody['v'],
        ];
    }

    /**
     * 设置父消息ID.
     *
     * @param string $messageID
     */
    public function setParentMessageID($messageID)
    {
        $this->_parentMessageID = $messageID;
    }

    /**
     * @return string
     */
    public function getParentMessageID()
    {
        return $this->_parentMessageID;
    }

    /**
     * 追加messageID到消息参数.
     */
    public function appendMessageIDToParams()
    {
        $this->_params[] = $this->_messageID;
    }

    /**
     * 设置消息的TopicName.
     *
     * @param string $topicName
     */
    public function setTopicName($topicName)
    {
        $this->_topicName = $topicName;
    }

    /**
     * 获取消息的TopicName
     *
     * @return string
     */
    public function topicName()
    {
        return $this->_topicName;
    }

    /**
     * 消息体的版本号
     *
     * @return string
     */
    public function version()
    {
        return self::$_VERSION;
    }

    public function routingKey()
    {
        return $this->_routingKey;
    }

    public function delay(){
        return $this->_routingKey;
    }



    public function isAllowRetry() {
        return $this->_retry;
    }

    /**
     * 返回序列化后的消息.
     *
     * @param bool $withID
     * @return string
     * @throws \Exception
     */
    public function toBody($withID = false)
    {
        $msg = array(
            "c" => $this->_className,
            "f" => $this->_functionName,
            "p" => serialize($this->_params),
            "t" => "".$this->_timestamp."",
            "h" => $this->_host,
            "d" => $this->_domain,
            "rt" => 0,
            "frt" => $this->_retry,
            "v" => self::$_VERSION,
            "pm" => $this->_parentMessageID,
            "tp" => $this->_topicName,
        );

        if ($withID) {
            $msg["id"] = $this->_messageID;
        }

        $ret = json_encode($msg);


        return $ret;
    }

    /**
     * 检查消息体长度是否超出限制.
     *
     * @return bool
     * @throws \Exception
     */
    public function isValidBodyLength()
    {
        return strlen($this->toBody()) <= self::$_MESSAGE_LENGTH_LIMIT;
    }

    /**
     * 检查类和方法是否可用.
     *
     * @return bool
     */
    public function isValidFunction()
    {
        if (!class_exists($this->_className)) {
            return false;
        }

        if (!is_callable(array($this->_className, $this->_functionName))) {
            return false;
        }

        return true;
    }

    /**
     * 检查参数字段是否是有效的（数组结构）
     *
     * @return bool
     */
    public function isValidParams()
    {
        if (!is_array($this->_params)) {
            return false;
        }
        if (json_encode($this->_params) === false) {
            return false;
        }
        return true;
    }

    public function mprint()
    {
        var_export(array(
            "class" => $this->_className,
            "function" => $this->_functionName,
            "params" => $this->_params,
        ));
        echo "<br/>";
    }

    public function params()
    {
        return $this->_params;
    }

    public function className()
    {
        return $this->_className;
    }

    public function functionName()
    {
        return $this->_functionName;
    }

    public function callableStringValue()
    {
        return $this->_callableStringValue;
    }

    public function getMessageID()
    {
        return $this->_messageID;
    }


    private function _getDomain()
    {
        return 'domain.com';
    }

    private function _createGuid()
    {
        static $guid = '';
        $uid = uniqid("", true);
        $data = gethostname();
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['LOCAL_ADDR'];
        $data .= $_SERVER['LOCAL_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = '{' .
            substr($hash, 0, 8) .
            '-' .
            substr($hash, 8, 4) .
            '-' .
            substr($hash, 12, 4) .
            '-' .
            substr($hash, 16, 4) .
            '-' .
            substr($hash, 20, 12) .
            '}';
        return $guid;
    }
}