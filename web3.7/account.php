<?php
/**
 * 账号信息页面
 * 
 * 用户可以在此页面查看和编辑自己的账号信息
 */

// 包含头部
include_once 'includes/header.php';

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 获取用户信息
$user_id = $_SESSION['user_id'];

// 包含数据库配置
require_once 'config/db_config.php';
$conn = getDbConnection();

// 初始化消息变量
$success = '';
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $shop_name = trim($_POST['shop_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $tax_id = trim($_POST['tax_id']);
    $tax_rate = floatval($_POST['tax_rate']);
    
    // 验证数据
    if (empty($shop_name) || empty($address) || empty($phone) || empty($tax_id) || $tax_rate <= 0) {
        $error = '所有字段都是必填的，税率必须大于0';
    } else {
        // 更新用户信息
        $stmt = $conn->prepare("UPDATE users SET shop_name = ?, address = ?, phone = ?, tax_id = ?, tax_rate = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $shop_name, $address, $phone, $tax_id, $tax_rate, $user_id);
        
        if ($stmt->execute()) {
            // 更新会话中的店铺名称
            $_SESSION['shop_name'] = $shop_name;
            $success = '账号信息已成功更新';
            
            // 重新获取用户信息以显示最新数据
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error = '更新失败: ' . $stmt->error;
        }
    }
} else {
    // 获取用户详细信息
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

closeDbConnection($conn);
?>

<!-- 账号信息页面 -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">账号信息</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <div class="form-text">用户名不可修改</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shop_name" class="form-label">店铺名称</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" value="<?php echo htmlspecialchars($user['shop_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">地址</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">电话</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tax_id" class="form-label">税号</label>
                        <input type="text" class="form-control" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($user['tax_id']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tax_rate" class="form-label">税率 (%)</label>
                        <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo $user['tax_rate']; ?>" step="0.01" min="0" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">保存修改</button>
                        <a href="dashboard.php" class="btn btn-secondary">返回仪表盘</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>