<?php

!defined('EMLOG_ROOT') && exit('access deined!');

if (!class_exists('EmToolKit', false)) {
    include __DIR__ . '/em_toolkit.php';
}

// 开启插件时执行该函数
function callback_init() {

}

// 关闭和删除插件时执行该函数
function callback_rm() {
    // do something
}
