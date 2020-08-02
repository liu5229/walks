<?php

Class Activity3Controller extends Activity2Controller {

    public function contestAction() {
        $todayDate = date("Y-m-d");
        $tomorrowDate = date("Y-m-d", strtotime('-1 day'));

        $sql = 'SELECT * FROM t_walk_contest LEFT JOIN t_walk USING(user_id) WHERE contest_date = ? AND user_id = ?';
        $todayContest = $this->db->getAll($sql, $todayDate);
        if ($todayContest) {

        } else {

        }

        $sql = 'SELECT * FROM t_walk_contest LEFT JOIN t_user WHERE contest_date = ?';
        $tomorrowContest = $this->db->getAll($sql, $tomorrowDate);

    }
}

