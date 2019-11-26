<?php 

Class AdminBaseController extends AbstractController {
    
    public function loginAction() {
        if (isset($_POST['username']) && isset($_POST['password'])) {
            if ('admin' == $_POST['username']) {
                $verifyPass = $_POST['password'];
                if (md5('HA@yMMWqki') == $_POST['password']) {
                    return array();
                }
            }
        }
//        $this->mode = 'POST';
//        $sql = 'SELECT * FROM t_order LIMIT 1';
//        var_dump($this->db->getRow($sql));
//  data: {
//    ticket: 'ticket',
//    token: '11111',
//  },
//  msg: '操作成功',
//  status: 1,
        throw new \Exception("Login failure");
    }
    
    public function menuAction() {
//    list: [
//      {
//        id: 600110233,
//        resName: '图表',
//        resKey: 'echarts',
//        resIcon: 'statistics',
//      },
//
//      {
//        id: 10062,
//        resName: '设置中心',
//        children: [
//          {
//            id: 10108,
//            resName: '用户管理',
//            resKey: 'set$/userManage',
//            resIcon: 'userManage',
//          },
//          {
//            id: 10109,
//            resName: '角色管理',
//            resKey: 'set$/roleManage',
//            resIcon: 'roleManage',
//          },
//          {
//            id: 10110,
//            resName: '权限管理',
//            resKey: 'set$/moduleManage',
//            resIcon: 'moduleManage',
//          },
//        ],
//        resKey: 'set$',
//        resIcon: 'xtxg',
//      },
//    ],
        return  array('list' => array(
//            array( 'id' => 10063, 'resName' => '用户管理', 'resKey'=> 'list', 'resIcon'=> 'pgmb'),
            array( 'id' => 2, 'resName' => '活动管理', 'resKey'=> 'activity', 'resIcon'=> 'statistics')
        ));
    }
    
    public function userInfoAction () {
//  data: {
//    id: 1,
//    username: 'admin',
//    password: '121212',
//    chineseName: '管理员',
//    idcardNo: '000000000000000000',
//    policeCode: '000000',
//    deptCode: '370200000000',
//    gender: 1,
//    email: 'abc@abc.com',
//    phoneNo: '15100000005',
//    duty: '超级管理员',
//    address: 'address',
//    remark: 'remarl',
//    type: 0,
//    status: 0,
//    roles: [
//      {
//        id: 1,
//        roleName: '超级管理员',
//        resources: [],
//      },
//    ],
//    deptName: '杭州市',
//    ticket: '.2XxGlEuidOmAoYIdSo6pQIlGbQSh83U7p4eJsoTO-70',
//    gxdwdm: '370200000000',
//    deptLevel: '1',
//    defaultDeptCode: '370200000000',
//    defaultXzqhCode: '370200',
//  }
        return array('id' => 1);
    }
    
    public function logoutAction() {
        return array();
    }
}