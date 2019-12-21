<?php 

Class AdminVersionController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM t_version";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $sql = "SELECT * FROM t_version ORDER BY version_id DESC LIMIT " . $this->page;
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => $totalCount,
            'list' => $list
        );
    }
    
    public function detailAction () {
        if (isset($_POST['action']) && isset($_POST['id'])) {
            if ($_POST['id']) {
                $sql = "UPDATE t_version SET
                        version_name = :version_name,
                        is_force_update = :is_force_update,
                        version_url = :version_url,
                        version_log = :version_log
                        WHERE version_id = :version_id";
                $return = $this->db->exec($sql, array(
                    'version_name' => $_POST['version_name'] ?? '', 
                    'is_force_update' => $_POST['is_force_update'] ?? 0, 
                    'version_url' => $_POST['version_url'] ?? '', 
                    'version_log' => $_POST['version_log'] ?? '', 
                    'version_id' => $_POST['id']));
            } else {
                $sql = "INSERT INTO t_version SET
                        version_name = :version_name,
                        is_force_update = :is_force_update,
                        version_url = :version_url,
                        version_log = :version_log";
                $return = $this->db->exec($sql, array(
                    'version_name' => $_POST['version_name'] ?? '', 
                    'is_force_update' => $_POST['is_force_update'] ?? 0, 
                    'version_url' => $_POST['version_url'] ?? '', 
                    'version_log' => $_POST['version_log'] ?? ''));
            }
            if ($return) {
                return array();
            } else {
                throw new \Exception("Operation failure");
            }
        }
        $versionInfo = array();
        if (isset($_POST['version_id'])) {
            $sql = "SELECT * FROM t_version WHERE version_id = ?";
            $versionInfo = $this->db->getRow($sql, $_POST['version_id']);
        }
        if ($versionInfo) {
            return $versionInfo;
        }
        throw new \Exception("Error Activity Id");
    }
}