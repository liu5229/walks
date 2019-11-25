<?php 

Class AdminUserController extends AbstractController {
    public function listAction () {
//        $this->mode = 'POST';
//        $sql = 'SELECT * FROM t_order LIMIT 1';
//        var_dump($this->db->getRow($sql));
        return array(
            'pageSize' => 10,
            'pageNo' => 1,
            'totalCount' => 20,
            'list' => array(array(
                'id' => 10240,
                'username' => 'tgramxs',
                'password' => '123456',
                'chineseName' => '销售部',
                'idcardNo' => '332527198010230505',
                'deptCode' => '370200000000',
                'phoneNo' => '18969784568',
                'status' => 0,
                'roles' => array(array(
                    'id' => 10100,
                    'roleName' => '演示账号',
                    'resources' => array()
                )),
                'gxdwdm' => '370200000000',
            )),
            'totalPage' => 2
        );
    }
    
    public function detailAction() {
        return array('id'=> 10240,
            'username'=> 'tgramxs',
            'password'=> '123456',
            'chineseName'=> '销售部',
            'idcardNo'=> '332527198010230505',
            'deptCode'=> '370200000000',
            'phoneNo'=> '18969784568',
            'status'=> 0,
            'roleIds'=> [10100],
            'gxdwdm'=> '370200000000');
    }
}