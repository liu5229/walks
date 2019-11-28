<?php 

Class UserController extends AbstractController {
    
    public function infoAction() {
        $data = file_get_contents("php://input");
        var_dump($data);
        if (isset($_POST['deviceId'])) {
            $userInfo = array(
                'userId' => 10000,
                'accessToken' => 'sdffe234fasdf',
                'nickname' => 'sdffe234fasdf',
                'sex' => 1,
                'province' => 'shangHai',
                'city' => 'shangHai',
                'country' => 'China',
                'headimgurl' => 'http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0',
                'isRegistered' => true,
                'hasCashed' => true
            );
            return new apiReturn($userInfo);
        } else {
            return new apiReturn('', 301, 'miss device id');
        }
    }
    
    public function listAction() {
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
}