<?php

// 步数挑战赛数据统计结算 20200731
//  一小时执行一次
require_once __DIR__ . '/../init.inc.php';

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

$model = new Model();
$awardConfig = array(3000 => 20, 5000 => 500, 10000 => 1000);
$virtualConfig = array(3000 => array(array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 50, 'max' => 100), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250), array('min' => 150, 'max' => 250)), 5000 => array(array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150)), 10000 => array(array(array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 20, 'max' => 50), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150), array('min' => 80, 'max' => 150))));

// 获取参数 是每天第几次执行
$variableName = 'contest_' . date('Ymd');
$sql = 'SELECT variable_value FROM t_variable WHERE variable_name = ?';
$execCount = $db->getOne($sql, $variableName) ?: 0;


// 没有参数的时候需要执行 前一天的数据
if (!$execCount) {
    updateData(date('Y-m-d', strtotime('-1 day')), 25);
    $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ?';
    $contestList = $db->getAll($sql, date('Y-m-d', strtotime('-1 day')));
    //执行前一天的奖励发放工作
    foreach ($contestList as $contestInfo) {
        $sql = 'SELECT user_id FROM t_walk_contest_user WHERE is_complete = 1 AND contest_id = ?';
        $realCompleteUser = $db->getColumn($sql, $contestInfo['contest_id']);
        // 参与的用户+参与的虚拟用户 total
        // 完成的用户+完成的虚拟用户 complete
        // 计算每个完成的用户奖励 total * 档位的奖励 / complete 向上取整
        $award = ceil($contestInfo['total_count'] * $awardConfig[$contestInfo['contest_level']] / $contestInfo['complete_count']);
        //发放奖励
        $sql = "INSERT INTO t_gold2receive (user_id, receive_gold, receive_walk, receive_type, receive_date) VALUES";
        foreach ($realCompleteUser as $userId) {
            $sql .= '(' . $userId . ', ' . $award . ', ' . $contestInfo['contest_level'] . ', "walk_contest"' . '"' . date('Y-m-d') . '"),';
        }
        $sql = rtrim($sql,',');
        $db->exec($sql);
    }
}

//更新今天的数据
updateData(date('Y-m-d'), $execCount);

if (4 == $execCount) {
    // 每天定时新增 后天的挑战赛数据  挑战赛分为三档
    $addTime = strtotime('+2day');
    $periods = date('md', $addTime);
    $contestDate = date('Y-m-d', $addTime);

    $sql = 'INSERT INTO t_walk_contest (contest_periods, contest_level, contest_date) VALUE (:contest_periods, 3000, :contest_date), (:contest_periods, 5000, :contest_date), (:contest_periods, 10000, :contest_date)';
    $db->exec($sql, array('contest_periods' => $periods, 'contest_date' => $contestDate));
}

$sql = 'REPLACE INTO t_variable SET variable_name = ?, variable_value = ?';
$db->exec($sql, $variableName, $execCount + 1);
echo 'done';

function updateData($date, $count) {
    global $db, $virtualConfig;
    $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ?';
    $contestList = $db->getAll($sql, $date);

    foreach ($contestList as $contestInfo) {
        // 添加 完成用户
        $sql = 'SELECT c.id, c.user_id, c.is_complete, w.total_walk FROM t_walk_contest_user c LEFT JOIN t_walk w ON c.user_id = w.user_id AND w.walk_date = ? WHERE contest_id = ?';
        $userList = $db->getAll($sql, $date, $contestInfo['contest_id']);
        $completeUserCount = 0;
        foreach ($userList as $userInfo) {
            if ($userInfo['is_complete']) {
                $completeUserCount++;
            } elseif ($userInfo['total_walk'] > $contestInfo['contest_level']) {
                $completeUserCount++;
                $sql = 'UPDATE t_walk_contest_user SET is_complete = 1 WHERE id = ?';
                $db->exec($sql, $userInfo['id']);
            }
        }
        // 添加 虚拟用户
        $addUser = $virtualConfig[$contestInfo['contest_level']][$count];
        var_dump($virtualConfig);
        // 添加 虚拟完成用户
        $virtualUser = $contestInfo['virtual_count'] + rand($addUser['min'], $addUser['max']);
        $virtualComplete = 0;
        if ($count >= 5) {
            // 虚拟用户完成比例
            $sql = 'SELECT rate FROM t_walk_contest_config WHERE contest_date >= ? AND contest_level = ? ORDER BY contest_date DESC';
            $rate = $db->getOne($sql, $date, $contestInfo['contest_level']);

            $virtualComplete = $virtualUser * $rate;
        }

        $sql = 'UPDATE t_walk_contest SET virtual_count = ?, virtual_complete_count = ?, complete_count = ?, total_count = ? WHERE contest_id = ?';
        $db->exec($sql, $virtualUser, $virtualComplete, $completeUserCount + $virtualComplete, $virtualUser + count($userList), $contestInfo['contest_id']);
    }

}