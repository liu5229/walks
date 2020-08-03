<?php

Class Activity3Controller extends Activity2Controller {

    public function contestAction() {
        $todayDate = date("Y-m-d");
        $tomorrowDate = date("Y-m-d", strtotime('+1 day'));
        $yesterdayDate = date("Y-m-d", strtotime('-1 day'));
        $return = array();
        $awardConfig = array(3000 => 20, 5000 => 500, 10000 => 1000);
        $todayWalks = NULL;

        foreach (array(3000, 5000, 10000) as $walks) {
            // 查看是否报名今日活动
            $sql = 'SELECT * FROM t_walk_contest LEFT JOIN t_walk_contest_user USING(contest_id) WHERE contest_date = ? AND user_id = ? AND contest_level = ?';
            $todayContest = $this->db->getRow($sql, $todayDate, $this->userId, $walks);
            $sql = 'SELECT * FROM t_walk_contest WHERE contest_date = ? AND contest_level = ?';
            $tomorrowContest = $this->db->getRow($sql, $tomorrowDate, $walks);

            $sql = 'SELECT COUNT(id) FROM t_walk_contest_user WHERE user_id = ? AND contest_id = ?';
            $isNextReg = $this->db->getOne($sql, $this->userId, $tomorrowContest['contest_id']) ? 1 : 0;
            // 查询明日活动
            if ($todayContest) {
                if (NULL === $todayWalks) {
                    $sql = 'SELECT total_walk FROM t_walk WHERE user_id = ? AND walk_date = ?';
                    $todayWalks = $this->db->getOne($sql, $this->userId, $todayDate) ?? 0;
                }
                //报名今天
                //  期数 名称 periods
                //	预计可获得金币 expectedAward
                //	当前达标 completeCount
                //	总奖池 totalAward
                //	当前步数 currentWalks
                //	目标步数 targetWalks
                //	当前时间 currentTime
                //	结束时间 endTime
                //	下期是否已报名 isNextReg
                //	下期的报名费用 nextRegFee // todo
                //	下期最低奖励 nextMinAward // todo
                //	下期期数 名称 nextPeriods
                //	下期报名人数 nextRegCount
                //	下期奖池 nextTotalAward

                $return[$walks]['current'] = array('periods' => $todayContest['contest_periods'], 'expectedReward' => ceil($todayContest['total_count'] * $awardConfig[$walks] / $todayContest['complete_count']), 'completeCount' => $todayContest['complete_count'], 'totalAward' => $todayContest['total_count'] * $awardConfig[$walks], 'currentWalks' => $todayWalks, 'targetWalks' => $walks, 'currentTime' => time() * 1000, 'endTime' => strtotime($todayContest['contest_date'] . ' 23:59:59') * 1000, 'isNextReg' => $isNextReg, 'nextPeriods' => $tomorrowContest['contest_periods'], 'nextRegCount' => $tomorrowContest['total_count'], 'nextTotalAward' => $tomorrowContest['total_count'] * $awardConfig[$walks]);
            } else {
                //  期数名称 periods
                //	总奖池 totalAward
                //	报名人数 regCount
                //	当前时间 currentTime
                //	开始时间 startTime
                //	是否已报名 isReg
                //	报名费用 regFee // todo
                //	最低奖励 minAward // todo
                //未报名今天
                $return[$walks]['next'] = array('periods' => $tomorrowContest['contest_periods'], 'totalAward' => $tomorrowContest['total_count'] * $awardConfig[$walks], 'regCount' => $tomorrowContest['total_count'], 'currentTime' => time() * 1000, 'startTime' => strtotime($tomorrowContest['contest_date'] . '00:00:00') * 1000, 'isReg' => $isNextReg);
            }
        }

        // 昨日活动奖励
        $sql = 'SELECT * FROM t_walk_contest c LEFT JOIN t_walk_contest_user cu ON cu.contest_id = c.contest_id WHERE c.contest_date = ? AND user_id = ?';
        $yesterdayList = $this->db->getAll($sql, $yesterdayDate, $this->userId);
        $type = $yesterdayList ? 1 : 0;
        $receiveInfo = array();
        foreach ($yesterdayList as $info) {
            if ($info['is_complete']) {
                $sql = 'SELECT * FROM t_gold2receive WHERE user_id = ? AND receive_walk = ? AND receive_type = ? AND receive_date = ?';
                $receiveInfo[$info['contest_level']] = $this->db->getRow($sql, $this->userId, $info['contest_level'], 'walk_contest', $yesterdayDate);
                $type = 2;
            }
        }
        // type  无弹窗 弹窗未达标 弹窗可领取
        $return['award'] = array('type' => $type, 'awardList' => $receiveInfo);
        return new ApiReturn($return);
    }
}

