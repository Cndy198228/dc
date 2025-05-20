<?php
/**
 * 管理员页面
 * 
 * 管理员可以在此页面管理用户账号
 */

// 包含头部
include_once 'includes/header.php';

// 检查是否已登录且是管理员
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php');
    exit;
}

// 包含数据库配置
require_once 'config/db_config.php';
$conn = getDbConnection();

// 获取所有用户（除了当前管理员）
$current_user_id = $_SESSION['user_id'];
$users = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE id != ? ORDER BY username");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

closeDbConnection($conn);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">用户管理</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>店铺名</th>
                                <th>电话</th>
                                <th>税号</th>
                                <th>税率</th>
                                <th>状态</th>
                                <th>注册时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-id="<?php echo $user['id']; ?>">
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['shop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($user['tax_id']); ?></td>
                                    <td><?php echo $user['tax_rate']; ?>%</td>
                                    <td>
                                        <?php if ($user['is_active'] == 1): ?>
                                            <span class="badge bg-success">启用</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">停用</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary edit-user-btn" data-bs-toggle="modal" data-bs-target="#editUserModal" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($user['is_active'] == 1): ?>
                                                <button type="button" class="btn btn-warning toggle-status-btn" data-action="deactivate" data-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-success toggle-status-btn" data-action="activate" data-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-danger delete-user-btn" data-id="<?php echo $user['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 编辑用户模态框 -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">编辑用户</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-user-form">
                    <input type="hidden" id="edit-user-id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit-shop-name" class="form-label">店铺名称</label>
                            <input type="text" class="form-control" id="edit-shop-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-username" class="form-label">用户名</label>
                            <input type="text" class="form-control" id="edit-username" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-address" class="form-label">地址</label>
                        <input type="text" class="form-control" id="edit-address" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit-phone" class="form-label">电话</label>
                            <input type="text" class="form-control" id="edit-phone" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-tax-id" class="form-label">税号</label>
                            <input type="text" class="form-control" id="edit-tax-id" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit-tax-rate" class="form-label">税率 (%)</label>
                        <input type="number" class="form-control" id="edit-tax-rate" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-password" class="form-label">新密码 (留空表示不修改)</label>
                        <input type="password" class="form-control" id="edit-password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="save-user-btn">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- 确认删除模态框 -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">确认删除</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>您确定要删除此用户吗？此操作不可逆，用户的所有数据将被永久删除。</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">删除</button>
            </div>
        </div>
    </div>
</div>

<!-- 管理员页面脚本 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 编辑用户
        const editUserBtns = document.querySelectorAll('.edit-user-btn');
        editUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                // 在实际应用中，这里应该发送AJAX请求获取用户详细信息
                // 这里简化为从表格行中获取数据
                const row = document.querySelector(`tr[data-id="${userId}"]`);
                document.getElementById('edit-user-id').value = userId;
                document.getElementById('edit-username').value = row.cells[1].textContent;
                document.getElementById('edit-shop-name').value = row.cells[2].textContent;
                document.getElementById('edit-phone').value = row.cells[3].textContent;
                document.getElementById('edit-tax-id').value = row.cells[4].textContent;
                document.getElementById('edit-tax-rate').value = parseFloat(row.cells[5].textContent);
                // 地址需要通过AJAX获取，这里简化处理
                document.getElementById('edit-address').value = '需要通过AJAX获取完整地址';
            });
        });
        
        // 保存用户编辑
        document.getElementById('save-user-btn').addEventListener('click', function() {
            // 在实际应用中，这里应该发送AJAX请求保存用户信息
            alert('保存成功！在实际应用中，这里会发送AJAX请求保存数据。');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
        });
        
        // 切换用户状态（启用/停用）
        const toggleStatusBtns = document.querySelectorAll('.toggle-status-btn');
        toggleStatusBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                // 在实际应用中，这里应该发送AJAX请求切换用户状态
                alert(`用户状态已${action === 'activate' ? '启用' : '停用'}！在实际应用中，这里会发送AJAX请求更新状态。`);
                // 刷新页面以显示更新后的状态
                location.reload();
            });
        });
        
        // 删除用户
        const deleteUserBtns = document.querySelectorAll('.delete-user-btn');
        let userIdToDelete = null;
        
        deleteUserBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                userIdToDelete = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                modal.show();
            });
        });
        
        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            if (userIdToDelete) {
                // 在实际应用中，这里应该发送AJAX请求删除用户
                alert('用户已删除！在实际应用中，这里会发送AJAX请求删除用户。');
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal'));
                modal.hide();
                // 刷新页面以显示更新后的用户列表
                location.reload();
            }
        });
    });
</script>

<?php
// 包含底部
include_once 'includes/footer.php';
?>