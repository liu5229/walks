<?php

// 对t_gold分表
require_once __DIR__ . '/../../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$variableName = 'break_gold_id';

if (isset($argv[1]) && '&' != $argv[1]) {
    switch ($argv[1]) {
        case 'count':
            $count = 0;
            $total = 0;
            for ($i=1;$i<=100;$i++) {
                $sql = 'SELECT COUNT(gold_id) FROM t_gold_' . $i;
                $count += $db->getOne($sql);
                $sql = 'SELECT SUM(change_gold) FROM t_gold_' . $i;
                $total += $db->getOne($sql);
            }
            echo $count . PHP_EOL;
            echo $total . PHP_EOL;
            break;
        case 'build':
            for ($i=1;$i<=100;$i++) {
                $tableName = 't_gold_' .$i;
                $sql = "CREATE TABLE IF NOT EXISTS " . $tableName . " (
                     `gold_id` int NOT NULL AUTO_INCREMENT,
                     `user_id` int NOT NULL COMMENT '用户id',
                     `change_gold` int NOT NULL COMMENT '修改金币金额',
                     `gold_source` varchar(20) NOT NULL COMMENT '修改金币来源',
                     `change_type` enum('in', 'out') NOT NULL COMMENT '修改金币类型（进或者出）',
                     `relation_id` int NOT NULL DEFAULT 0 COMMENT '关联表id 比如金币提现表id',
                     `change_date` date NOT NULL DEFAULT 0 COMMENT '年月日',
                     `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                     PRIMARY KEY (`gold_id`),
                     KEY `change_date` (`change_date`),
                     KEY `user_id` (`user_id`),
                     KEY `gold_source` (`gold_source`),
                     KEY `gold_source_2` (`gold_source`,`relation_id`)
                    ) COMMENT='用户金币流水表'";
                $db->exec($sql);
            }
            echo 'done';
            break;
        case 'drop':
            for ($i=1;$i<=100;$i++) {
                $tableName = 't_gold_' .$i;
                $sql = 'DROP TABLE '. $tableName;
                $db->exec($sql);
            }
            echo 'done';
            break;
        case 'delete':
            for ($i=1;$i<=100;$i++) {
                $tableName = 't_gold_' .$i;
                $sql = 'TRUNCATE '. $tableName;
                $db->exec($sql);
            }
            $sql = 'DELETE FROM t_variable WHERE variable_name = ?';
            $db->exec($sql, $variableName);
            echo 'done';
            break;
    }
    exit;
}

$sql = 'SELECT MAX(gold_id) FROM t_gold';
$maxGoldId = $db->getOne($sql);

while (TRUE) {
    $sql = 'SELECT IFNULL(variable_value, 0) FROM t_variable WHERE variable_name = ?';
    $goldIdStart = $db->getOne($sql, $variableName);
    if ($goldIdStart >= $maxGoldId) {
        break;
    }

    $sql = 'SELECT * FROM t_gold WHERE gold_id > ? ORDER BY gold_id LIMIT 1000';
    $goldList = $db->getAll($sql, $goldIdStart);
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
    return 't_gold_' . ($userId % 100 + 1);
}

echo 'done';