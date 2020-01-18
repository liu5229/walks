<?php 

Class AdminAdController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_advertise";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_advertise ORDER BY advertise_id LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => (int) $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action']) && isset($_POST['id'])) {
            $uploadImg = '';
            if (isset($_POST['advertise_image']['file']['response']['data'][0]['file']['name'])) {
                $uploadImg = 'img/' . $_POST['advertise_image']['file']['response']['data'][0]['file']['name'];
            }
            switch ($_POST['action']) {
                case 'edit':
                    if ($_POST['id']) {
                        $sql = 'UPDATE t_advertise SET ';
                    } else {
                        $sql = 'INSERT INTO t_advertise SET ';
                    }
                    $sql .= 'advertise_name = :advertise_name,
                            advertise_type = :advertise_type,
                            advertise_url = :advertise_url,
                            advertise_location = :advertise_location,
                            advertise_status = :advertise_status';
                    $dataArr = array('advertise_name' => $_POST['advertise_name'] ?? 0, 
                            'advertise_type' => $_POST['advertise_type'] ?? 0, 
                            'advertise_url' => $_POST['advertise_url'] ?? '', 
                            'advertise_location' => $_POST['advertise_location'] ?? '', 
                            'advertise_status' => $_POST['advertise_status'] ?? '');
                    if ($uploadImg) {
                        $sql .= ', advertise_image = :advertise_image';
                        $dataArr['advertise_image'] = $uploadImg;
                    }
                    if ($_POST['id']) {
                        $sql .= ' WHERE advertise_id = :advertise_id';
                        $dataArr['advertise_id'] = $_POST['id'];
                    }
                    $return = $this->db->exec($sql, $dataArr);
                    if ($return) {
                        return array();
                    } else {
                        throw new \Exception("Operation failure");
                    }
                    break;
            }
        }
        $activityInfo = array();
        if (isset($_POST['id'])) {
            $sql = "SELECT * FROM t_advertise WHERE advertise_id = ?";
            $activityInfo = $this->db->getRow($sql, $_POST['id']);
        }
        if ($activityInfo) {
            return $activityInfo;
        }
        throw new \Exception("Error Id");
    }
}