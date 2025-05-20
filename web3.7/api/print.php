<?php
/**
 * 打印相关API
 * 
 * 处理小票打印和订单保存
 */

// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit;
}

// 包含数据库配置
require_once '../config/db_config.php';

// 获取用户ID
$user_id = $_SESSION['user_id'];

// 处理请求
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'save_order':
        // 保存订单
        $table_number = isset($_POST['table_number']) ? trim($_POST['table_number']) : '';
        $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
        $items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
        
        if (empty($table_number) || $total_amount <= 0 || empty($items)) {
            echo json_encode(['success' => false, 'message' => '无效的订单数据']);
            exit;
        }
        
        $conn = getDbConnection();
        
        // 开始事务
        $conn->begin_transaction();
        
        try {
            // 插入订单
            $stmt = $conn->prepare("INSERT INTO orders (user_id, table_number, total_amount, tax_amount) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isdd", $user_id, $table_number, $total_amount, $tax_amount);
            $stmt->execute();
            
            // 获取订单ID
            $order_id = $conn->insert_id;
            
            // 插入订单项目
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, dish_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $stmt->bind_param("isdid", $order_id, $item['name'], $item['price'], $item['quantity'], $item['subtotal']);
                $stmt->execute();
            }
            
            // 提交事务
            $conn->commit();
            $success = true;
            $message = '订单已保存';
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            $success = false;
            $message = '保存订单失败：' . $e->getMessage();
        }
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => $message, 'order_id' => $success ? $order_id : 0]);
        break;
        
    case 'get_orders':
        // 获取订单列表
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $conn = getDbConnection();
        
        $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}