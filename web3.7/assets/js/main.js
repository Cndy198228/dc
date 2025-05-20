/**
 * 小票打印系统主JavaScript文件
 */

$(document).ready(function() {
    // 全局变量
    let receiptItems = [];
    let tableNumber = 1;
    
    // 初始化页面
    initializePage();
    
    /**
     * 初始化页面
     */
    function initializePage() {
        // 设置桌号输入框的初始值
        $('#table-number').val(tableNumber);
        
        // 更新小票上的桌号
        $('#receipt-table').text(tableNumber);
        
        // 加载菜品列表（如果在dashboard页面）
        if ($('#dish-list').length > 0) {
            loadDishes();
        }
        
        // 绑定事件处理程序
        bindEventHandlers();
    }
    
    /**
     * 绑定事件处理程序
     */
    function bindEventHandlers() {
        // 桌号输入框变化事件
        $('#table-number').on('change', function() {
            tableNumber = $(this).val();
            $('#receipt-table').text(tableNumber);
        });
        
        // 添加菜品按钮点击事件
        $('#add-item-btn').on('click', function() {
            addDishToReceipt();
        });
        
        // 打印按钮点击事件
        $('#print-btn').on('click', function() {
            printReceipt();
        });
        
        // 菜品管理按钮点击事件
        $('#manage-dishes-btn').on('click', function() {
            // 打开模态框前重新加载菜品列表
            loadDishes();
            $('#dishesModal').modal('show');
        });
        
        // 保存菜品按钮点击事件
        $('#save-dish-btn').on('click', function() {
            saveDish();
        });
        
        // Excel导入按钮点击事件
        $('#import-excel-btn').on('click', function() {
            importExcel();
        });
        
        // 全局变量存储所有菜品数据
        let allDishes = [];
        
        // 加载所有菜品数据
        function loadAllDishes() {
            $.ajax({
                url: 'api/dishes.php',
                type: 'POST',
                data: {
                    action: 'search',
                    query: ''
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.dishes.length > 0) {
                        // 保存所有菜品数据
                        allDishes = response.dishes;
                        
                        // 清空并重新填充datalist
                        $('#dish-list').empty();
                        
                        // 添加所有菜品到datalist
                        allDishes.forEach(function(dish) {
                            $('#dish-list').append(
                                '<option value="' + dish.name + '" data-price="' + dish.price + '">' + dish.name + ' - €' + parseFloat(dish.price).toFixed(2) + '</option>'
                            );
                        });
                    }
                }
            });
        }
        
        // 页面加载时初始化datalist
        loadAllDishes();
        
        // 菜品名称输入框输入事件 - 自动补全功能
        $('#dish-name').on('input', function() {
            const input = $(this).val().trim();
            
            // 查找匹配的菜品
            if (input.length > 0) {
                // 在本地菜品数据中查找匹配项
                const matchedDish = allDishes.find(dish => dish.name.toLowerCase() === input.toLowerCase());
                
                if (matchedDish) {
                    // 找到匹配的菜品，更新单价
                    $('#dish-price').val(matchedDish.price);
                } else {
                    // 未找到精确匹配，尝试从服务器获取
                    $.ajax({
                        url: 'api/dishes.php',
                        type: 'POST',
                        data: {
                            action: 'search',
                            query: input
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.dishes.length > 0) {
                                // 找到匹配的菜品，更新单价
                                $('#dish-price').val(response.dishes[0].price);
                                
                                // 更新本地菜品数据
                                response.dishes.forEach(function(dish) {
                                    if (!allDishes.some(d => d.id === dish.id)) {
                                        allDishes.push(dish);
                                    }
                                });
                                
                                // 更新datalist
                                $('#dish-list').empty();
                                allDishes.forEach(function(dish) {
                                    $('#dish-list').append(
                                        '<option value="' + dish.name + '" data-price="' + dish.price + '">' + dish.name + ' - €' + parseFloat(dish.price).toFixed(2) + '</option>'
                                    );
                                });
                            } else {
                                // 未找到匹配的菜品，单价设为0
                                $('#dish-price').val(0);
                            }
                        }
                    });
                }
            } else {
                // 输入为空，单价设为0
                $('#dish-price').val(0);
            }
        });
        
        // 菜品名称输入框变化事件 - 当选择datalist中的选项时
        $('#dish-name').on('change', function() {
            const input = $(this).val().trim();
            
            // 在本地菜品数据中查找匹配项
            const matchedDish = allDishes.find(dish => dish.name === input);
            
            if (matchedDish) {
                // 找到匹配的菜品，更新单价
                $('#dish-price').val(matchedDish.price);
            } else {
                // 未找到匹配的菜品，单价设为0
                $('#dish-price').val(0);
            }
        });
        
        // 保存菜品后重新加载菜品列表
        $(document).on('dishSaved', function() {
            loadAllDishes();
        });
        
        // 添加菜品表单提交事件
        $('#add-dish-form').on('submit', function(e) {
            e.preventDefault();
            saveDish();
        });
        
        // 保存菜品按钮点击事件
        $('#save-dish-btn').on('click', function() {
            saveDish();
        });
        
        // 导入Excel表单提交事件
        $('#import-excel-form').on('submit', function(e) {
            e.preventDefault();
            importExcel();
        });
        
        // 导入Excel按钮点击事件
        $('#import-excel-btn').on('click', function() {
            importExcel();
        });
        
        // 管理员页面：编辑用户按钮点击事件
        $('.edit-user-btn').on('click', function() {
            const userId = $(this).data('id');
            loadUserData(userId);
        });
        
        // 管理员页面：切换用户状态按钮点击事件
        $('.toggle-status-btn').on('click', function() {
            const userId = $(this).data('id');
            const action = $(this).data('action');
            toggleUserStatus(userId, action);
        });
        
        // 管理员页面：删除用户按钮点击事件
        $('.delete-user-btn').on('click', function() {
            const userId = $(this).data('id');
            confirmDeleteUser(userId);
        });
    }
    
    /**
     * 加载菜品列表
     */
    function loadDishes() {
        $.ajax({
            url: 'api/dishes.php',
            type: 'POST',
            data: {
                action: 'list'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 清空菜品列表
                    $('#dish-list').empty();
                    
                    // 添加菜品到列表
                    response.dishes.forEach(function(dish) {
                        $('#dish-list').append(
                            `<tr class="dish-item" data-id="${dish.id}" data-name="${dish.name}" data-price="${dish.price}">
                                <td>${dish.name}</td>
                                <td>${dish.price}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger delete-dish-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>`
                        );
                    });
                    
                    // 绑定删除菜品按钮点击事件
                    $('.delete-dish-btn').on('click', function() {
                        const dishId = $(this).closest('tr').data('id');
                        deleteDish(dishId);
                    });
                    
                    // 绑定菜品项点击事件（添加到小票）
                    $('.dish-item').on('click', function() {
                        const dishName = $(this).data('name');
                        const dishPrice = $(this).data('price');
                        
                        // 设置菜品输入框的值
                        $('#dish-name').val(dishName);
                        $('#dish-price').val(dishPrice);
                        $('#dish-quantity').val(1);
                        $('#dish-quantity').focus();
                    });
                } else {
                    alert('加载菜品列表失败：' + response.message);
                }
            },
            error: function() {
                alert('加载菜品列表失败，请检查网络连接');
            }
        });
    }
    
    /**
     * 添加菜品到小票
     */
    function addDishToReceipt() {
        const dishName = $('#dish-name').val().trim();
        const dishPrice = parseFloat($('#dish-price').val());
        const dishQuantity = parseInt($('#dish-quantity').val());
        
        // 验证输入
        if (!dishName || isNaN(dishPrice) || dishPrice <= 0 || isNaN(dishQuantity) || dishQuantity <= 0) {
            alert('请输入有效的菜品名称、价格和数量');
            return;
        }
        
        // 计算小计
        const subtotal = dishPrice * dishQuantity;
        
        // 添加到小票项目数组
        receiptItems.push({
            name: dishName,
            price: dishPrice,
            quantity: dishQuantity,
            subtotal: subtotal
        });
        
        // 更新小票预览
        updateReceiptPreview();
        
        // 清空输入框
        $('#dish-name').val('');
        $('#dish-price').val('');
        $('#dish-quantity').val('1');
        $('#dish-name').focus();
    }
    
    /**
     * 更新小票预览
     */
    function updateReceiptPreview() {
        // 清空小票项目
        $('#receipt-items').empty();
        
        // 计算总计
        let total = 0;
        
        // 添加菜品项目
        receiptItems.forEach(function(item, index) {
            $('#receipt-items').append(
                `<div class="row py-1">
                    <div class="col-1 text-end">${item.quantity}</div>
                    <div class="col-1 text-center">X</div>
                    <div class="col-3">€${item.price.toFixed(2)}</div>
                    <div class="col-4">${item.name}</div>
                    <div class="col-3 text-end">€${item.subtotal.toFixed(2)}</div>
                </div>`
            );
            
            total += item.subtotal;
        });
        
        // 更新总计
        $('#receipt-total').text('€' + total.toFixed(2));
        
        // 获取税率
        const taxRate = parseFloat($('#tax-rate').val()) || 10; // 默认税率10%
        
        // 计算税基和税额
        const taxBase = total / (1 + taxRate / 100);
        const taxAmount = total - taxBase;
        
        // 更新税金信息
        $('#receipt-tax-base').text('€' + taxBase.toFixed(2));
        $('#receipt-tax-rate').text(taxRate.toFixed(2) + '%');
        $('#receipt-tax-amount').text('€' + taxAmount.toFixed(2));
        
        // 更新不含税价格和税金显示
        $('#receipt-base').text('€' + taxBase.toFixed(2));
    }
    
    /**
     * 打印小票
     */
    function printReceipt() {
        // 检查是否有菜品
        if (receiptItems.length === 0) {
            alert('请先添加菜品到小票');
            return;
        }
        
        // 添加打印样式，只打印小票预览区域，并设置宽度为48mm
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                /* 修复后的完整样式 */
                @page {
                    size: 48mm auto;
                    margin: 0;
                }
                body * {
                    visibility: hidden;
                }
                .receipt-preview, .receipt-preview * {
                    visibility: visible;
                }
                .receipt-preview {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 48mm;
                    margin: 0 auto;
                    font-size: 10px;
                }
                .receipt-preview h4 {
                    font-size: 12px;
                }
                .receipt-preview h5 {
                    font-size: 11px;
                }
                .receipt-preview .row {
                    margin: 0;
                }
                .receipt-preview [class*="col-"] {
                    padding: 2px;
                }
                
                /* 移动设备打印优化 */
                @media (max-width: 767px) {
                    .receipt-preview {
                        width: 100% !important;
                        max-width: 48mm;
                        font-size: 9px !important;
                    }
                    .receipt-preview h4 {
                        font-size: 11px !important;
                    }
                    .receipt-preview h5 {
                        font-size: 10px !important;
                    }
                }
            }`;
        document.head.appendChild(style);
        
        // 弹出打印对话框
        window.print();
        
        // 移除打印样式
        document.head.removeChild(style);
        
        // 保存订单到数据库的逻辑可以在这里添加
        
        // 清空小票
        receiptItems = [];
        updateReceiptPreview();
    }
    
    /**
     * 保存菜品
     */
    function saveDish() {
        const dishName = $('#new-dish-name').val().trim();
        const dishPrice = $('#new-dish-price').val().trim();
        
        if (!dishName) {
            alert('请输入菜品名称');
            return;
        }
        
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
                    
                    // 触发自定义事件，通知菜品已保存
                    $(document).trigger('dishSaved');
                    
                    // 刷新菜品管理页面，显示已录入的菜品信息
                    $('#dishes-table tbody').empty();
                    loadDishes();
                } else {
                    alert(response.message || '保存失败，请重试');
                }
            },
            error: function() {
                alert('网络错误，请重试');
            },
            complete: function() {
                // 恢复按钮状态
                $('#save-dish-btn').prop('disabled', false).text('保存');
            }
        });
    }
    
    /**
     * 删除菜品
     */
    function deleteDish(dishId) {
        // 创建确认对话框
        const confirmDialog = $('<div class="modal fade" tabindex="-1">');
        confirmDialog.html(`
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">确认删除</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>确定要删除这个菜品吗？</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-danger confirm-delete">删除</button>
                    </div>
                </div>
            </div>
        `);
        
        // 添加到页面并显示
        $('body').append(confirmDialog);
        const modal = new bootstrap.Modal(confirmDialog[0]);
        modal.show();
        
        // 绑定确认删除事件
        confirmDialog.find('.confirm-delete').on('click', function() {
            // 关闭确认对话框
            modal.hide();
            
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
                        // 重新加载菜品列表
                        loadDishes();
                        
                        // 显示成功提示（不使用弹窗）
                        const successAlert = $('<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">').text(response.message);
                        successAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        $('#dishes-table').before(successAlert);
                        setTimeout(function() {
                            successAlert.alert('close');
                        }, 5000);
                    } else {
                        // 显示错误提示（不使用弹窗）
                        const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('删除菜品失败：' + response.message);
                        errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        $('#dishes-table').before(errorAlert);
                        setTimeout(function() {
                            errorAlert.alert('close');
                        }, 5000);
                    }
                },
                error: function() {
                    // 移除加载提示
                    loadingAlert.alert('close');
                    
                    // 显示错误提示（不使用弹窗）
                    const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('删除菜品失败，请检查网络连接');
                    errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    $('#dishes-table').before(errorAlert);
                    setTimeout(function() {
                        errorAlert.alert('close');
                    }, 5000);
                }
            });
            
            // 移除确认对话框
            confirmDialog.on('hidden.bs.modal', function() {
                confirmDialog.remove();
            });
        });
        
        // 对话框关闭时移除
        confirmDialog.on('hidden.bs.modal', function() {
            confirmDialog.remove();
        });
    }
    
    /**
     * 导入Excel
     */
    function importExcel() {
        // 检查是否选择了文件
        if ($('#excel-file')[0].files.length === 0) {
            // 显示错误提示（不使用弹窗）
            const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('请选择Excel文件');
            errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
            $('#import-excel-form').prepend(errorAlert);
            setTimeout(function() {
                errorAlert.alert('close');
            }, 3000);
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('excel-file', $('#excel-file')[0].files[0]);
        
        // 显示加载提示（不使用弹窗）
        const loadingAlert = $('<div class="alert alert-info alert-dismissible fade show mt-2" role="alert">').text('正在导入Excel文件，请稍候...');
        loadingAlert.append('<span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>');
        $('#import-excel-form').prepend(loadingAlert);
        
        // 禁用导入按钮
        $('#import-excel-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 导入中...');
        
        // 发送AJAX请求
        $.ajax({
            url: 'api/dishes.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                // 移除加载提示
                loadingAlert.alert('close');
                
                if (response.success) {
                    // 清空文件输入框
                    $('#excel-file').val('');
                    
                    // 重新加载菜品列表
                    loadDishes();
                    
                    // 显示成功提示（不使用弹窗）
                    const successAlert = $('<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">').text(response.message);
                    successAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    $('#import-excel-form').prepend(successAlert);
                    setTimeout(function() {
                        successAlert.alert('close');
                    }, 5000);
                    
                    // 切换到手动录入标签页，显示导入的菜品
                    $('#manual-tab').tab('show');
                } else {
                    // 显示错误提示（不使用弹窗）
                    const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('导入失败：' + response.message);
                    errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    $('#import-excel-form').prepend(errorAlert);
                    setTimeout(function() {
                        errorAlert.alert('close');
                    }, 5000);
                }
            },
            error: function(xhr, status, error) {
                // 移除加载提示
                loadingAlert.alert('close');
                
                // 显示错误提示（不使用弹窗）
                const errorAlert = $('<div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">').text('导入Excel失败，请确保文件格式正确且文件大小不超过2MB');
                errorAlert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                $('#import-excel-form').prepend(errorAlert);
                setTimeout(function() {
                    errorAlert.alert('close');
                }, 5000);
            },
            complete: function() {
                // 恢复按钮状态
                $('#import-excel-btn').prop('disabled', false).text('导入');
            }
        });
    }
    
    // 管理员页面函数
    
    /**
     * 加载用户数据
     */
    function loadUserData(userId) {
        // 发送AJAX请求获取用户数据
        $.ajax({
            url: 'api/users.php',
            type: 'POST',
            data: {
                action: 'get_user',
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.user) {
                    // 设置表单字段
                    $('#edit-user-id').val(userId);
                    $('#edit-shop-name').val(response.user.shop_name);
                    $('#edit-phone').val(response.user.phone);
                    $('#edit-tax-id').val(response.user.tax_id);
                    $('#edit-tax-rate').val(parseFloat(response.user.tax_rate));
                    
                    // 显示编辑用户模态框
                    $('#editUserModal').modal('show');
                } else {
                    alert('获取用户数据失败：' + (response.message || '未知错误'));
                }
            },
            error: function() {
                alert('获取用户数据失败，请检查网络连接');
            }
        });
    }
    
    /**
     * 切换用户状态
     */
    function toggleUserStatus(userId, action) {
        const confirmMessage = action === 'activate' ? '确定要启用这个用户吗？' : '确定要停用这个用户吗？';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // 发送AJAX请求
        $.ajax({
            url: 'api/users.php',
            type: 'POST',
            data: {
                action: 'toggle_status',
                user_id: userId,
                status_action: action
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('操作失败：' + response.message);
                }
            },
            error: function() {
                alert('操作失败，请检查网络连接');
            }
        });
    }
    
    /**
     * 确认删除用户
     */
    function confirmDeleteUser(userId) {
        if (!confirm('确定要删除这个用户吗？此操作不可撤销！')) {
            return;
        }
        
        // 发送AJAX请求
        $.ajax({
            url: 'api/users.php',
            type: 'POST',
            data: {
                action: 'delete',
                user_id: userId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('删除失败：' + response.message);
                }
            },
            error: function() {
                alert('删除失败，请检查网络连接');
            }
        });
    }
});