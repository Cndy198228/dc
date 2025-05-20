<?php
/**
 * 退出登录页面
 * 
 * 用户可以通过此页面退出系统
 */

// 启动会话
session_start();

// 清除所有会话变量
$_SESSION = array();

// 如果要彻底销毁会话，还需要删除会话cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
header('Location: index.php');
exit;