<?php 

Class VideoController extends AbstractController {
    protected $userId;
    
    public function init() {
        parent::init();
        $userId = $this->model->user->verifyToken();
        if ($userId instanceof apiReturn) {
            return $userId;
        }
        $this->userId = $userId;
    }

    //视频列表接口 带翻页
    public function listAction() {
        if (isset($this->inputData['amount']) && $this->inputData['amount']) {
            //是否绑定微信
            $sql = 'SELECT unionid, openid, umeng_token, user_status FROM t_user WHERE user_id = ?';
            $payInfo = $this->db->getRow($sql, $this->userId);
            if (!$payInfo['user_status']) {
                return new ApiReturn('', 408, '申请失败');
            }
        }
    }
    //观看视频接口
    public function watchAction () {

    }
    //每日已领取的金币次数，最大金币次数，奖励数组[1,2,4,5,6],额外奖励
    public function configAction () {

    }
    //领取奖励（两种/三种  单次奖励，额外首次奖励，每5次额外奖励）


}