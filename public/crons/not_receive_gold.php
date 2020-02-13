<?php

//删除两周前未领取的用户接收到金币  这些数据也没有任何作用
//每周一1：00执行一次

require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = 'Asia/Shanghai'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$lastWeekDate = date('Y-m-d 00:00:00', strtotime('-2 week'));
$sql = 'DELETE FROM t_gold2receive WHERE create_time < ? AND receive_status = 0';
$db->exec($sql, $lastWeekDate);
echo 'done';