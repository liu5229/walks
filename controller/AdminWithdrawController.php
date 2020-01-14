<?php 

Class AdminWithdrawController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_withdraw";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_withdraw ORDER BY withdraw_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function actionAction () {
        if (isset($_POST['action']) && isset($_POST['withdraw_id'])) {
            switch ($_POST['action']) {
                case 'failed' :
                    $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = ? WHERE withdraw_id = ?';
                    $return = $this->db->exec($sql, $_POST['withdraw_remark'] ?? '', $_POST['withdraw_id']);
                    break;
                case 'success':
//                    $alipay = new Alipay();
//                    $sql = 'SELECT * FROM t_withdraw WHERE withdraw_id = ?';
//                    $userInfo = $this->db->getOne($sql, $_POST['withdraw_id']);
//                    $returnStatus = $alipay->transfer(array(
//                        'price' => $userInfo['withdraw_amount'],
//                        'phone' => $userInfo['alipay_account'],
//                        'name' => $userInfo['alipay_name']));
                    $returnStatus = TRUE;
                    if (TRUE === $returnStatus) {
                        $sql = 'UPDATE t_withdraw SET withdraw_status = "success" WHERE withdraw_id = ?';
                        $return = $this->db->exec($sql, $_POST['withdraw_id']);
                    } else {
                        //to do failure reason from api return
                        $sql = 'UPDATE t_withdraw SET withdraw_status = "failure", withdraw_remark = ? WHERE withdraw_id = ?';
                        $return = $this->db->exec($sql, $returnStatus, $_POST['withdraw_id']);
                    }
                    break;
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        throw new \Exception("Error Request");
    }
}