<?php
/**
 * 登录页面
 * 
 * 用户可以通过此页面登录系统
 */

// 包含头部
include_once 'includes/header.php';

// 如果已经登录，重定向到仪表盘
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// 处理登录表单提交
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 包含数据库配置
    require_once 'config/db_config.php';
    
    // 获取表单数据
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // 验证输入
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        // 连接数据库
        $conn = getDbConnection();
        
        // 准备SQL语句
        $stmt = $conn->prepare("SELECT id, username, password, shop_name, is_admin FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // 验证密码
            if (password_verify($password, $user['password'])) {
                // 密码正确，设置会话
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['shop_name'] = $user['shop_name'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // 重定向到仪表盘
                header('Location: dashboard.php');
                exit;
            } else {
                $error = '密码不正确';
            }
        } else {
            $error = '用户名不存在或账号已被停用';
        }
        
        // 关闭数据库连接
        closeDbConnection($conn);
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h4>登录系统</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">登录</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">还没有账号？<a href="register.php">注册新用户</a></p>
            </div>
        </div>
    </div>
</div>

<?php
// 包含底部
include_once 'includes/footer.php';
?>