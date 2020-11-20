<?php
namespace App\Mq\Rabbitmq;
/**
 * producer.php
 * @package
 */

use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;


class Producer {

    private $_host;

    private $_port;

    private $_user;

    private $_password;

    private $_vhost;

    private $_queue;

    private $_exchange;

    private $_exchangeType;

    /**
     * @var AMQPSocketConnection
     */
    private $_connection;

    /**
     * @var AMQPChannel
     */
    private $_channel;

    /**
     * MRabbitmq_Producer constructor.
     * @param $host
     * @param $port
     * @param $user
     * @param $pass
     * @param $vhost
     * @param $queue
     * @param $exchange
     * @param $exType
     * @throws \Exception
     */
    public function __construct($host, $port, $user, $pass, $vhost, $queue, $exchange, $exType)
    {
        $this->_host = $host;
        $this->_port = $port;
        $this->_user = $user;
        $this->_password = $pass;
        $this->_vhost = $vhost;
        $this->_queue = $queue;
        $this->_exchange = $exchange;
        $this->_exchangeType = $exType;

        try {
            $this->_init();
        } catch (\Exception $e) {
            // 重试一次连接
            $this->_init();
        }

    }

    public function __destruct()
    {
        try {
            $this->_channel->close();
            $this->_connection->close();
        } catch (\Exception $e) {

        }
    }

    /**
     *
     * 发送消息.
     *
     * @param string      $message   消息内容
     * @param string      $messageID 消息唯一ID（可选）
     * @param string      $routing_key
     * @param string|null $exchange
     * @throws \Exception
     */
    public function publish($message, $messageID = '', $routing_key = '', $exchange=null)
    {
        if (!$this->_connection->isConnected()) {
            $this->_init();
        }

        try {
            $this->_publish($message, $messageID, $routing_key, $exchange);
        } catch (\Exception $e) {
            // 处理socket timeout,暂时没有好的办法
            $this->_init();
            $this->_publish($message, $messageID);
        }
    }


    /**
     * 初始化RabbitMQ连接.
     * @throws \Exception
     */
    private function _init()
    {
        $this->_checkParams();

        $this->_connection = new AMQPSocketConnection(
            $this->_host,
            $this->_port,
            $this->_user,
            $this->_password,
            $this->_vhost,
            false,
            'AMQPLAIN',
            null,
            'UTF8',
            3,
            true,
            3,
            0
        );

        $this->_connection->set_close_on_destruct(false);

        $this->_channel = $this->_connection->channel();

        $this->_channel->basic_qos(null, 10, null);
        $this->_channel->exchange_declare($this->_exchange, $this->_exchangeType, false, true, false);


        if (is_array($this->_queue)) {
            foreach ($this->_queue as $queue) {
                $this->_channel->queue_declare($queue['queue'], false, true, false, false, false,
                    new AMQPTable($queue['arguments']));
                $this->_channel->queue_bind($queue['queue'], $this->_exchange, $queue['routing_key']);
            }

        } else {
            $this->_channel->queue_declare($this->_queue, false, true, false, false);
            $this->_channel->queue_bind($this->_queue, $this->_exchange);
        }
    }

    /**
     * 检查参数.
     *
     * @throws MRabbitmq_Exception
     */
    private function _checkParams()
    {
        if (!$this->_host) {
            throw new MRabbitmq_Exception(10001);
        }
        if (!$this->_port) {
            throw new MRabbitmq_Exception(10002);
        }
        if (!$this->_user) {
            throw new MRabbitmq_Exception(10003);
        }
        if (!$this->_password) {
            throw new MRabbitmq_Exception(10004);
        }
        if (!$this->_vhost) {
            throw new MRabbitmq_Exception(10005);
        }
        if (!$this->_queue) {
            throw new MRabbitmq_Exception(10006);
        }
        if (!$this->_exchange) {
            throw new MRabbitmq_Exception(10007);
        }
    }

    public function _publish($message, $messageID = '', $routing_key='', $exchange = null)
    {
        $properties = array(
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        );
        if ($messageID) {
            $properties['message_id'] = $messageID;
        }

        $amqpMsg = new AMQPMessage($message, $properties);

        if ($exchange===null) {
            $exchange = $this->_exchange;
        }

        $this->_channel->basic_publish($amqpMsg, $exchange, $routing_key);
    }
}