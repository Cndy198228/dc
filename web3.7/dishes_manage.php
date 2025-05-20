<?php
require_once 'config/db_config.php';

// 检查用户是否已登录
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 获取菜品列表
function getDishes() {
    global $user_id; // 确保使用当前登录用户的ID
    $conn = getDbConnection();
    $sql = "SELECT * FROM dishes WHERE user_id = ? ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dishes = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dishes[] = $row;
        }
    }
    
    $conn->close();
    return $dishes;
}

$dishes = getDishes();

// 获取用户信息
$user_id = $_SESSION['user_id'];
$conn = getDbConnection();
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>菜品管理 - 小票打印系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">小票打印系统</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> 首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dishes_manage.php"><i class="fas fa-utensils me-1"></i> 菜品管理</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> 退出登录</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-utensils me-2"></i> 菜品管理</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-3" id="dishesTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">手动录入</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="excel-tab" data-bs-toggle="tab" data-bs-target="#excel" type="button" role="tab">批量导入</button>
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
                                <div id="dish-save-result" class="mt-3"></div>
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
                                <!-- 分页导航 -->
                                <div id="pagination-container" class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="pagination-info">显示 <span id="pagination-start">1</span>-<span id="pagination-end">15</span> 条，共 <span id="pagination-total">0</span> 条</div>
                                    <nav aria-label="菜品列表分页">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item disabled" id="pagination-prev">
                                                <a class="page-link" href="javascript:void(0)" tabindex="-1">上一页</a>
                                            </li>
                                            <li class="page-item disabled" id="pagination-next">
                                                <a class="page-link" href="javascript:void(0)">下一页</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="excel" role="tabpanel">
                                <form id="import-excel-form" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="excel-file" class="form-label">选择导入文件</label>
                                        <input type="file" class="form-control" id="excel-file" accept=".xlsx,.xls,.csv,.txt">
                                        <div class="form-text">支持.xlsx, .xls, .csv, .txt格式，文件大小不超过2MB</div>
                                        <div class="form-text">TXT文件格式：每行一个菜品，菜品名和价格用制表符、逗号、分号或空格分隔</div>
                                        <div class="form-text">例如：宫保鸡丁 38 或 鱼香肉丝,32 或 水煮牛肉	45</div>
                                    </div>
                                    <div class="d-grid">
                                          <button type="button" id="import-excel-btn" class="btn btn-primary">导入文件</button>
                                      </div>
                                </form>
                                <div id="excel-import-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> 返回首页
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // 当前页码和每页显示数量
        let currentPage = 1;
        const itemsPerPage = 15;
        
        // 加载菜品列表函数
        function loadDishes(page = 1) {
            currentPage = page;
            
            $.ajax({
                url: 'api/dishes.php',
                type: 'POST',
                data: {
                    action: 'list',
                    page: page,
                    limit: itemsPerPage
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 清空表格
                        $('#dishes-table tbody').empty();
                        
                        // 添加菜品到表格
                        if (response.dishes.length > 0) {
                            $.each(response.dishes, function(index, dish) {
                                const row = $('<tr data-id="' + dish.id + '">').append(
                                    $('<td>').text(dish.name),
                                    $('<td>').text(parseFloat(dish.price).toFixed(2)),
                                    $('<td>').append(
                                        $('<button type="button" class="btn btn-sm btn-danger delete-dish-btn">').text('删除')
                                    )
                                );
                                $('#dishes-table tbody').append(row);
                            });
                            
                            // 更新分页信息
                            updatePagination(response.pagination);
                        } else {
                            // 没有菜品时显示提示
                            $('#dishes-table tbody').append('<tr><td colspan="3" class="text-center">暂无菜品</td></tr>');
                            // 隐藏分页
                            $('#pagination-container').hide();
                        }
                    } else {
                        alert('加载菜品列表失败：' + (response.message || '未知错误'));
                    }
                },
                error: function() {
                    alert('加载菜品列表失败，请检查网络连接');
                }
            });
        }
        
        // 更新分页控件
        function updatePagination(pagination) {
            const total = pagination.total;
            const page = pagination.page;
            const limit = pagination.limit;
            const pages = pagination.pages;
            
            // 计算当前页显示的记录范围
            const start = (page - 1) * limit + 1;
            const end = Math.min(page * limit, total);
            
            // 更新分页信息
            $('#pagination-total').text(total);
            $('#pagination-start').text(start);
            $('#pagination-end').text(end);
            
            // 更新上一页按钮状态
            if (page <= 1) {
                $('#pagination-prev').addClass('disabled');
            } else {
                $('#pagination-prev').removeClass('disabled');
            }
            
            // 更新下一页按钮状态
            if (page >= pages) {
                $('#pagination-next').addClass('disabled');
            } else {
                $('#pagination-next').removeClass('disabled');
            }
            
            // 显示分页控件
            if (total > 0) {
                $('#pagination-container').show();
            } else {
                $('#pagination-container').hide();
            }
        }
        
        $(document).ready(function() {
            // 页面加载时加载菜品列表
            loadDishes();
            
            // 保存菜品按钮点击事件
            $('#save-dish-btn').on('click', function() {
                saveDishAndRefresh();
            });
            
            // 导入Excel按钮点击事件
            $('#import-excel-btn').on('click', function() {
                importExcelAndRefresh();
            });
            
            // 使用事件委托绑定删除按钮事件
            $('#dishes-table').on('click', '.delete-dish-btn', function() {
                const dishId = $(this).closest('tr').data('id');
                deleteDish(dishId);
            });
            
            // 上一页按钮点击事件
            $('#pagination-prev').on('click', function() {
                if (!$(this).hasClass('disabled')) {
                    loadDishes(currentPage - 1);
                }
            });
            
            // 下一页按钮点击事件
            $('#pagination-next').on('click', function() {
                if (!$(this).hasClass('disabled')) {
                    loadDishes(currentPage + 1);
                }
            });
            
            // 回车键提交表单
            $('#add-dish-form').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    saveDishAndRefresh();
                }
            });
        });
        
        // 保存菜品并刷新列表
        function saveDishAndRefresh() {
            const dishName = $('#new-dish-name').val().trim();
            const dishPrice = $('#new-dish-price').val().trim();
            
            if (!dishName) {
                alert('请输入菜品名称');
                return;
            }
            
            // 保存成功后应该显示第一页，因为新添加的菜品可能在任何页面
            
            if (!dishPrice || isNaN(parseFloat(dishPrice)) || parseFloat(dishPrice) <= 0) {
                alert('请输入有效的价格');
                return;
            }
            
            // 显示加载提示
            $('#save-dish-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 保存中...');
            
            // 发送请求保存菜品
            $.ajax({
                url: 'api/dishes.php',
                type: 'POST',
                data: {
                    action: 'add',
                    name: dishName,
                    price: dishPrice
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 清空输入框
                        $('#new-dish-name').val('');
                        $('#new-dish-price').val('');
                        
                        // 显示成功提示
                        $('#dish-save-result').html('<div class="alert alert-success">菜品添加成功</div>');
                        
                        // 刷新菜品列表（返回第一页）
                        loadDishes(1);
                        
                        // 3秒后隐藏提示
                        setTimeout(function() {
                            $('#dish-save-result').html('');
                        }, 3000);
                    } else {
                        $('#dish-save-result').html('<div class="alert alert-danger">' + (response.message || '保存失败，请重试') + '</div>');
                    }
                },
                error: function() {
                    $('#dish-save-result').html('<div class="alert alert-danger">网络错误，请重试</div>');
                },
                complete: function() {
                    // 恢复按钮状态
                    $('#save-dish-btn').prop('disabled', false).text('保存');
                }
            });
        }
        
        // 导入文件并刷新列表
        // 删除菜品函数
        function deleteDish(dishId) {
            if (!confirm('确定要删除这个菜品吗？')) {
                return;
            }
            
            // 显示加载提示
            const loadingAlert = $('<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">').text('正在删除菜品，请稍候...');
            loadingAlert.append('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
            $('#dishes-table').before(loadingAlert);
            
            // 发送AJAX请求
            $.ajax({
                url: 'api/dishes.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: dishId
                },
                dataType: 'json',
                success: function(response) {
                    // 移除加载提示
                    loadingAlert.alert('close');
                    
                    if (response.success) {
                        // 获取当前页的菜品数量
                        const currentItemsCount = $('#dishes-table tbody tr').length;
                        
                        // 如果当前页只有一个菜品且不是第一页，删除后应该加载前一页
                        if (currentItemsCount === 1 && currentPage > 1) {
                            loadDishes(currentPage - 1);
                        } else {
                            // 否则重新加载当前页
                            loadDishes(currentPage);
                        }
                        
                        // 显示成功提示
                        const successAlert = $('<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">').text(response.message);
                        successAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        $('#dishes-table').before(successAlert);
                        setTimeout(function() {
                            successAlert.alert('close');
                        }, 3000);
                    } else {
                        // 显示错误提示
                        const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('删除菜品失败：' + response.message);
                        errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        $('#dishes-table').before(errorAlert);
                        setTimeout(function() {
                            errorAlert.alert('close');
                        }, 3000);
                    }
                },
                error: function() {
                    // 移除加载提示
                    loadingAlert.alert('close');
                    
                    // 显示错误提示
                    const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('删除菜品失败，请检查网络连接');
                    errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    $('#dishes-table').before(errorAlert);
                    setTimeout(function() {
                        errorAlert.alert('close');
                    }, 3000);
                }
            });
        }
        
        function importExcelAndRefresh() {
            const fileInput = $('#excel-file')[0];
            
            if (!fileInput.files || fileInput.files.length === 0) {
                $('#excel-import-result').html('<div class="alert alert-danger">请选择要导入的文件</div>');
                return;
            }
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('action', 'import');
            formData.append('excel-file', file);
            
            // 显示加载提示
            $('#import-excel-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 导入中...');
            
            // 发送请求导入Excel
            $.ajax({
                url: 'api/dishes.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 清空文件输入
                        $('#excel-file').val('');
                        
                        // 显示成功提示
                        $('#excel-import-result').html('<div class="alert alert-success">成功导入 ' + response.count + ' 个菜品</div>');
                        
                        // 刷新菜品列表（返回第一页）并切换到手动录入标签页
                        loadDishes(1);
                        $('#manual-tab').tab('show');
                        
                        // 3秒后隐藏提示
                        setTimeout(function() {
                            $('#excel-import-result').html('');
                        }, 3000);
                    } else {
                        // 显示错误提示
                        $('#excel-import-result').html('<div class="alert alert-danger">' + (response.message || '导入失败，请确保文件格式正确且文件大小不超过2MB') + '</div>');
                        
                        // 5秒后隐藏提示
                        setTimeout(function() {
                            $('#excel-import-result').html('');
                        }, 5000);
                    }
                },
                error: function() {
                    // 显示网络错误提示
                    $('#excel-import-result').html('<div class="alert alert-danger">网络错误，请检查网络连接后重试</div>');
                    
                    // 5秒后隐藏提示
                    setTimeout(function() {
                        $('#excel-import-result').html('');
                    }, 5000);
                },
                complete: function() {
                    // 恢复按钮状态
                    $('#import-excel-btn').prop('disabled', false).text('导入');
                }
            });
        }
    </script>
</body>
</html>