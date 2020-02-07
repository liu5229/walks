<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once '../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = 'Asia/Shanghai'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$variableName = 'report_daily';
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$reportDaily = $db->getOne($sql, $variableName);
$lastDate = date('Y-m-d 00:00:00', strtotime('-1 days'));

if (!$reportDaily) {
    $reportDaily = '2019-12-10';
}

while (true) {
    $start = $reportDaily . ' 00:00:00';
    $end = $reportDaily . ' 23:59:59';
    $sql = 'SELECT COUNT(*) count, SUM(withdraw_amount) sum FROM t_withdraw WHERE change_time >= ? AND change_time < ?';
    $withInfo = $db->getRow($sql, $start, $end);
    
    $sql = 'SELECT COUNT(*) FROM t_user WHERE create_time >= ? AND create_time < ?';
    $newUser = $db->getOne($sql, $start, $end);
    
    $sql = 'SELECT SUM(change_gold) FROM t_gold WHERE create_time >= ? AND create_time < ? AND change_type = "in"';
    $newGold = $db->getOne($sql, $start, $end) ?: 0;
    
    $sql = 'SELECT COUNT(user_id) FROM t_user_first_login WHERE date = ?';
    $loginUser = $db->getOne($sql, $reportDaily);
    
    $sql = 'INSERT INTO t_report SET withdraw_value = :withdraw_value, 
        withdraw_count = :withdraw_count, 
        new_user = :new_user, 
        new_gold = :new_gold, 
        login_user = :login_user,
        report_date = :report_date';
    $db->exec($sql, array('withdraw_value' => $withInfo['sum'] ?: 0,
        'withdraw_count' => $withInfo['count'],
        'new_user' => $newUser,
        'new_gold' => $newGold,
        'login_user' => $loginUser,
        'report_date' => $reportDaily
        ));
    if (strtotime($lastDate) == strtotime($reportDaily . ' 00:00:00')) {
        break;
    }
    $reportDaily = date('Y-m-d', strtotime('+1 day', strtotime($reportDaily)));
}
$sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
$db->exec($sql, $variableName, $reportDaily);