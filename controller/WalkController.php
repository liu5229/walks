<?php 

Class WalkController extends AbstractController {
    //提现汇率
    protected $withdrawalRate = 10000;
    protected $userId;
    
    public function init() {
        parent::init();
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }

    public function goldDetailAction () {
        $goldDetail = $this->model->gold->goldDetail($this->userId, date('Y-m-d 00:00:00', strtotime('-3 days')));
        $sql = 'SELECT activity_type, activity_name FROM t_activity ORDER BY activity_id DESC';
        $activeTypeList = $this->db->getPairs($sql);
        array_walk($goldDetail, function (&$v) use($activeTypeList) {
            switch ($v['type']) {
                case 'in':
                    $v['gSource'] = $activeTypeList[$v['source']] ?? $v['source'];
                    break;
                case 'out':
                    switch ($v['source']) {
                        case 'withdraw':
                            $v['gSource'] = '提现';
                            break;
                        case 'walk_contest_regfee':
                            $v['gSource'] = '步数挑战赛报名费';
                            break;
                        default :
                            $v['gSource'] = $v['source'];
                    }
                    break;
            }
            if ('system' == $v['source']) {
                $v['gSource'] = '官方操作';
            } elseif ('newer_invalid' == $v['source']) {
                $v['gSource'] = '新手红包过期';
            }
            $v['gTime'] = strtotime($v['gTime']) * 1000;
        });
        return new ApiReturn($goldDetail);    
    }
    
    public function withdrawDetailAction () {
        $statusArray = array('pending' => '审核中', 'success' => '审核成功', 'failure' => '审核失败');
        $sql = "SELECT withdraw_amount amount, withdraw_status status, create_time wTime  FROM t_withdraw WHERE user_id = ? ORDER BY withdraw_id DESC";
        $withdrawDetail = $this->db->getAll($sql, $this->userId);
        array_walk($withdrawDetail, function (&$v) use ($statusArray) {
            $v['status'] = $statusArray[$v['status']];
            $v['wTime'] = strtotime($v['wTime']) * 1000;
        });
        return new ApiReturn($withdrawDetail);    
    }
}