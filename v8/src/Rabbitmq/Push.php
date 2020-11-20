<?php
/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 2020-03-31
 * Time: 18:44
 */

namespace App\Mq\Rabbitmq;


class Push
{
    public function handle($params)
    {
        echo 'good';

        var_dump(unserialize($params));

        return true;
    }
}