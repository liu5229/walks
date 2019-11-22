<?php
try {
    require_once 'init.inc.php';
    $requestUrl = strtok($_SERVER['REQUEST_URI'], '?');
    // remove the base path
    $requestUrl = substr($requestUrl, strlen('/'));
    // raw urldecode
    $requestUrl = rawurldecode($requestUrl);
    
    list($controllerName, $actionName) = explode('/', $requestUrl);
    if ($controllerName) {
        $fullControllerName = ucfirst($controllerName) . 'Controller';
        $controller = new $fullControllerName();
        if ($actionName) {
            $fullActionName = ucfirst($actionName) . 'Action';
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
    $return = array('status' => 'ok', 'data' => $result, 'msg' => '');
} catch(\Exception $e) {
    $return = array('status' => 'error', 'data' => '', 'msg' => $e->getMessage());
}
//var_dump($return);
echo json_encode($return);