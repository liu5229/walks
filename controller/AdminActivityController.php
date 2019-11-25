<?php 

Class AdminActivityController extends AbstractController {
    public function listAction () {
        $sql = "SELECT COUNT(*) FROM jy_activity";
        $totalCount = $this->db->getOne($sql);
        $list = array();
        if ($totalCount) {
            $limitStart = ($this->paramObj->pageNo - 1) * $this->paramObj->pageSize;
            $limitCount = $this->paramObj->pageSize;
            $sql = "SELECT * FROM jy_activity ORDER BY activity_id LIMIT {$limitStart}, {$limitCount}";
            $list = $this->db->getAll($sql);
        }
        return array(
            'totalCount' => $totalCount,
            'list' => $list
        );
    }
}