<?php

//引入composer代码加载器
require_once __DIR__ . './../vendor/autoload.php';
//引入链接类
use PhpAmqpLib\Connection\AMQPStreamConnection;
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest', '/');
//通过链接获得一个新通道.
$channel = $connection->channel();

$channel->exchange_declare('程序监控' , 'fanout');
$channel->exchange_declare('服务器监控', 'fanout');
$channel->exchange_declare('程序监控通配转发', 'topic',false,false,true,true);
$channel->exchange_bind('程序监控通配转发', '程序监控');

$channel->queue_declare('归档存储');
$channel->queue_declare('MYSQL错误统计');
$channel->queue_declare('钉钉提示');

$channel->queue_bind('归档存储','程序监控');
$channel->queue_bind('归档存储','服务器监控');
$channel->queue_bind('MYSQL错误统计','程序监控通配转发','MYSQL.#');
$channel->queue_bind('钉钉提示','程序监控通配转发','MYSQL.ERROR');



$channel->basic_consume("归档存储", "", false, false, false, false,
    function ($message)
    {
        var_dump('归档存储'.$message->body);
    }
);
$channel->basic_consume("MYSQL错误统计", "", false, false, false, false,
    function ($message)
    {
        var_dump('MYSQL错误统计'.$message->body);
    }
);
$channel->basic_consume("钉钉提示", "", false, false, false, false,
    function ($message)
    {
        var_dump('钉钉提示'.$message->body);
    }
);
while (count($channel->callbacks)) {
    $channel->wait();
}
//关闭通道
$channel->close();
//关闭链接
$connection->close();