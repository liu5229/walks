<?php

define('ROOT_DIR', dirname(__DIR__)  . '/');
define('CONFIG_DIR', ROOT_DIR  . '/config/');
define('CORE_DIR', ROOT_DIR  . '/core/');
define('CONTROLLER_DIR', ROOT_DIR  . '/controller/');
define('MODEL_DIR', ROOT_DIR  . '/model/');
define('UPLOAD_DIR', ROOT_DIR  . '/upload/');

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

!defined('JY_WALK_ADMIN_USER') && define('JY_WALK_ADMIN_USER', 'username');
!defined('JY_WALK_ADMIN_PASSWORD') && define('JY_WALK_ADMIN_PASSWORD', '123456');

/**
 * register autoload
 */
require CORE_DIR . 'AutoLoad.php';
AutoLoad::register();
