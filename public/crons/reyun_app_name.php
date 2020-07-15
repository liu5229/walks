<?php

//累计邀请多次好友的用户发放奖励
//  一小时执行一次
require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();

while (true) {
    $sql = 'SELECT log_id, imei, app_name FROM t_reyun_log WHERE status = 0 ORDER BY id LIMIT 1000';
    $reyunList = $db->getAll($sql);
    foreach ($reyunList as $reyunInfo) {
        $sql = 'SELECT user_id FROM `t_user` where imei = ?';
        $userId = $db->getOne($sql, $reyunInfo['imei']);
        if ($userId) {
            $sql = 'UPDATE t_user SET reyun_app_name = ? WHERE user_id = ?';
            $db->exec($sql, $reyunInfo['app_name'], $userId);
            $sql = 'UPDATE t_reyun_log SET status = 1 WHERE log_id = ?';
            $db->exec($sql, $reyunInfo['log_id']);
            continue;
        }

        $sql = 'SELECT user_id FROM `t_user` where OAID = ?';
        $userId = $db->getOne($sql, $reyunInfo['imei']);
        if ($userId) {
            $sql = 'UPDATE t_user SET reyun_app_name = ? WHERE user_id = ?';
            $db->exec($sql, $reyunInfo['app_name'], $userId);
            $sql = 'UPDATE t_reyun_log SET status = 1 WHERE log_id = ?';
            $db->exec($sql, $reyunInfo['log_id']);
            continue;
        }

        $sql = 'SELECT user_id FROM `t_user` where AndroidId = ?';
        $userId = $db->getOne($sql, $reyunInfo['imei']);
        if ($userId) {
            $sql = 'UPDATE t_user SET reyun_app_name = ? WHERE user_id = ?';
            $db->exec($sql, $reyunInfo['app_name'], $userId);
            $sql = 'UPDATE t_reyun_log SET status = 1 WHERE log_id = ?';
            $db->exec($sql, $reyunInfo['log_id']);
            continue;
        }

        if (time() - strtotime($reyunInfo['create_time']) > 10 * 60) {
            $sql = 'UPDATE t_reyun_log SET status = 2 WHERE log_id = ?';
            $db->exec($sql, $reyunInfo['log_id']);
        }
    }

    sleep(5);
}
echo 'done';