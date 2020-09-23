<?php

//删除昨天前未领取的用户接收到金币  这些数据也没有任何作用
//每天1：00执行一次

require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();

$variableName = 'remove_news_gold_id';
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$userIdStart = $db->getOne($sql, $variableName) ?: 0;

$createTime = date('Y-m-d 23:59:59', strtotime('-30 day'));

$sql = 'SELECT user_id FROM t_user WHERE user_id > ? AND create_time <= ?';
$userList =  $db->getAll($sql, $userIdStart, $createTime);
foreach ($userList as $userInfo) {
    $sql = 'SELECT COUNT(withdraw_id) FROM t_withdraw WHERE user_id = ?';
    if ($db->getOne($sql, $userInfo['user_id'])) {
        $newerGold = $model->gold->getDetailBysource($userInfo['user_id'], 'newer');

        $model->gold->updateGold(array('user_id' => $userInfo['user_id'], 'gold' => $newerGold['change_gold'] ?: 0, 'source' => "newer_invalid", 'type' => "out", 'relation_id' => $newerGold['gold_id'] ?: 0));
    }
    $sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
    $db->exec($sql, $variableName, $userInfo['user_id']);
}

echo 'done';