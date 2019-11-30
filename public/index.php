<?php
try {
    require_once 'init.inc.php';
    $requestUrl = strtok($_SERVER['REQUEST_URI'], '?');
    // remove the base path
    $requestUrl = substr($requestUrl, strlen('/'));
    // raw urldecode
    $requestUrl = rawurldecode($requestUrl);
    
    $routerArr = explode('/', $requestUrl);
    if (isset($routerArr[0]) && $routerArr[0]) {
        $controllerName = preg_replace('/\s+/', '', ucwords(str_replace('-', ' ', $routerArr[0])));
        $fullControllerName = $controllerName . 'Controller';
        $controller = new $fullControllerName();
        if (isset($routerArr[1]) && $routerArr[1]) {
            $actionName = preg_replace('/\s+/', '', ucwords(str_replace('-', ' ', $routerArr[1])));
            $fullActionName = $actionName . 'Action';
            if (method_exists($controller, $fullActionName)) {
                $result = $controller->$fullActionName();
            } else {
                throw new \Exception("Can't autoload action " . $actionName);
            }
        } else {
            throw new \Exception("Need a action");
        }
    } else {
        throw new \Exception("Need a controller");
    }
    if ($result instanceof apiReturn) {
        $return = array('code' => $result->code, 'data' => $result->data, 'msg' => $result->msg);
    } else {
        $return = array('status' => 'ok', 'data' => $result, 'msg' => '');
    }
} catch(\Exception $e) {
    if (in_array($e->getMessage(), array(201))) {
        $return = array('code' => 201, 'data' => '', 'msg' => 'Token lost or error'); 
    } else {
        $return = array('status' => 'error', 'data' => '', 'msg' => $e->getMessage());
    }
}
//var_dump($return);
echo json_encode($return);