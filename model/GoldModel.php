<?php


class GoldModel extends AbstractModel
{
    protected $goldTable = 't_gold';

    public function walkReceive ($userId, $startTime, $limit) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT create_time FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = "walk" ORDER BY gold_id DESC LIMIT ' . $limit;
        $walkList = $this->db->getColumn($sql, $userId);
        $return = array('count' => 0, 'min' => 0);
        foreach ($walkList as $createTime) {
            if (strtotime($createTime) > $startTime) {
                $return['count']++;
                $return['min'] = strtotime($createTime);
            } else {
                break;
            }
        }
        return $return;
        $sql = 'SELECT COUNT(gold_id) count, MIN(create_time) min FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = "walk" AND create_time >= ?';
        return $this->db->getRow($sql, $userId, date('Y-m-d H:i:s', $startTime));
    }

    public function invitedList ($userId) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT c.counter_min, c.award_min, g.gold_id FROM t_award_config c LEFT JOIN ' . $this->goldTable . ' g ON g.relation_id = c.config_id AND g.gold_source = c.config_type AND g.user_id = ? WHERE c.config_type = ? ORDER BY c.counter_min ASC';
        return $this->db->getAll($sql, $userId, 'invited_count');
    }

    public function invitedSum ($userId) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source IN ("do_invite", "invited_count")';
         return $this->db->getOne($sql, $userId);
     }

     public function invitedDetails ($userId) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT u.nickname, g.change_gold gold, unix_timestamp(i.create_time) * 1000 cTime FROM t_user_invited i LEFT JOIN t_user u ON i.invited_id = u.user_id LEFT JOIN ' . $this->goldTable . ' g ON g.gold_source = "do_invite" AND g.relation_id = i.id WHERE i.user_id = ? ORDER BY i.id DESC';
         return $this->db->getAll($sql, $userId);
     }

     public function thirdAward ($userId, $source, $startTime) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) gold FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ? AND create_time > ?';
         return $this->db->getOne($sql, $userId, $source, $startTime);
     }

     public function walkContestTotal ($userId) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) total, IFNULL(MAX(change_gold), 0) max FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ?';
         return $this->db->getRow($sql, $userId, 'walk_contest');
     }

     public function existSource ($userId, $source) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT COUNT(*) FROM ' . $this->goldTable . ' WHERE user_id = ?  AND gold_source = ?';
         return $this->db->getOne($sql, $userId, $source);
     }

     public function goldTotal ($userId) {
         $this->setTableByuserId($userId);
         $sql = "SELECT COUNT(*) FROM " . $this->goldTable . " WHERE user_id = ?";
         return $this->db->getOne($sql, $userId);
     }

     public function goldDetails ($userId, $limit) {
         $this->setTableByuserId($userId);
         $sql = "SELECT * FROM " . $this->goldTable . " WHERE user_id = ? ORDER BY gold_id DESC LIMIT " . $limit;
         return $this->db->getAll($sql, $userId);
     }

     public function existSourceDate ($userId, $date, $type) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT COUNT(*) FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_date = ? AND gold_source = ?';
         return $this->db->getOne($sql, $userId, $date, $type);
     }

     public function singGoldList ($userId, $fromDate, $today, $type) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT gold_id id , change_gold num, 1 isReceive, 0 isDouble, 0 isToday FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_date >= ? AND change_date < ? AND gold_source = ? ORDER BY gold_id';
         return $this->db->getAll($sql, $userId, $fromDate, $today, $type);
     }

    public function signList ($userId, $type, $limit) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT gold_id id , change_gold num, 1 isReceive, 0 isDouble, 0 isToday FROM ' . $this->goldTable . ' WHERE user_id = ? AND change_date < ? AND gold_source = ? ORDER BY gold_id DESC LIMIT ' . $limit;
        return $this->db->getAll($sql, $userId, date('Y-m-d'), $type);

    }

    public function getDetailBysource ($userId, $source) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT change_gold, gold_id FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ?';
        return $this->db->getRow($sql, $userId, $source);
    }

     public function totalGoldByDate($date) {
         $total = 0;
         for ($i=1;$i<=100;$i++) {
             $tempName = 't_gold_' . $i;
             $sql = 'SELECT IFNULL(SUM(change_gold), 0) FROM ' . $tempName . ' WHERE change_date = ? AND change_type = "in"';
             $total += $this->db->getOne($sql, $date);
         }
         return $total;
     }

     public function shareCountByDate ($date) {
         $count = 0;
         for ($i=1;$i<=100;$i++) {
             $tempName = 't_gold_' . $i;
             $sql = 'SELECT COUNT(DISTINCT user_id) FROM ' . $tempName . ' WHERE change_date = ? AND gold_source = "share"';
             $count += $this->db->getOne($sql, $date);
         }
         return $count;
     }

     public function totalGoldByUser ($userId) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT IFNULL(SUM(change_gold), 0) FROM ' . $this->goldTable . ' WHERE user_id = ?';
         return $this->db->getOne($sql, $userId);
     }

     public function goldDetail ($userId, $startDate) {
         $this->setTableByuserId($userId);
         $sql = 'SELECT gold_source source,change_gold value, change_type type, create_time gTime FROM ' . $this->goldTable . ' WHERE user_id = ? AND create_time >= ? ORDER BY gold_id DESC';
         return $this->db->getAll($sql, $userId, $startDate);
     }

    /**
     * 更新用户金币
     * @param array $params
     * $params user_id
     * $params gold
     * $params source
     * $params type
     * $params relation_id if has
     * @return boolean|\ApiReturn
     */
    public function updateGold($params = array()) {
        $todayDate = date('Y-m-d');
        $userState = $this->model->user2->userInfo($params['user_id'], 'user_status');
        if (!$userState) {
            return new ApiReturn('', 203, '抱歉您的账户已被冻结');
        }
        $this->setTableByuserId($params['user_id']);
        if ('out' == $params['type']) {
            $params['gold'] = 0 - $params['gold'];
        }
        if ('sign' == $params['source']) {
            $sql = "INSERT INTO " . $this->goldTable . " SET user_id = :user_id, change_gold = :change_gold, gold_source = :gold_source, change_type = :change_type, relation_id = :relation_id, change_date = :change_date";
            $this->db->exec($sql, array( 'user_id' => $params['user_id'], 'change_gold' => $params['gold'], 'gold_source' => $params['source'], 'change_type' => $params['type'], 'relation_id' => $params['relation_id'] ?? 0, 'change_date' => $todayDate));
        } else {
            $sql = "INSERT INTO " . $this->goldTable . " (user_id, change_gold, gold_source, change_type, relation_id, change_date) SELECT :user_id, :change_gold, :gold_source, :change_type, :relation_id, :change_date WHERE NOT EXISTS( SELECT * FROM " . $this->goldTable . " WHERE user_id = :user_id AND change_gold = :change_gold AND gold_source = :gold_source AND change_type = :change_type AND relation_id = :relation_id AND change_date = :change_date)";
            $this->db->exec($sql, array( 'user_id' => $params['user_id'], 'change_gold' => $params['gold'], 'gold_source' => $params['source'], 'change_type' => $params['type'], 'relation_id' => $params['relation_id'] ?? 0, 'change_date' => $todayDate ));
        }
        return TRUE;
    }

    public function signCount ($userId) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT COUNT(gold_id) FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ?';
        return $this->db->getOne($sql, $userId, 'sign');
    }

    public function withdrawVideoCount ($userId) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT COUNT(gold_id) FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source IN ("newer", "sign")';
        return $this->db->getOne($sql, $userId);
    }

    public function newerCount ($userId) {
        $this->setTableByuserId($userId);
        $sql = 'SELECT IFNULL(change_gold, 0) FROM ' . $this->goldTable . ' WHERE user_id = ? AND gold_source = ?';
        return $this->db->getOne($sql, $userId, 'newer');
    }

    protected function setTableByuserId ($userId) {
        $userId = (int) $userId;
        $this->goldTable = 't_gold_' . ($userId % 100 + 1);
    }
}