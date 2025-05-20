# 小票打印系统阿里云部署详细指南

本文档提供了将小票打印系统部署到阿里云服务器的详细步骤，包括环境配置、文件上传、数据库设置和系统访问等内容。

## 一、准备工作

### 1. 阿里云ECS服务器

1. **购买阿里云ECS服务器**
   - 登录阿里云官网：https://www.aliyun.com
   - 选择「云服务器ECS」产品
   - 推荐配置：
     - 地域：华北2（北京）
     - 实例规格：至少2核4GB内存
     - 镜像：Ubuntu 20.04 64位
     - 存储：至少40GB系统盘
     - 带宽：按需选择（建议至少1Mbps固定带宽）

2. **设置安全组**
   - 在ECS控制台中选择「网络与安全」→「安全组」
   - 创建安全组，开放以下端口：
     - 22端口（SSH）
     - 80端口（HTTP）
     - 443端口（HTTPS，如需配置SSL证书）
     - 3306端口（MySQL，建议仅对特定IP开放）

3. **获取服务器信息**
   - 记录服务器公网IP地址
   - 记录root用户密码（或已创建的其他管理员账号）

### 2. 域名与解析（可选）

1. **购买域名**
   - 在阿里云或其他域名注册商购买域名

2. **域名解析设置**
   - 登录域名控制台
   - 添加解析记录：
     - 记录类型：A
     - 主机记录：www（或其他前缀，如dc）
     - 记录值：填写ECS服务器的公网IP地址
     - TTL：默认值（10分钟）

## 二、服务器环境配置

### 1. 连接服务器

```bash
# Windows用户可使用PuTTY或其他SSH客户端
# Linux/Mac用户可直接使用终端
ssh root@your_server_ip
```

### 2. 更新系统包

```bash
# 更新软件包列表
apt update

# 升级已安装的软件包
apt upgrade -y
```

### 3. 安装LAMP环境

```bash
# 安装Apache2
apt install apache2 -y

# 启动Apache并设置开机自启
systemctl start apache2
systemctl enable apache2

# 检查Apache状态
systemctl status apache2
```

```bash
# 安装MySQL
apt install mysql-server -y

# 启动MySQL并设置开机自启
systemctl start mysql
systemctl enable mysql

# 检查MySQL状态
systemctl status mysql
```

```bash
# 安装PHP及相关扩展
apt install php libapache2-mod-php php-mysql php-gd php-mbstring php-xml php-curl -y

# 检查PHP版本
php -v
```

### 4. 配置MySQL安全设置

```bash
# 运行MySQL安全配置脚本
mysql_secure_installation
```

按照提示进行以下设置：
- 设置root密码（记住此密码！）
- 删除匿名用户
- 禁止root远程登录
- 删除测试数据库
- 重新加载权限表

### 5. 创建数据库和用户

```bash
# 登录MySQL
mysql -u root -p
```

在MySQL命令行中执行：

```sql
# 创建数据库
CREATE DATABASE web CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# 创建用户并授权
CREATE USER 'webuser'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON web.* TO 'webuser'@'localhost';
FLUSH PRIVILEGES;

# 退出MySQL
EXIT;
```

## 三、项目部署

### 1. 安装Git和Zip工具

```bash
apt install git unzip -y
```

### 2. 配置Web目录

```bash
# 清空默认网站目录
rm -rf /var/www/html/*

# 设置目录权限
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
```

### 3. 上传项目文件

**方法一：使用SFTP工具（推荐）**

1. 在本地计算机上安装FileZilla或WinSCP等SFTP客户端
2. 使用服务器IP、用户名和密码连接服务器
3. 将本地项目文件上传到服务器的`/var/www/html/`目录

**方法二：使用Git克隆（如果项目在Git仓库中）**

```bash
cd /var/www/html
git clone your_repository_url .
```

**方法三：使用scp命令（适用于Linux/Mac用户）**

```bash
# 在本地计算机执行
scp -r /path/to/local/web3.7/* root@your_server_ip:/var/www/html/
```

### 4. 配置项目文件

```bash
# 设置目录权限
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/assets/img  # 如果需要上传图片
```

### 5. 导入数据库

```bash
# 导入数据库结构
mysql -u root -p web < /var/www/html/database.sql
```

### 6. 修改数据库配置

```bash
# 编辑数据库配置文件
nano /var/www/html/config/db_config.php
```

修改为：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'webuser');  // 使用前面创建的MySQL用户
define('DB_PASS', 'your_strong_password');  // 使用前面设置的密码
define('DB_NAME', 'web');
```

### 7. 修改管理员密码

如果遇到管理员密码问题，可以通过以下方式解决：

```bash
# 登录MySQL
mysql -u root -p
```

在MySQL命令行中执行：

```sql
# 使用web数据库
USE web;

# 更新管理员密码为"admin123"的哈希值
UPDATE users SET password = '$2y$10$YYxLK5nY.dYfXjDZDDUdnOUb0WeFLKRZMjrKzZOPYvZxGf/4cald.' WHERE username = 'admin';

# 退出MySQL
EXIT;
```

## 四、配置Apache虚拟主机

### 1. 创建虚拟主机配置文件

```bash
nano /etc/apache2/sites-available/receipt.conf
```

添加以下内容（请替换domain.com为您的实际域名，例如dc.jinduo.xyz）：

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName dc.jinduo.xyz
    ServerAlias www.dc.jinduo.xyz
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

如果不使用域名，只使用IP访问，可以简化为：

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### 2. 启用虚拟主机和重写模块

```bash
# 启用重写模块
a2enmod rewrite

# 启用站点配置
a2ensite receipt.conf

# 禁用默认站点
a2dissite 000-default.conf

# 检查配置是否有语法错误
apache2ctl configtest

# 重启Apache
systemctl restart apache2
```

## 五、配置SSL证书（可选）

### 1. 安装Certbot

```bash
apt install certbot python3-certbot-apache -y
```

### 2. 获取SSL证书

```bash
certbot --apache -d domain.com -d www.domain.com
```

按照提示完成配置，Certbot会自动修改Apache配置文件并启用HTTPS。

## 六、系统访问与测试

### 1. 访问系统

- 如果配置了域名：在浏览器中访问 `http://domain.com` 或 `https://domain.com`（如果配置了SSL）
- 如果只使用IP：在浏览器中访问 `http://your_server_ip`

### 2. 登录系统

使用管理员账号登录：
- 用户名：`admin`
- 密码：`admin123`

### 3. 测试功能

- 测试用户注册
- 测试小票打印
- 测试菜品管理
- 测试管理员功能

## 七、系统维护

### 1. 日志查看

```bash
# 查看Apache错误日志
tail -f /var/log/apache2/error.log

# 查看Apache访问日志
tail -f /var/log/apache2/access.log
```

### 2. 数据库备份

```bash
# 创建备份目录
mkdir -p /var/backups/database

# 备份数据库
mysqldump -u root -p web > /var/backups/database/web_$(date +%Y%m%d).sql
```

可以创建定时任务，每天自动备份：

```bash
nano /etc/cron.daily/backup-database
```

添加以下内容：

```bash
#!/bin/bash
mysqldump -u root -p'your_mysql_root_password' web > /var/backups/database/web_$(date +%Y%m%d).sql
find /var/backups/database -type f -name "*.sql" -mtime +7 -delete
```

设置执行权限：

```bash
chmod +x /etc/cron.daily/backup-database
```

### 3. 系统更新

定期更新系统和软件包：

```bash
apt update
apt upgrade -y
```

## 八、故障排除

### 1. 域名无法访问问题

如果通过域名（如dc.jinduo.xyz）无法访问网站，显示"无法访问此网站"，请按照以下步骤排查：

1. **检查域名解析**
   ```bash
   # 检查域名是否正确解析到服务器IP
   nslookup dc.jinduo.xyz
   ```
   确保结果显示的IP地址与您的服务器IP一致。如不一致，请在域名控制台更新解析记录。

2. **检查Apache虚拟主机配置**
   ```bash
   # 查看虚拟主机配置
   cat /etc/apache2/sites-available/receipt.conf
   ```
   确保ServerName和ServerAlias正确设置为您的域名（dc.jinduo.xyz）。

3. **检查Apache是否正在运行**
   ```bash
   systemctl status apache2
   ```
   如果Apache未运行，启动它：
   ```bash
   systemctl start apache2
   ```

4. **检查防火墙设置**
   ```bash
   # 确保80端口开放
   ufw status
   ```
   如果80端口未开放，请开放它：
   ```bash
   ufw allow 80/tcp
   ```

5. **检查阿里云安全组设置**
   在阿里云控制台中，确保安全组已开放80端口（HTTP）和443端口（HTTPS）。

6. **检查Apache错误日志**
   ```bash
   tail -n 50 /var/log/apache2/error.log
   ```
   查看是否有相关错误信息。

7. **测试使用IP直接访问**
   在浏览器中直接使用服务器IP地址访问，如果可以访问但域名不行，则确认是域名配置问题。

### 2. 管理员密码问题

如果使用正确的管理员密码仍然无法登录，可能是密码哈希值不匹配。请执行以下步骤：

1. 登录MySQL并更新密码：

```bash
mysql -u root -p
```

```sql
USE web;
UPDATE users SET password = '$2y$10$YYxLK5nY.dYfXjDZDDUdnOUb0WeFLKRZMjrKzZOPYvZxGf/4cald.' WHERE username = 'admin';
EXIT;
```

2. 确认密码为 `admin123`

### 3. 权限问题

如果遇到文件权限问题，请执行：

```bash
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
```

### 4. 数据库连接错误

检查 `db_config.php` 文件中的连接信息是否正确：

```bash
cat /var/www/html/config/db_config.php
```

确保用户名、密码、数据库名称正确。

### 5. Apache配置错误

检查Apache配置：

```bash
apache2ctl configtest
```

如有错误，根据提示修复后重启Apache：

```bash
systemctl restart apache2
```

## 九、安全建议

1. **定期更新系统**：保持系统和软件包最新
2. **使用强密码**：为所有账户设置强密码
3. **限制SSH访问**：配置SSH密钥认证，禁用密码登录
4. **配置防火墙**：使用UFW或iptables限制端口访问
5. **启用HTTPS**：使用SSL证书加密通信
6. **定期备份**：定期备份数据库和重要文件
7. **监控日志**：定期检查系统和应用日志

## 十、联系与支持

如有任何问题或需要技术支持，请联系系统管理员。