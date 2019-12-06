<?php 

Class AdminActivityController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_activity";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_activity ORDER BY activity_id LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'edit':
                    if (isset($_POST['id'])) {
                        $sql = "UPDATE t_activity SET
                                activity_award = :activity_award,
                                activity_name = :activity_name,
                                activity_type = :activity_type,
                                activity_max = :activity_max,
                                activity_desc = :activity_desc
                                WHERE activity_id = :activity_id";
                        $return = $this->db->exec($sql, array('activity_award' => $_POST['activity_award'] ?? '', 
                            'activity_name' => $_POST['activity_name'] ?? '', 
                            'activity_max' => $_POST['activity_max'] ?? '', 
                            'activity_type' => $_POST['activity_type'] ?? '', 
                            'activity_desc' => $_POST['activity_desc'] ?? '', 
                            'activity_id' => $_POST['id']));
                        if ($return) {
                            return array();
                        } else {
                            throw new \Exception("Operation failure");
                        }
                    }
                    break;
                case 'add':
                    $sql = "INSERT INTO t_activity SET
                            activity_award = :activity_award,
                            activity_name = :activity_name,
                            activity_type = :activity_type,
                            activity_max = :activity_max,
                            activity_desc = :activity_desc";
                    $return = $this->db->exec($sql, array('activity_award' => $_POST['activity_award'] ?? '', 
                        'activity_type' => $_POST['activity_type'] ?? '', 
                        'activity_name' => $_POST['activity_name'] ?? '', 
                        'activity_max' => $_POST['activity_max'] ?? '', 
                        'activity_desc' => $_POST['activity_desc'] ?? ''));
                    if ($return) {
                        return array();
                    } else {
                        throw new \Exception("Operation failure");
                    }
                    break;
            }
        }
        $activityInfo = array();
        if (isset($_POST['activity_id'])) {
            $sql = "SELECT * FROM t_activity WHERE activity_id = ?";
            $activityInfo = $this->db->getRow($sql, $_POST['activity_id']);
        }
        if ($activityInfo) {
            return $activityInfo;
        }
        throw new \Exception("Error Activity Id");
    }
}