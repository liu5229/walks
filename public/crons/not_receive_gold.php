<?php

//删除昨天前未领取的用户接收到金币  这些数据也没有任何作用
//每天1：00执行一次

require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = 'Asia/Shanghai'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$lastWeekDate = date('Y-m-d', strtotime('-1 day'));

$sql = 'INSERT INTO t_gold2receive_old(receive_id, user_id, receive_gold, receive_walk, receive_type, receive_status, end_time, is_double, receive_date, create_time)
        SELECT receive_id, user_id, receive_gold, receive_walk, receive_type, receive_status, end_time, is_double, receive_date, create_time
        FROM t_gold2receive WHERE receive_date <= ? AND receive_status = 1';
$return = $db->exec($sql, $lastWeekDate);

 if ($return) {
    $sql = 'DELETE FROM t_gold2receive WHERE receive_date <= ?';
    $db->exec($sql, $lastWeekDate); 
 } else {
     echo '转移数据失败';
 }



echo 'done';