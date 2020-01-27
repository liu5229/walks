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
}