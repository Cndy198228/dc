<?php
/**
 * 数据库配置文件
 * 
 * 包含数据库连接参数和数据库连接函数
 */

// 数据库连接参数
define('DB_HOST', 'localhost');
define('DB_USER', 'web');
define('DB_PASS', 'FYZnh198228@@'); 
define('DB_NAME', 'web');

/**
 * 获取数据库连接
 * 
 * @return mysqli 数据库连接对象
 */
function getDbConnection() {
    // 创建连接
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // 检查连接
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    
    // 设置字符集
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * 关闭数据库连接
 * 
 * @param mysqli $conn 数据库连接对象
 */
function closeDbConnection($conn) {
    $conn->close();
}

/**
 * 为用户创建专属数据表
 * 
 * @param int $userId 用户ID
 * @param string $username 用户名
 * @return bool 是否成功创建表
 */
function createUserTables($userId, $username) {
    $conn = getDbConnection();
    
    // 创建用户专属的菜品表
    $tableName = 'dishes_' . $username;
    $sql = "CREATE TABLE IF NOT EXISTS $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $result = $conn->query($sql);
    
    closeDbConnection($conn);
    
    return $result;
}