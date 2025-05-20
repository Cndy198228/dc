<?php
/**
 * 注册页面
 * 
 * 新用户可以通过此页面注册系统
 */

// 包含头部
include_once 'includes/header.php';

// 如果已经登录，重定向到仪表盘
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 处理注册表单提交
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 包含数据库配置
    require_once 'config/db_config.php';
    
    // 获取表单数据
    $shop_name = trim($_POST['shop_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $tax_id = trim($_POST['tax_id']);
    $tax_rate = floatval($_POST['tax_rate']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 验证输入
    if (empty($shop_name) || empty($address) || empty($phone) || empty($tax_id) || 
        empty($username) || empty($password) || empty($confirm_password)) {
        $error = '请填写所有必填字段';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致';
    } elseif ($tax_rate <= 0 || $tax_rate > 100) {
        $error = '税率必须在0-100之间';
    } else {
        // 连接数据库
        $conn = getDbConnection();
        
        // 检查用户名是否已存在
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = '用户名已存在，请选择其他用户名';
        } else {
            // 密码加密
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // 插入新用户
            $stmt = $conn->prepare("INSERT INTO users (username, password, shop_name, address, phone, tax_id, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssd", $username, $hashed_password, $shop_name, $address, $phone, $tax_id, $tax_rate);
            
            if ($stmt->execute()) {
                // 获取新用户ID
                $user_id = $conn->insert_id;
                
                // 为新用户创建专属数据表
                if (createUserTables($user_id, $username)) {
                    $success = '注册成功！请使用您的用户名和密码登录系统。';
                } else {
                    $error = '创建用户数据表失败，请联系管理员';
                }
            } else {
                $error = '注册失败：' . $stmt->error;
            }
        }
        
        // 关闭数据库连接
        closeDbConnection($conn);
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4>注册新用户</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div class="text-center mb-3">
                        <a href="index.php" class="btn btn-primary">前往登录</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="">
                        <h5 class="mb-3">店铺信息</h5>
                        <div class="mb-3">
                            <label for="shop_name" class="form-label">店铺名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">地址 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">电话 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">税号 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">税率 (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="10" required>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">账号信息</h5>
                        <div class="mb-3">
                            <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认密码 <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">注册</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">已有账号？<a href="index.php">登录</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// 包含底部
include_once 'includes/footer.php';
?>