<?php
/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 2020-03-31
 * Time: 17:20
 */

namespace App\Mq;

use App\Mq\Rabbitmq\Producer;
use App\Mq\Rabbitmq\MRabbitmq_Exception;

class Sdk
{
    private static $_instance;

    private static $_QUEUE = "nxc-online-queue";

    private static $_EXCHANGE = "test.nxc.online.exchange";

    private static $_RABBITMQ_CONFIG = array(

        "develop" => array(
            "host" => "127.0.0.1",
            "port" => "5672",
            "user" => "guest",
            "pass" => "guest",
            "vhost" => "/test",
        ),
        "product" => array(
            "host" => "127.0.0.1",
            "port" => "10004",
            "user" => "mfw",
            "pass" => "141f250766960fc09adb9a861d40032737966",
            "vhost" => "/nxc",
        ),
    );

    /**
     * @var  Producer
     */
    private $_rabbitProducer;

    /**
     * 某种类型每秒消息push阈值
     */
    const MSG_THRESHOLD = 100;

    /**
     * Sdk constructor.
     * @throws \Exception
     */
    private function __construct()
    {
        $this->_init();
    }

    /**
     * @return Sdk
     * @throws \Exception
     */
    public static function getInstance()
    {
        if (!is_object(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param Message $msg
     * @throws \Exception
     */
    public function publish(Message $msg)
    {
        if (!$msg->isValidParams()) {

            throw new MRabbitmq_Exception(10004);
        }

        if (!$msg->isValidFunction()) {

            throw new MRabbitmq_Exception(10002);
        }


        $this->send($msg);
    }

    /**
     * @throws \Exception
     */
    private function _init()
    {
        $config = self::$_RABBITMQ_CONFIG["product"];

        if (true) {
            $config = self::$_RABBITMQ_CONFIG["develop"];
        }

        $this->_rabbitProducer = new Producer(
            $config["host"],
            $config["port"],
            $config["user"],
            $config["pass"],
            $config["vhost"],
            self::$_QUEUE,
            self::$_EXCHANGE,
            "topic"
        );
    }

    /**
     * @param Message $msg
     * @throws \Exception
     */
    private function send(Message $msg)
    {

        $body = $msg->toBody();
        if (!$body || $body == "[]") {
            die('1');
        }

        $exchange = null;
//        if ($msg->className()==='apps\\message\\monitor\\MQueueHeartbeatMonitor') {
//            $exchange = 'docker.online';
//        }

        $this->_rabbitProducer->publish($body, $msg->getMessageID(), '', $exchange);
    }

}