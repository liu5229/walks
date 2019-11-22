<?php 

Class UsercenterController extends AbstractController {
    
    public function loginAction() {
//        $this->mode = 'POST';
//        $sql = 'SELECT * FROM t_order LIMIT 1';
//        var_dump($this->db->getRow($sql));
        
        
//  data: {
//    ticket: 'ticket',
//    token: '11111',
//  },
//  msg: '操作成功',
//  status: 1,
        return array(
            'ticket' => 'ticket',
            'token' => '11111');
    }
}