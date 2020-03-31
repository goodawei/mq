<?php
/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 2020-03-31
 * Time: 18:46
 */

namespace App\Mq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

require_once __DIR__ . './../vendor/autoload.php';

class Worker
{
    public function consum()
    {
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest','/test');
        $channel    = $connection->channel();

        $channel->exchange_declare('test.nxc.online.exchange', 'topic', false, true, false);

        list($queue_name, ,) = $channel->queue_declare("", false, true, true, false);

        $channel->queue_bind($queue_name, 'test.nxc.online.exchange');

        echo " [*] Waiting for logs. To exit press CTRL+C\n";

        $callback = function ($msg) {
            /** @var Message $msg */
            $msg = json_decode($msg->body,true);
            $c = new $msg['c']();
            $res = call_user_func_array([$c, $msg['f']], [$msg['p']]);

            var_dump($res);
        };

        $channel->basic_consume($queue_name, '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}


$c = new Worker();

$c->consum();