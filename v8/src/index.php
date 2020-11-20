<?php
/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 2020-03-31
 * Time: 18:09
 */

namespace App\Mq;

require_once __DIR__ . './../vendor/autoload.php';

use App\Mq\Rabbitmq\Push;

class index
{
    /**
     * @throws \Exception
     */
    public function test()
    {
        $retry = false;
        $delay = 0;

        $msg = new Message(Push::class, 'handle', ['foo'=>'bar'], $retry, $delay);

        Sdk::getInstance()->publish($msg);

    }
}


$run = new index();

$run->test();