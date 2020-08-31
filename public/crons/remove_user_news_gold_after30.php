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

$userList = $model->gold->noWithdrawUser($userIdStart, $createTime);
foreach ($userList as $userInfo) {
    $params = array('user_id' => $userInfo['user_id'],
        'gold' => $userInfo['change_gold'] ?: 0,
        'source' => "newer_invalid",
        'type' => "out",
        'relation_id' => $userInfo['gold_id'] ?: 0
    );
    $model->user2->updateGold($params);
    
    $sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
    $db->exec($sql, $variableName, $userInfo['user_id']);
}

echo 'done';