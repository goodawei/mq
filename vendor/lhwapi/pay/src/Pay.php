<?php

/**
 * Created by PhpStorm.
 * User: lihongwei
 * Date: 16/10/19
 * Time: 下午3:05
 */
namespace Api\Pay;

/**
 * Class Pay
 * @package Api\pay
 */
class Pay
{
    /**
     * @param $value
     * @param array $options
     * @return string
     */
    public function payMake($value, array $options = []){
        $salt = isset($options['salt']) ? $options['salt'] : '';
        return hash('md5',$value.$salt);
    }

    /**
     * @param $value
     * @param $hashValue
     * @param array $options
     * @return bool
     */
    public function payCheck($value, $hashValue, array $options = []){
        $salt = isset($options['salt']) ? $options['salt'] : '';
        return hash('md5',$value.$salt) === $hashValue;
    }
}