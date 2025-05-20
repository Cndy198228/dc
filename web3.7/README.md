# 小票打印系统

这是一个基于Web的小票打印系统，支持用户登录、注册、打印小票和菜品管理等功能。系统采用响应式设计，可自动适应手机或电脑浏览器的屏幕尺寸。

## 功能特点

- 用户登录和注册
- 新用户信息注册（店铺名、地址、电话、税号、税率等）
- 小票打印页面，支持自动计算税金和总价
- 菜品管理（手动录入或Excel批量导入）
- 权限管理（管理员可对其他账号进行管理）
- 支持通过蓝牙或WiFi连接小票打印机

## 技术栈

- 前端：HTML, CSS, JavaScript, Bootstrap, jQuery
- 后端：PHP
- 数据库：MySQL

## 目录结构

```
web3.7/
├── index.php              # 登录页面
├── register.php           # 注册页面
├── dashboard.php          # 小票打印主页面
├── admin.php              # 管理员页面
├── logout.php             # 退出登录
├── config/
│   └── db_config.php      # 数据库配置
├── api/
│   ├── dishes.php         # 菜品管理API
│   ├── print.php          # 打印API
│   └── users.php          # 用户管理API
├── assets/
│   ├── css/               # 样式文件
│   │   └── style.css      # 自定义样式
│   ├── js/                # JavaScript文件
│   │   └── main.js        # 主脚本文件
│   └── img/               # 图片资源（如需添加）
├── includes/
│   ├── header.php         # 页面头部
│   └── footer.php         # 页面底部
└── database.sql           # 数据库初始化脚本
```

## 环境要求（软件版本）

### 必需软件

- **XAMPP 8.0.25** 或更高版本
  - 包含 Apache 2.4.54+
  - PHP 8.0.25+
  - MariaDB 10.4.27+ 或 MySQL 8.0+
  - phpMyAdmin 5.2.0+

### 浏览器要求

- Chrome 90+ (推荐)
- Firefox 88+
- Edge 90+
- Safari 14+

## 详细部署说明

### 1. 安装 XAMPP

1. 从官方网站下载 XAMPP 8.0.25 或更高版本：https://www.apachefriends.org/zh_cn/download.html
2. 运行安装程序，按照向导完成安装
   - 推荐安装路径：`C:\xampp`
   - 建议选择组件：Apache, MySQL, PHP, phpMyAdmin
3. 安装完成后，启动 XAMPP 控制面板
4. 点击 Apache 和 MySQL 旁边的 "Start" 按钮启动服务

### 2. 配置项目

#### 方法一：使用虚拟主机（推荐）

1. 确保项目文件位于 `C:\web3.7` 目录下
2. 编辑 Apache 配置文件：
   - 打开 `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
   - 添加以下内容：

   ```
   <VirtualHost *:80>
       DocumentRoot "C:/web3.7"
       ServerName web3.7.local
       <Directory "C:/web3.7">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. 编辑 hosts 文件：
   - 以管理员身份打开记事本
   - 打开 `C:\Windows\System32\drivers\etc\hosts`
   - 添加以下内容：`127.0.0.1 web3.7.local`
4. 重启 Apache 服务

#### 方法二：直接使用 htdocs 目录

1. 将项目文件复制到 XAMPP 的 htdocs 目录下：
   - 创建目录：`C:\xampp\htdocs\web3.7`
   - 将所有项目文件复制到该目录下

### 3. 创建和配置数据库

1. 打开浏览器，访问 `http://localhost/phpmyadmin`
2. 使用以下凭据登录：
   - 用户名：`root`
   - 密码：留空（默认情况下 XAMPP 的 MySQL root 用户没有密码）
3. 创建新数据库：
   - 点击左侧的 "New"
   - 数据库名称输入 `web`
   - 字符集选择 `utf8mb4_general_ci`
   - 点击 "Create"
4. 导入数据库结构：
   - 选择刚创建的 `web` 数据库
   - 点击顶部的 "Import" 选项卡
   - 点击 "Choose File" 按钮，选择项目中的 `database.sql` 文件
   - 点击页面底部的 "Go" 按钮

### 4. 配置数据库连接

1. 打开项目中的 `config/db_config.php` 文件
2. 修改数据库连接信息：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // 默认情况下密码为空，如果您设置了密码，请填写
define('DB_NAME', 'web');
```

### 5. 设置文件权限

确保 Web 服务器对项目目录有读写权限：

1. 右键点击 `C:\web3.7` 文件夹（或 `C:\xampp\htdocs\web3.7`）
2. 选择 "属性"
3. 切换到 "安全" 选项卡
4. 确保 SYSTEM 和当前用户有完全控制权限

### 6. 访问应用

- 如果使用虚拟主机：打开浏览器，访问 `http://web3.7.local`
- 如果使用 htdocs 目录：打开浏览器，访问 `http://localhost/web3.7`

### 7. 默认管理员账号

系统预设了一个管理员账号，可用于首次登录：

- 用户名：`admin`
- 密码：`admin123`

## 常见问题解决

### 数据库连接错误

如果遇到数据库连接错误，请检查：

1. MySQL 服务是否正在运行
2. `db_config.php` 中的连接信息是否正确
3. 数据库 `web` 是否已创建
4. 用户名和密码是否正确

### 页面无法访问

如果无法访问页面，请检查：

1. Apache 服务是否正在运行
2. 虚拟主机配置是否正确
3. hosts 文件是否正确配置
4. 项目文件是否位于正确的目录

### PHP 错误

如果遇到 PHP 错误，请检查：

1. PHP 版本是否符合要求（8.0+）
2. PHP 扩展是否已启用（mysqli, mbstring, gd）
   - 在 XAMPP 控制面板中点击 "Config" 按钮（PHP 行）
   - 打开 php.ini 文件
   - 确保以下行没有被注释（没有前导分号）：
     ```
     extension=mysqli
     extension=mbstring
     extension=gd
     ```

## 联系与支持

如有任何问题或需要技术支持，请联系系统管理员。