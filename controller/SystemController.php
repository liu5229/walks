<?php 

Class SystemController extends AbstractController {

//    public function init() {
//        parent::init();
//        $userId = $this->model->user->verifyToken();
//        if ($userId instanceof apiReturn) {
//            return $userId;
//        }
//        $this->userId = $userId;
//    }
    //interactionExpress

//newer1:[2,7,12,17,22];
//newer2:[,,];
//normal:[2,12,22,32,42];

    public function ieConfigAction () {

        return array('newer1' => array(2, 7, 12, 17, 22), 'newer2' => array(2, 12, 22, 32, 42), 'normal' => array(15, 60));
    }
}