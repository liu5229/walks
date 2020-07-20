<?php

//统计昨日以前的相关数据
//每日1：00执行一次
require_once __DIR__ . '/../init.inc.php';
set_time_limit(0);
ini_set('memory_limit', '1024M');

$db = new NewPdo('mysql:dbname=' . DB_DATABASE . ';host=' . DB_HOST . ';port=' . DB_PORT, DB_USERNAME, DB_PASSWORD);
$db->exec("SET time_zone = '+8:00'");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

if (!isset($_GET['start'])) {
    die('输入开始时间start');
}
$start = $_GET['start'];
$variableName = 'report_daily';

$end = $_GET['end'] ?? date('Y-m-d');

$sql = 'SELECT user_id, imei, oaid, create_time FROM t_user WHERE create_time >= ? AND create_time <= ? ORDER BY user_id ASC';
$userList = $db->getAll($sql, $start, $end);

$returnUser = array();
foreach ($userList as $userInfo) {
    $sql = 'SELECT 1 FROM t_user_first_login WHERE date = ? AND user_id = ?';
    $nextLeft = $db->getOne($sql, date('Y-m-d', strtotime( '+1 day', strtotime($userInfo['create_time']))),$userInfo['user_id']);
    if ($nextLeft) {
        $returnUser[] = $userInfo;
    }
}

header('Content-Type: application/vnd.ms-excel');   //header设置
header("Content-Disposition: attachment;filename=". 'next_left.csv');
header('Cache-Control: max-age=0');

$fp = fopen('php://output','a');
$head = array('用户Id', 'IMEI', 'OAID');
foreach($head as $k=>$v){
    $head[$k] = iconv("UTF-8","GBK//IGNORE",$v);    //将utf-8编码转为gbk。理由是： Excel 以 ANSI 格式打开，不会做编码识别。如果直接用 Excel 打开 UTF-8 编码的 CSV 文件会导致汉字部分出现乱码。
}
fputcsv($fp, $head);
foreach ($returnUser as $user) {
    fputcsv($fp, array($user['user_id'], $user['imei'] . "\t", $user['oaid'] . "\t"));
}

exit;

echo 'done';