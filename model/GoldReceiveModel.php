<?php


class GoldReceiveModel extends AbstractModel
{
    public function insert ($params) {
        $sql = 'INSERT INTO t_gold2receive SET user_id = ?, receive_gold = ?, receive_walk = ?, receive_type = ?, receive_status = ?, end_time = ?, is_double = ?, receive_date = ?';
        $this->db->exec($sql, $params['user_id'], $params['gold'], $params['walk'] ?? 0, $params['type'], $params['status'] ?? 0, $params['end_time'] ?? '0000-00-00 00:00:00', $params['is_double'] ?? 0, $params['date'] ?? date('Y-m-d'));
        return $this->db->lastInsertId();
    }

    //批量插入待领取金币
    //参数顺序 user_id, receive_gold, receive_walk, receive_type, receive_date
    public function batchInsert ($data) {
        $sql = "INSERT INTO t_gold2receive (user_id, receive_gold, receive_walk, receive_type, receive_date) VALUES";
        foreach ($data as $line) {
            $sql .= '(' . implode(', ', $line) . '),';
        }
        $sql = rtrim($sql,',');
        $this->db->exec($sql);
    }
}