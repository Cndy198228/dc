<?php
/**
 * 菜品管理API
 * 
 * 处理菜品的添加、删除和导入
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
    case 'search':
        // 搜索菜品（用于自动补全）
        $query = isset($_POST['query']) ? trim($_POST['query']) : '';
        
        $conn = getDbConnection();
        
        // 搜索匹配的菜品
        if (!empty($query)) {
            $search = "%$query%";
            $stmt = $conn->prepare("SELECT id, name, price FROM dishes WHERE user_id = ? AND name LIKE ? ORDER BY name LIMIT 10");
            $stmt->bind_param("is", $user_id, $search);
        } else {
            // 如果查询为空，返回所有菜品
            $stmt = $conn->prepare("SELECT id, name, price FROM dishes WHERE user_id = ? ORDER BY name LIMIT 10");
            $stmt->bind_param("i", $user_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dishes = [];
        while ($row = $result->fetch_assoc()) {
            $dishes[] = $row;
        }
        
        closeDbConnection($conn);
        
        // 返回匹配的菜品列表，即使为空也返回成功状态
        echo json_encode(['success' => true, 'dishes' => $dishes]);
        break;
        
    case 'add':
        // 添加菜品
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        
        if (empty($name) || $price <= 0) {
            echo json_encode(['success' => false, 'message' => '菜品名称和价格不能为空']);
            exit;
        }
        
        $conn = getDbConnection();
        
        // 检查菜品是否已存在
        $stmt = $conn->prepare("SELECT id FROM dishes WHERE user_id = ? AND name = ?");
        $stmt->bind_param("is", $user_id, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // 更新价格
            $stmt = $conn->prepare("UPDATE dishes SET price = ? WHERE user_id = ? AND name = ?");
            $stmt->bind_param("dis", $price, $user_id, $name);
            $success = $stmt->execute();
            $message = '菜品价格已更新';
        } else {
            // 添加新菜品
            $stmt = $conn->prepare("INSERT INTO dishes (user_id, name, price) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $user_id, $name, $price);
            $success = $stmt->execute();
            $message = '菜品已添加';
        }
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => $message]);
        break;
        
    case 'delete':
        // 删除菜品
        $dish_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($dish_id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的菜品ID']);
            exit;
        }
        
        $conn = getDbConnection();
        
        // 确保只删除当前用户的菜品
        $stmt = $conn->prepare("DELETE FROM dishes WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $dish_id, $user_id);
        $success = $stmt->execute();
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => $success ? '菜品已删除' : '删除失败']);
        break;
        
    case 'import':
        // 导入Excel菜品
        if (!isset($_FILES['excel-file']) || $_FILES['excel-file']['error'] != 0) {
            echo json_encode(['success' => false, 'message' => '请选择有效的Excel文件']);
            exit;
        }
        
        // 检查文件类型
        $file_name = $_FILES['excel-file']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, ['xlsx', 'xls', 'csv', 'txt'])) {
            echo json_encode(['success' => false, 'message' => '请上传支持的文件格式（.xlsx, .xls, .csv或.txt）']);
            exit;
        }
        
        // 检查文件大小（限制为2MB）
        if ($_FILES['excel-file']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => '文件大小不能超过2MB']);
            exit;
        }
        
        // 解析Excel文件并导入菜品
        $conn = getDbConnection();
        
        // 临时文件路径
        $tmp_file = $_FILES['excel-file']['tmp_name'];
        
        // 根据文件类型选择不同的解析方法
        $dishes = [];
        
        if ($file_ext == 'csv') {
            // 解析CSV文件
            if (($handle = fopen($tmp_file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 2 && is_numeric($data[1])) {
                        $dishes[] = ['name' => trim($data[0]), 'price' => floatval($data[1])];
                    }
                }
                fclose($handle);
            }
        } else if ($file_ext == 'txt') {
            // 解析TXT文件
            if (($handle = fopen($tmp_file, "r")) !== FALSE) {
                while (($line = fgets($handle)) !== FALSE) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    // 尝试多种分隔符：制表符、逗号、分号、空格
                    $separators = ["\t", ",", ";", " "];
                    
                    foreach ($separators as $separator) {
                        $parts = explode($separator, $line);
                        
                        // 如果分割后至少有两部分，并且最后一部分是数字
                        if (count($parts) >= 2) {
                            // 最后一个部分作为价格
                            $price_part = end($parts);
                            $price_candidate = trim(preg_replace('/[^0-9\.]/u', '', $price_part));
                            
                            if (is_numeric($price_candidate) && floatval($price_candidate) > 0) {
                                // 前面所有部分作为菜品名称
                                array_pop($parts); // 移除价格部分
                                $name = trim(implode($separator, $parts));
                                
                                if (!empty($name)) {
                                    $dishes[] = ['name' => $name, 'price' => floatval($price_candidate)];
                                    break; // 找到一个有效的分隔符后，不再尝试其他分隔符
                                }
                            }
                        }
                    }
                }
                fclose($handle);
            }
        } else {
            // 对于Excel文件，使用PHP内置函数解析
            // 读取Excel文件内容
            $excel_data = file_get_contents($tmp_file);
            
            // 检查文件头部标识，确认是否为Excel文件
            $is_xlsx = (substr($excel_data, 0, 4) === "\x50\x4B\x03\x04"); // XLSX文件头部标识
            $is_xls = (substr($excel_data, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"); // XLS文件头部标识
            
            if ($is_xlsx || $is_xls) {
                // 使用更简单的方法解析Excel文件内容
                // 首先尝试按行分割
                $lines = preg_split('/[\r\n]+/', $excel_data);
                
                foreach ($lines as $line) {
                    // 尝试多种分隔符：制表符、逗号、分号、空格
                    $separators = ["\t", ",", ";", " "];
                    
                    foreach ($separators as $separator) {
                        $parts = explode($separator, $line);
                        
                        // 如果分割后至少有两部分，并且第二部分是数字
                        if (count($parts) >= 2) {
                            // 清理并检查第一部分是否为有效的菜品名称
                            $name = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $parts[0]));
                            
                            // 查找第一个数字作为价格
                            $price = null;
                            for ($i = 1; $i < count($parts); $i++) {
                                $price_candidate = trim(preg_replace('/[^0-9\.]/u', '', $parts[$i]));
                                if (is_numeric($price_candidate) && floatval($price_candidate) > 0) {
                                    $price = floatval($price_candidate);
                                    break;
                                }
                            }
                            
                            // 如果找到有效的名称和价格，添加到菜品列表
                            if (!empty($name) && $price !== null) {
                                $dishes[] = ['name' => $name, 'price' => $price];
                                break; // 找到一个有效的分隔符后，不再尝试其他分隔符
                            }
                        }
                    }
                }
                
                // 如果上面的方法没有找到任何菜品，尝试使用正则表达式
                if (empty($dishes)) {
                    $patterns = [
                        '/([\w\s\p{Han}\p{P}&&[^\d]]{1,})[\s\t]*(\d+(?:\.\d+)?)/u',
                        '/([^\d\n\r]{2,})[\s\t]*(\d+(?:\.\d+)?)/u',
                        '/([^\d\n\r\t,;]{2,})[^\d\n\r\t,;]*(\d+(?:\.\d+)?)/u'
                    ];
                    
                    foreach ($patterns as $pattern) {
                        preg_match_all($pattern, $excel_data, $matches, PREG_SET_ORDER);
                        
                        foreach ($matches as $match) {
                            if (isset($match[1]) && isset($match[2]) && is_numeric($match[2])) {
                                $name = trim($match[1]);
                                $price = floatval($match[2]);
                                if (!empty($name) && $price > 0) {
                                    $dishes[] = ['name' => $name, 'price' => $price];
                                }
                            }
                        }
                        
                        // 如果找到了数据，就不再尝试其他模式
                        if (!empty($dishes)) {
                            break;
                        }
                    }
                }
            }
        }
        
        // 检查是否解析到菜品数据
        if (empty($dishes)) {
            closeDbConnection($conn);
            echo json_encode(['success' => false, 'message' => '无法从文件中解析菜品数据，请确保文件格式正确（第一列为菜品名称，第二列为价格）']);
            exit;
        }
        
        $success = true;
        $imported_count = 0;
        
        foreach ($dishes as $dish) {
            // 检查菜品是否已存在
            $stmt = $conn->prepare("SELECT id FROM dishes WHERE user_id = ? AND name = ?");
            $stmt->bind_param("is", $user_id, $dish['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // 更新价格
                $stmt = $conn->prepare("UPDATE dishes SET price = ? WHERE user_id = ? AND name = ?");
                $stmt->bind_param("dis", $dish['price'], $user_id, $dish['name']);
                $success = $stmt->execute() && $success;
            } else {
                // 添加新菜品
                $stmt = $conn->prepare("INSERT INTO dishes (user_id, name, price) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $user_id, $dish['name'], $dish['price']);
                $success = $stmt->execute() && $success;
                $imported_count++;
            }
        }
        
        closeDbConnection($conn);
        
        echo json_encode(['success' => $success, 'message' => "成功导入{$imported_count}个菜品", 'count' => $imported_count]);
        break;
        
    case 'list':
        // 获取菜品列表（支持分页）
        $conn = getDbConnection();
        
        // 获取分页参数
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 15; // 默认每页15条
        
        // 确保页码和每页数量有效
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 15;
        
        // 计算偏移量
        $offset = ($page - 1) * $limit;
        
        // 获取总记录数
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM dishes WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $total = $result->fetch_assoc()['total'];
        
        // 获取当前页的菜品
        $stmt = $conn->prepare("SELECT id, name, price FROM dishes WHERE user_id = ? ORDER BY name LIMIT ?, ?");
        $stmt->bind_param("iii", $user_id, $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $dishes = [];
        while ($row = $result->fetch_assoc()) {
            $dishes[] = $row;
        }
        
        closeDbConnection($conn);
        
        // 返回菜品列表、总记录数和分页信息
        echo json_encode([
            'success' => true, 
            'dishes' => $dishes,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}