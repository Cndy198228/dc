<?php
/**
 * 用户管理API
 * 
 * 处理用户的编辑、启用/停用和删除
 */

// 启动会话
session_start();

// 检查是否已登录且是管理员
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未授权']);
    exit;
}

// 包含数据库配置
require_once '../config/db_config.php';

// 获取管理员ID
$admin_id = $_SESSION['user_id'];

// 处理请求
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'edit':
        // 编辑用户
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $shop_name = isset($_POST['shop_name']) ? trim($_POST['shop_name']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $tax_id = isset($_POST['tax_id']) ? trim($_POST['tax_id']) : '';
        $tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) : 0;
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if ($user_id <= 0 || $user_id == $admin_id) {
            echo json_encode(['success' => false, 'message' => '无效的用户ID']);
            exit;
        }
        
        if (empty($shop_name) || empty($address) || empty($phone) || empty($tax_id) || $tax_rate <= 0) {
            echo json_encode(['success' => false, 'message' => '请填写所有必填字段']);
            exit;
        }
        
        $conn = getDbConnection();
        
        // 更新用户信息
        if (!empty($password)) {
            // 更新包括密码
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET shop_name = ?, address = ?, phone = ?, tax_id = ?, tax_rate = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssssdsi", $shop_name, $address, $phone, $tax_id, $tax_rate, $hashed_password, $user_id);
        } else {
            // 不更新密码
            $stmt = $conn->prepare("UPDATE users SET shop_name = ?, address = ?, phone = ?, tax_id = ?, tax_rate = ? WHERE id = ?");
            $stmt->bind_param("ssssdi", $shop_name, $address, $phone, $tax_id, $tax_rate, $user_id);
        }
        
        $success = $stmt->execute();
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => $success ? '用户信息已更新' : '更新失败']);
        break;
        
    case 'toggle_status':
        // 启用/停用用户
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        
        if ($user_id <= 0 || $user_id == $admin_id) {
            echo json_encode(['success' => false, 'message' => '无效的用户ID']);
            exit;
        }
        
        $conn = getDbConnection();
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $user_id);
        $success = $stmt->execute();
        
        closeDbConnection($conn);
        
        $statusText = $status ? '启用' : '停用';
        echo json_encode(['success' => $success, 'message' => $success ? "用户已{$statusText}" : "操作失败"]);
        break;
        
    case 'delete':
        // 删除用户
        $user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($user_id <= 0 || $user_id == $admin_id) {
            echo json_encode(['success' => false, 'message' => '无效的用户ID']);
            exit;
        }
        
        $conn = getDbConnection();
        
        // 开始事务
        $conn->begin_transaction();
        
        try {
            // 删除用户的菜品
            $stmt = $conn->prepare("DELETE FROM dishes WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // 删除用户的订单项目
            $stmt = $conn->prepare("DELETE oi FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // 删除用户的订单
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // 删除用户
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // 提交事务
            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            $success = false;
        }
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => $success ? '用户已删除' : '删除失败']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}