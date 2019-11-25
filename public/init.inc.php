<?php

define('ROOT_DIR', dirname(__DIR__)  . '/');
define('CONFIG_DIR', ROOT_DIR  . '/config/');

/**
 * load the private configure
 */
if (file_exists(CONFIG_DIR . 'config.private.php')) {
    include CONFIG_DIR . "config.private.php";
}

!defined('DB_HOST') && define('DB_HOST', '192.168.0.25');
!defined('DB_PORT') && define('DB_PORT', 3306);
!defined('DB_USERNAME') && define('DB_USERNAME', 'root');
!defined('DB_PASSWORD') && define('DB_PASSWORD', '123456');
!defined('DB_DATABASE') && define('DB_DATABASE', 'jy_walk');

function autoload ($controllerName) {
    $fileAutoFindArr = array(ROOT_DIR . 'controller/', ROOT_DIR . 'core/');
    foreach ($fileAutoFindArr as $fileDir) {
        $file = $fileDir . $controllerName . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    throw new \Exception("Can't autoload controller " . $controllerName);
}
spl_autoload_register('autoload');