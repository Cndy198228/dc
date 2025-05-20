<?php
/**
 * 小票打印页面
 * 
 * 用户可以在此页面添加菜品、预览和打印小票
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
$shop_name = $_SESSION['shop_name'];

// 包含数据库配置
require_once 'config/db_config.php';
$conn = getDbConnection();

// 获取用户详细信息
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 获取用户菜品
$dishes = [];
$stmt = $conn->prepare("SELECT * FROM dishes WHERE user_id = ? ORDER BY name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dishes[] = $row;
}

closeDbConnection($conn);
?>

<!-- 小票打印页面 -->
<div class="row">
    <div class="col-md-8">
        <!-- 小票预览 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">小票预览</h5>
            </div>
            <div class="card-body">
                <div class="receipt-preview p-3 border">
                    <!-- 店铺名 -->
                    <h4 class="text-center fw-bold"><?php echo htmlspecialchars($user['shop_name']); ?></h4>
                    
                    <!-- 税号和电话 -->
                    <p class="text-center mb-1">NIF: <?php echo htmlspecialchars($user['tax_id']); ?> Tel.: <?php echo htmlspecialchars($user['phone']); ?></p>
                    
                    <!-- 地址 -->
                    <p class="text-center mb-1"><?php echo htmlspecialchars($user['address']); ?></p>
                    
                    <!-- FACTURA SIMPLIFICADA -->
                    <h5 class="text-center fw-bold mb-3">FACTURA SIMPLIFICADA</h5>
                    
                    <!-- 桌号 -->
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-6">
                                <span>Num.: <span id="receipt-num">0001</span></span>
                            </div>
                            <div class="col-6 text-end">
                                <span>Taula: <span id="receipt-table">1</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 菜品列表 -->
                    <div class="mb-3">
                        <div class="row fw-bold border-bottom pb-1">
                            <div class="col-1 text-end">Q.</div>
                            <div class="col-1 text-center">X</div>
                            <div class="col-3">Preu</div>
                            <div class="col-4">Descripció</div>
                            <div class="col-3 text-end">Suma</div>
                        </div>
                        <div id="receipt-items">
                            <!-- 菜品项目将通过JavaScript动态添加 -->
                        </div>
                    </div>
                    
                    <!-- 总计金额 -->
                    <div class="mb-3">
                        <h5 class="text-end fw-bold">Total: <span id="receipt-total">0.00</span></h5>
                    </div>
                    
                    <!-- 税金信息 -->
                    <div class="row mb-3">
                        <div class="col-4 text-center">BASE</div>
                        <div class="col-4 text-center">CUOTA</div>
                        <div class="col-4 text-center">IVA</div>
                        <div class="col-4 text-center" id="receipt-base">0.00</div>
                        <div class="col-4 text-center" id="receipt-tax-amount">0.00</div>
                        <div class="col-4 text-center"><?php echo $user['tax_rate']; ?>%</div>
                    </div>
                    
                    <!-- 日期和时间 -->
                    <div class="text-center mb-3">
                        <span id="receipt-date">15-05-2023 15:27:45</span>
                    </div>
                    
                    <!-- IVA INCLÒS -->
                    <h5 class="text-center fw-bold mb-3">IVA INCLÒS</h5>
                    
                    <!-- 感谢语 -->
                    <h5 class="text-center fw-bold">GRACIES PER LA SEVA VISITA</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 菜品输入 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">添加菜品</h5>
            </div>
            <div class="card-body">
                <form id="add-item-form">
                    <div class="mb-3">
                        <label for="table-number" class="form-label">桌号</label>
                        <input type="text" class="form-control" id="table-number" value="1">
                    </div>
                    <div class="mb-3">
                        <label for="dish-name" class="form-label">菜品</label>
                        <input type="text" class="form-control" id="dish-name" list="dish-list" autocomplete="off">
                        <datalist id="dish-list">
                            <?php foreach ($dishes as $dish): ?>
                                <option value="<?php echo htmlspecialchars($dish['name']); ?>" data-price="<?php echo $dish['price']; ?>"><?php echo htmlspecialchars($dish['name']); ?> - €<?php echo number_format($dish['price'], 2); ?></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label for="dish-price" class="form-label">单价</label>
                        <input type="number" class="form-control" id="dish-price" step="0.01" min="0">
                    </div>
                    <div class="mb-3">
                        <label for="dish-quantity" class="form-label">数量</label>
                        <input type="number" class="form-control" id="dish-quantity" value="1" min="1">
                    </div>
                    <div class="d-grid">
                        <button type="button" id="add-item-btn" class="btn btn-success">添加</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 功能按钮 -->
        <div class="card">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" id="print-btn" class="btn btn-success btn-lg">
                        <i class="fas fa-print me-2"></i> 打印
                    </button>
                    <a href="dishes_manage.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-utensils me-2"></i> 录入菜品
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 菜品管理模态框 -->
<div class="modal fade" id="dishesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">菜品管理</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="dishesTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">手动录入</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="excel-tab" data-bs-toggle="tab" data-bs-target="#excel" type="button" role="tab">Excel导入</button>
                    </li>
                </ul>
                <div class="tab-content" id="dishesTabContent">
                    <div class="tab-pane fade show active" id="manual" role="tabpanel">
                        <form id="add-dish-form">
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="new-dish-name" class="form-label">菜品名称</label>
                                    <input type="text" class="form-control" id="new-dish-name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="new-dish-price" class="form-label">单价</label>
                                    <input type="number" class="form-control" id="new-dish-price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="button" id="save-dish-btn" class="btn btn-primary">保存</button>
                            </div>
                        </form>
                        <hr>
                        <div class="table-responsive mt-3">
                            <table class="table table-striped" id="dishes-table">
                                <thead>
                                    <tr>
                                        <th>菜品名称</th>
                                        <th>单价</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dishes as $dish): ?>
                                        <tr data-id="<?php echo $dish['id']; ?>">
                                            <td><?php echo htmlspecialchars($dish['name']); ?></td>
                                            <td><?php echo number_format($dish['price'], 2); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger delete-dish-btn">删除</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="excel" role="tabpanel">
                        <form id="import-excel-form">
                            <div class="mb-3">
                                <label for="excel-file" class="form-label">选择Excel文件</label>
                                <input type="file" class="form-control" id="excel-file" accept=".xlsx, .xls">
                                <div class="form-text">Excel文件格式：第一列为菜品名称，第二列为单价</div>
                            </div>
                            <div class="d-grid">
                                <button type="button" id="import-excel-btn" class="btn btn-primary">导入</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 打印机连接模态框 -->
<div class="modal fade" id="printerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">连接打印机</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="printer-status" class="alert alert-info">请点击下方按钮连接打印机</div>
                <div class="d-grid">
                    <button type="button" id="connect-printer-btn" class="btn btn-primary">连接打印机</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入打印脚本 -->
<script src="/assets/js/print.js"></script>

<?php
// 包含底部
include_once 'includes/footer.php';
?>