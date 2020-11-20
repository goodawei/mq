<?php
namespace App\Mq\Rabbitmq;
/**
 * Exception.php
 * @package
 */
use Exception;

class MRabbitmq_Exception extends \Exception {

    private static $_exceptionInfos = array(
        10001 => array(
            'message' => 'rabbitmq producer error : need host',
            'desc' => '需要传入RabbitMQ服务的host参数',
        ),
        10002 => array(
            'message' => 'rabbitmq producer error : need port',
            'desc' => '需要传入RabbitMQ服务的port参数',
        ),
        10003 => array(
            'message' => 'rabbitmq producer error : need user',
            'desc' => '需要传入RabbitMQ服务的user参数',
        ),
        10004 => array(
            'message' => 'rabbitmq producer error : need password',
            'desc' => '需要传入RabbitMQ服务的password参数',
        ),
        10005 => array(
            'message' => 'rabbitmq producer error : need vhost',
            'desc' => '需要传入RabbitMQ服务的vhost参数',
        ),
        10006 => array(
            'message' => 'rabbitmq producer error : need queue',
            'desc' => '需要传入RabbitMQ服务的queue参数',
        ),
        10007 => array(
            'message' => 'rabbitmq producer error : need exchange',
            'desc' => '需要传入RabbitMQ服务的exchange参数',
        ),
    );


    public function __construct($code, $message = '', Exception $previous = null)
    {
        if (array_key_exists($code, self::$_exceptionInfos))
        {
            $message = str_replace("[message]", $message, self::$_exceptionInfos[$code]['message']);
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取异常描述信息(中文).
     *
     * @return mixed
     */
    public function getDesc()
    {
        return self::$_exceptionInfos[$this->getCode()]['desc'];
    }
}