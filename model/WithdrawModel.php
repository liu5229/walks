<?php


class WithdrawModel extends AbstractModel
{

    public function updateStatus ($params) {
        if (!isset($params['withdraw_status']) || !isset($params['withdraw_id'])) {
            return FALSE;
        }
        $sql = 'UPDATE t_withdraw SET withdraw_status = :withdraw_status, withdraw_remark = :withdraw_remark, change_time = :change_time WHERE withdraw_id = :withdraw_id';
        return $this->db->exec($sql, array('withdraw_status' => $params['withdraw_status'],'withdraw_remark' => $params['withdraw_remark'] ?? '','change_time' => date('Y-m-d H:i:s'),'withdraw_id' => $params['withdraw_id']));
    }

    public function insert () {
//                if (REDIS_ENABLE) {
//                    $redis = new \Redis();
//                    $redis->pconnect(REDIS_NAME, 6379);
//                    $redis->select(1);
//                    $key = 'wd:' . $this->userId . ':' . $withdrawalAmount;//提现key
//                    if ($redis->setnx($key, '1')) {
//                        $sql = 'INSERT INTO t_withdraw SET user_id = :user_id, withdraw_amount = :withdraw_amount, withdraw_gold = :withdraw_gold, withdraw_status = :withdraw_status, withdraw_method = :withdraw_method, wechat_openid = :wechat_openid';
//                        $this->db->exec($sql, array('user_id' => $this->userId, 'withdraw_amount' => $withdrawalAmount, 'withdraw_gold' => $withdrawalGold, 'withdraw_method' => 'wechat', 'withdraw_status' => 'pending', 'wechat_openid' => $payInfo['openid']));
//                        $redis->expire($key, 10);
//                        return new ApiReturn('');
//                    } else {
//                        if (-1 == $redis->ttl($key)) {
//                            $redis->expire($key, 50);
//                        }
//                        return new ApiReturn('', 206, '重复提交');
//                    }
//                }
    }
}