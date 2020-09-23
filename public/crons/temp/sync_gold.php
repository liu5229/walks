<?php

// 对t_gold分表
require_once __DIR__ . '/../../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);


if (isset($argv[1])) {
    switch ($argv[1]) {
        case 'count':
            $count = 0;
            $total = 0;
            for ($i=1;$i<=100;$i++) {
                $tempName = 't_gold_temp_' . $i;
                $sql = 'SELECT COUNT(gold_id) count, IFNULL(SUM(change_gold), 0) sum FROM ' . $tempName;
                $temp = $db->getRow($sql);
                $count += $temp['count'];
                $total += $temp['sum'];
            }
            echo $count . PHP_EOL;
            echo $total . PHP_EOL;
            break;
        case 'build':
            for ($i=1;$i<=100;$i++) {
                $tempName = 't_gold_temp_' . $i;
                $tableName = 't_gold_' . $i;
                $sql = "CREATE TABLE IF NOT EXISTS " . $tempName . " (
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
                $sql = 'INSERT INTO ' . $tempName . ' (user_id, change_gold, gold_source, change_type, relation_id, change_date, create_time) SELECT user_id, change_gold, gold_source, change_type, relation_id, change_date, create_time FROM ' . $tableName . ' ORDER BY create_time';
                $db->exec($sql);
            }
            echo 'done' . PHP_EOL;
            break;
        case 'drop':
            for ($i=1;$i<=100;$i++) {
                $tempName = 't_gold_temp_' . $i;
                $tableName = 't_gold_' .$i;
                $sql = 'DROP TABLE '. $tableName;
                $sql = 'RENAME TABLE ' . $tempName . ' TO ' . $tableName;
                $db->exec($sql);
            }
            echo 'done' . PHP_EOL;
            break;
    }
} else {
    echo '缺少参数' . PHP_EOL;
}


function breakTableName($userId) {
    $userId = (int) $userId;
    return 't_gold_' . ($userId % 100 + 1);
}