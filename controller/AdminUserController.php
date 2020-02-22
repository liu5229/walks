<?php 

Class AdminUserController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_user ORDER BY user_id";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_user ORDER BY user_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
            foreach ($list as &$userInfo) {
                $userInfo = array_merge($userInfo, $this->model->user->getGold($userInfo['user_id']));
            }
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction() {
        $userInfo = array();
        if (isset($_POST['id'])) {
            $sql = "SELECT * FROM t_user WHERE user_id = ?";
            $userInfo = $this->db->getRow($sql, $_POST['id']);
            $userInfo = array_merge($userInfo, $this->model->user->getGold($_POST['id']));
        }
        if ($userInfo) {
            return $userInfo;
        }
        throw new \Exception("Error User Id");
    }
    
    public function changeStatusAction () {
        if (isset($_POST['user_id'])) {
            $sql = 'UPDATE t_user SET user_status = NOT(user_status) WHERE user_id = ?';
            $return = $this->db->exec($sql, $_POST['user_id']);
            if ($return) {
                return array();
            }
        }
        throw new \Exception("Operation failure");
    }

    public function changeGoldAction() {
        if (isset($_POST['id']) && isset($_POST['change_type']) && isset($_POST['change_gold'])) {
            $sql = "INSERT INTO t_gold_change_log SET type = :type, gold = :gold, remark = :remark, user_id = :user_id";
            $this->db->exec($sql, array('type' => $_POST['change_type'], 'gold' => $_POST['change_gold'], 'remark' => $_POST['change_remark'],'user_id' => $_POST['id']));
            $relationId = $this->db->lastInsertId();
            $return = $this->model->user->updateGold(array(
                'user_id' => $_POST['id'],
                'gold' => $_POST['change_gold'],
                'source' => 'system',
                'type' => $_POST['change_type'],
                'relation_id' => $relationId));
            return $relationId;
        }
        throw new \Exception("Error");
    }
    
    public function goldAction () {
        if (isset($_POST['id'])) {
            $sql = "SELECT COUNT(*) FROM t_gold WHERE user_id = ? ORDER BY user_id";
            $totalCount = $this->db->getOne($sql, $_POST['id']);
            $configInfo = array();
            if ($totalCount) {
                $sql = "SELECT * FROM t_gold WHERE user_id = ? ORDER BY gold_id DESC LIMIT " . $this->page;
                $configInfo = $this->db->getAll($sql, $_POST['id']);
                $sql = 'SELECT activity_type, activity_name FROM t_activity ORDER BY activity_id DESC';
                $activeTypeList = $this->db->getPairs($sql);
                array_walk($configInfo, function (&$v) use($activeTypeList) {
                    switch ($v['change_type']) {
                        case 'in':
                            $v['gSource'] = $activeTypeList[$v['gold_source']] ?? $v['gold_source'];
                            $v['value'] = $v['change_gold'];
                            break;
                        case 'out':
                            $v['gSource'] = 'withdraw' == $v['gold_source'] ? '提现' : $v['gold_source'];
                            $v['value'] = 0 - $v['change_gold'];
                            break;
                    }
                    if ('system' == $v['gold_source']) {
                        $v['gSource'] = '官方操作';
                    }
                });
            }
            
            return array(
                'totalCount' => (int) $totalCount,
                'list' => $configInfo
            );
        }
        throw new \Exception("Error Config Id");
    }
}