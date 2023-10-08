<?php
/********
 * 版权声明：
 * 版权所有 © [天津点滴记忆信息科技有限公司]
 * 本软件受到版权法和国际版权条约的保护。未经许可，任何人不得复制、修改、发布、出售、分发本软件的任何部分。
 * 未经许可，不得将本软件用于任何商业用途。任何人不得使用本软件进行任何违法活动。
 * 未经授权使用本软件的任何行为都将受到法律追究。
 * [天津点滴记忆信息科技有限公司] 保留所有权利。
 ********/

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
