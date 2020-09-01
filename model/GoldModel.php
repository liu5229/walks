<?php


class GoldModel extends AbstractModel
{
    protected $goldTable = 't_gold';

     public function walkReceive ($userId, $date, $startTime) {
         $sql = 'SELECT COUNT(gr.receive_id) count, MIN(g.create_time) min FROM t_gold2receive gr LEFT JOIN ' . $this->goldTable . ' g ON gr.receive_id = g.relation_id AND g.gold_source = gr.receive_type WHERE gr.user_id = ? AND gr.receive_date = ? AND gr.receive_type = "walk" AND gr.receive_status = 1 AND g.create_time >= ?';
         return $this->db->getRow($sql, $userId, $date, $startTime);
     }

     public function invitedList ($userId) {
         $sql = 'SELECT c.counter_min, c.award_min, g.gold_id FROM t_award_config c LEFT JOIN ' . $this->goldTable . ' g ON g.relation_id = c.config_id AND g.gold_source = c.config_type AND g.user_id = ? WHERE c.config_type = ? ORDER BY c.counter_min ASC';
         return $this->db->getAll($sql, $userId, 'invited_count');
     }

     public function invitedSum ($userId) {
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source IN ("do_invite", "invited_count")';
         return $this->db->getOne($sql, $userId);
     }

     public function invitedDetails ($userId) {
         $sql = 'SELECT u.nickname, g.change_gold gold, unix_timestamp(i.create_time) * 1000 cTime FROM t_user_invited i LEFT JOIN t_user u ON i.invited_id = u.user_id LEFT JOIN ' . $this->goldTable . ' g ON g.gold_source = "do_invite" AND g.relation_id = i.id WHERE i.user_id = ? ORDER BY i.id DESC';
         return $this->db->getAll($sql, $userId);
     }

     public function news ($userId) {
         $sql = 'SELECT gold_id FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ?';
         return $this->db->getOne($sql, $userId, 'newer');
     }

     public function thirdAward ($userId, $source, $startTime) {
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) gold FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ? AND create_time > ? ORDER BY gold_id DESC';
         return $this->db->getOne($sql, $userId, $source, $startTime);
     }

     public function walkContestTotal ($userId) {
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) total, IFNULL(MAX(change_gold), 0) max FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_type = ?';
         return $this->db->getRow($sql, $userId, 'walk_contest');
     }

     public function existSource ($userId, $source) {
         $sql = 'SELECT COUNT(*) FROM ' . $this->goldTable . ' FORCE INDEX(user_id) WHERE user_id = ?  AND gold_source = ?';
         return $this->db->getOne($sql, $userId, $source);
     }

     public function goldTotal ($userId) {
         $sql = "SELECT COUNT(*) FROM ' . $this->goldTable . ' WHERE user_id = ?";
         return $this->db->getOne($sql, $userId);
     }

     public function goldDetails ($userId, $limit) {
         $sql = "SELECT * FROM ' . $this->goldTable . ' WHERE user_id = ? ORDER BY gold_id DESC LIMIT " . $limit;
         return $this->db->getAll($sql, $userId);
     }

     public function existSourceDate ($userId, $date, $type) {
         $sql = 'SELECT COUNT(*) FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_date = ? AND gold_source = ?';
         return $this->db->getOne($sql, $userId, $date, $type);
     }

     public function singGoldList ($userId, $fromDate, $today, $type) {
         $sql = 'SELECT gold_id id , change_gold num, 1 isReceive, 0 isDouble, 0 isToday FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_date >= ? AND change_date < ? AND gold_source = ? ORDER BY gold_id';
         return $this->db->getAll($sql, $userId, $fromDate, $today, $type);
     }

     public function noWithdrawUser ($userIdStart, $createTime) {
         $sql = 'SELECT u.user_id, g.change_gold, g.gold_id FROM t_user u LEFT JOIN t_withdraw w ON w.user_id = u.user_id LEFT JOIN ' . $this->goldTable . ' g ON g.user_id = u.user_id AND g.gold_source = "newer" WHERE w.withdraw_id IS NULL AND u.user_id > ? AND u.create_time <= ?';
         return $this->db->getAll($sql, $userIdStart, $createTime);
     }

     public function totalGoldByDate($date) {
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) FROM ' . $this->goldTable . ' WHERE change_date = ? AND change_type = "in"';
         return $this->db->getOne($sql, $date);
     }

     public function shareCountByDate ($date) {
         $sql = 'SELECT COUNT(DISTINCT user_id) FROM ' . $this->goldTable . ' WHERE change_date = ? AND gold_source = "share"';
         return $this->db->getOne($sql, $date);
     }

     public function totalGoldByUser ($userId) {
         $sql = 'SELECT IFNULL(SUM(IF(change_type="in", change_gold, -change_gold)), 0) FROM ' . $this->goldTable . ' WHERE user_id = ?';
         return $this->db->getOne($sql, $userId);
     }

     public function goldDetail ($userId, $startDate) {
         $sql = 'SELECT gold_source source,change_gold value, change_type type, create_time gTime FROM ' . $this->goldTable . ' WHERE user_id = ? AND create_time >= ? ORDER BY gold_id DESC';
         return $this->db->getAll($sql, $userId, $startDate);
     }
}