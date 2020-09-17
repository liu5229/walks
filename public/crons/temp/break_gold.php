<?php

// 对t_gold分表
require_once __DIR__ . '/../../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$variableName = 'break_gold_id';

if (isset($_GET['count'])) {
    $count = 0;
    for ($i=1;$i<=10;$i++) {
        $sql = 'SELECT COUNT(gold_id) FROM t_gold_' . $i;
        $count += $db->getOne($sql);
    }
    echo $count;
    exit;
}


$sql = 'SELECT MAX(gold_id) FROM t_gold';
$maxGoldId = $db->getOne($sql);

while (TRUE) {
    $sql = 'SELECT IFNULL(variable_value, 0) FROM t_variable WHERE variable_name = ?';
    $goldIdStart = $db->getOne($sql, $variableName);

    $sql = 'SELECT * FROM t_gold WHERE gold_id > ? AND gold_id <= ? ORDER BY gold_id LIMIT 10000';
    $goldList = $db->getAll($sql, $goldIdStart, $maxGoldId);
    if (!$goldList) {
        break;
    }
    foreach ($goldList as $goldInfo) {
        $goldId = $goldInfo['gold_id'];
        if ('out' == $goldInfo['change_type']) {
            $goldInfo['change_gold'] = 0 - $goldInfo['change_gold'];
        }
        unset($goldInfo['gold_id']);
        $sql = 'INSERT INTO ' . breakTableName($goldInfo['user_id']) . ' SET user_id = :user_id, change_gold = :change_gold, gold_source = :gold_source, change_type = :change_type, relation_id = :relation_id, change_date = :change_date, create_time = :create_time';
        $db->exec($sql, $goldInfo);
        $sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
        $db->exec($sql, $variableName, $goldId);
    }
}

function breakTableName($userId) {
    $userId = (int) $userId;
    return 't_gold_' . ($userId % 10 + 1);
}

echo 'done';