# 开发环境搭建指南

本文说明如何在 Ubuntu 上从零搭建本项目的本地开发环境，包括 PHP、MySQL、Redis、Nginx、Node.js、npm、项目初始化、前端构建、队列、计划任务和测试。

以下示例假设：

- 项目目录为 `/www/framework`。
- 使用 Bash 执行命令。
- 使用 `a.base.local`、`p.base.local` 和 `www.base.local` 作为本地域名。
- 本文面向开发环境，不代替生产部署和安全加固流程。

## 1. 环境要求

| 软件 | 项目要求 | 推荐版本 | 说明 |
| --- | --- | --- | --- |
| Ubuntu | 64 位 LTS | 26.04 LTS，24.04 LTS 也可 | 以下命令基于 Ubuntu/Debian 的 APT 包管理器 |
| PHP | `^8.4` | PHP 8.4 最新补丁版 | 项目使用 Laravel 13 |
| Composer | 2.x | 最新稳定版 | 安装 PHP 依赖 |
| MySQL | 8.0+ | MySQL 8.4 LTS | 开发和测试均使用 MySQL |
| Redis | 7.0+ | Redis 8.0 | 默认会话和缓存依赖 Redis，PHP 使用 `phpredis` 扩展 |
| Nginx | 1.24+ | 最新稳定版 | 通过 PHP-FPM 提供 Web 服务 |
| Node.js | `^20.19.0` 或 `>=22.12.0` | Node.js 24 LTS | 版本要求来自 Vite 8 |
| npm | 与 Node.js 匹配 | npm 11+ | 使用 `package-lock.json` 安装前端依赖 |
| Git | 2.x | 最新稳定版 | 获取项目源码 |

PHP 和 JavaScript 依赖的准确版本分别以 `composer.lock`、`package-lock.json` 为准。不要使用低于上述要求的 PHP 或 Node.js 版本。

## 2. 安装系统软件

### 2.1 安装基础工具

```bash
sudo apt update
sudo apt install -y ca-certificates curl git unzip software-properties-common
```

### 2.2 安装 PHP 8.4 和扩展

Ubuntu 官方源未提供所需版本时，可使用常用的 Ondřej PHP 源：

```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y \
  php8.4-cli php8.4-fpm php8.4-mysql php8.4-redis \
  php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip \
  php8.4-gd php8.4-intl php8.4-bcmath php8.4-opcache
sudo systemctl enable --now php8.4-fpm
```

检查 PHP 和关键扩展：

```bash
php -v
php -m | grep -E 'mbstring|openssl|pdo_mysql|redis|xml|curl|gd|intl|bcmath'
systemctl status php8.4-fpm --no-pager
```

### 2.3 安装 Composer

使用 Composer 官方安装程序：

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --quiet
rm composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

团队环境也可以按照 [Composer 官方安装文档](https://getcomposer.org/download/) 校验安装程序签名后再安装。

### 2.4 安装 MySQL

```bash
sudo apt install -y mysql-server
sudo systemctl enable --now mysql
mysql --version
```

建议执行交互式安全初始化，并按提示设置本机 MySQL 的安全策略：

```bash
sudo mysql_secure_installation
```

创建开发数据库和独立账号。先将示例密码替换为自己的密码：

```sql
sudo mysql
```

```sql
CREATE DATABASE `framework`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'framework'@'127.0.0.1'
  IDENTIFIED BY 'replace-with-a-strong-password';

GRANT ALL PRIVILEGES ON `framework`.*
  TO 'framework'@'127.0.0.1';

EXIT;
```

不要使用 root 作为应用数据库账号，也不要将示例密码用于真实环境。

### 2.5 安装 Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping
```

正常结果为 `PONG`。项目默认通过本机 `127.0.0.1:6379` 连接 Redis，并使用 PHP 的 `phpredis` 扩展，因此还必须确认 `php8.4-redis` 已安装。

### 2.6 安装 Node.js 和 npm

使用 NodeSource 安装 Node.js 24 LTS：

```bash
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

如果团队使用 nvm、Volta 等版本管理器，也可以安装同版本 Node.js，但应确保所有开发环境使用一致的 npm 锁文件。

## 3. 获取项目并安装依赖

如果机器上还没有项目源码：

```bash
sudo mkdir -p /www
sudo chown "$USER":"$USER" /www
cd /www
git clone <repository-url> framework
cd /www/framework
```

将 `<repository-url>` 替换为实际的 Git 仓库地址。如果项目已经存在，直接进入项目目录即可：

```bash
cd /www/framework
composer install
npm ci
cp .env.example .env
php artisan key:generate --no-interaction
```

如果 `.env` 已存在，不要覆盖，也不要重复生成 `APP_KEY`，否则已有加密数据可能无法解密。

`composer install` 和 `npm ci` 分别按照锁文件安装依赖。日常更新依赖后，应提交相应锁文件，不要在搭建环境时随意升级依赖。

## 4. 配置环境变量

编辑 `.env`，至少核对以下配置。数据库密码使用前面创建账号时设置的密码：

```dotenv
APP_NAME=Framework
APP_ENV=local
APP_DEBUG=true
APP_URL=http://www.base.local
APP_TIMEZONE=Asia/Shanghai

BACKEND_DOMAIN=a.base.local
PARTNER_DOMAIN=p.base.local
FRONTEND_DOMAIN=www.base.local

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=framework
DB_USERNAME=framework
DB_PASSWORD=replace-with-a-strong-password

SESSION_DRIVER=redis
SESSION_STORE=backend
CACHE_STORE=backend

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=false
```

说明：

- `.env` 包含本地凭据，不得提交到 Git。
- 使用本项目独立的数据库，不要直接沿用其他项目的数据库名或账号。
- 默认会话和缓存使用 Redis；如果 Redis 未启动，后台登录和缓存操作会失败。
- `QUEUE_CONNECTION=sync` 仅表示默认队列同步执行，AI 等使用独立数据库队列的功能仍需要对应 worker。
- 邮件、对象存储、OAuth 和 AI 服务按需配置；未配置时保持相应功能关闭即可。
- 修改 `.env` 后，如配置未生效，执行 `php artisan config:clear`。

## 5. 初始化项目

### 5.1 设置目录权限

Nginx/PHP-FPM 通常以 `www-data` 用户运行，需要允许其写入 `storage` 和 `bootstrap/cache`：

```bash
cd /www/framework
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R g+rwX storage bootstrap/cache
```

项目文件仍由当前开发用户维护，不要将整个项目目录设为 `777`。

### 5.2 执行迁移、种子和资源构建

确认 MySQL、Redis 已启动且 `.env` 配置正确后执行：

```bash
php artisan migrate --seed --no-interaction
php artisan storage:link --no-interaction
npm run build
```

开发和测试环境会创建初始后台账号：

- 登录地址：`http://a.base.local/login`
- 邮箱：`admin@framework.local`
- 密码：`12345qwert`

首次登录后应立即修改默认密码。重复执行种子不会覆盖已有管理员凭据。生产环境不会创建该演示账号，应通过以下交互式命令创建超级管理员：

```bash
php artisan backend:create-admin admin@example.com
```

## 6. 配置本地域名和 Nginx

### 6.1 配置 hosts

编辑本机 hosts 文件：

```bash
sudoedit /etc/hosts
```

添加：

```text
127.0.0.1 a.base.local p.base.local www.base.local
```

如果使用 WSL2、虚拟机或远程开发机，应在浏览器所在机器上配置 hosts，并将地址指向实际运行 Nginx 的机器。WSL2 也可以参考项目根目录的 `configure-local-domains.ps1`。

### 6.2 启用项目 Nginx 配置

项目根目录已提供适用于上述目录和 PHP 8.4 的 `nginx.conf`：

```bash
sudo apt install -y nginx
sudo cp /www/framework/nginx.conf /etc/nginx/sites-available/framework.conf
sudo ln -s /etc/nginx/sites-available/framework.conf /etc/nginx/sites-enabled/framework.conf
sudo nginx -t
sudo systemctl enable --now nginx
sudo systemctl reload nginx
```

如果项目不在 `/www/framework`，需同步修改配置中的 `root`。如果不是 PHP 8.4，需修改 `fastcgi_pass` 的 socket 路径；本项目不建议降低 PHP 版本。

检查服务：

```bash
systemctl status nginx --no-pager
curl -I http://www.base.local/up
```

各入口地址：

| 入口 | 地址 |
| --- | --- |
| 管理后台 | `http://a.base.local/login` |
| 网站前台 | `http://www.base.local/` |
| 公共 API | `http://www.base.local/api/v1` |

## 7. 启动开发服务

使用 Nginx 时，PHP 请求已经由 Nginx 和 PHP-FPM 处理，只需根据前端开发方式选择一种模式。

### 模式一：热更新开发

```bash
cd /www/framework
npm run dev
```

该命令同时启动后台和前台的 Vite 服务：

- 后台 Vite：端口 `5174`
- 前台 Vite：端口 `5175`

页面仍通过 `a.base.local` 和 `www.base.local` 访问，不需要在浏览器中直接打开 Vite 端口。

### 模式二：使用构建产物

```bash
npm run build
```

修改前端源码后重新构建。也可以单独构建：

```bash
npm run build:backend
npm run build:frontend
```

不要直接修改 `public/build/` 中的生成文件。

### 不使用 Nginx 的临时开发方式

也可以分别启动 Laravel 内置服务器和 Vite：

```bash
# 终端一
composer run dev

# 终端二
npm run dev
```

此时访问：

- 后台：`http://a.base.local:8000/login`
- 前台：`http://www.base.local:8000/`

## 8. 队列和计划任务

仅在启用异步任务或开发相关功能时启动对应进程。开发阶段可在独立终端运行：

```bash
php artisan queue:work             # 默认队列
php artisan queue:work ai          # AI 数据库队列
php artisan queue:work maintenance # 计划任务执行队列
php artisan queue:work file-sync   # 文件同步队列
php artisan schedule:work          # 每分钟触发计划任务调度器
```

项目每分钟执行 `system-schedule:dispatch`。不需要开发计划任务、文件同步或 AI 功能时，可以不启动相应 worker。

生产环境应使用 Supervisor 或 systemd 保持 worker 运行，并通过系统 Cron 调用 Laravel 调度器，而不是长期手动运行 `schedule:work`。

## 9. 验证和测试

### 9.1 基础验证

```bash
cd /www/framework
php artisan about
php artisan migrate:status
composer check-platform-reqs
redis-cli ping
npm run build
curl -I http://www.base.local/up
```

### 9.2 创建独立测试数据库

测试固定使用 MySQL 数据库 `framework_test`，并复用 `.env` 中的数据库主机和账号。不要使用开发数据库或生产数据库运行测试。

```bash
sudo mysql
```

```sql
CREATE DATABASE `framework_test`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `framework_test`.*
  TO 'framework'@'127.0.0.1';

EXIT;
```

运行测试：

```bash
php artisan test --compact
```

运行单个测试文件：

```bash
php artisan test --compact tests/Feature/FrontendPageTest.php
```

修改 PHP 文件后，在提交前运行项目格式化工具：

```bash
vendor/bin/pint --dirty --format agent
```

## 10. 常用维护命令

```bash
php artisan config:clear       # 清除配置缓存
php artisan cache:clear        # 清除应用缓存
php artisan route:list         # 查看路由
php artisan migrate:status     # 查看迁移状态
php artisan queue:failed       # 查看失败队列任务
npm ci                         # 严格按锁文件重新安装前端依赖
composer install               # 按锁文件安装 PHP 依赖
```

不要随意执行 `migrate:fresh`、`migrate:reset` 或清空 Redis；这些操作会删除数据，只能在明确确认数据可丢弃的环境中使用。

## 11. 常见问题

### 页面报错或无法写入日志

检查 `storage/logs/laravel.log`，并确认 `storage`、`bootstrap/cache` 对 PHP-FPM 用户可写：

```bash
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R g+rwX storage bootstrap/cache
```

### 提示无法连接 Redis

确认服务和 PHP 扩展均正常：

```bash
systemctl status redis-server --no-pager
redis-cli ping
php -m | grep redis
```

### Nginx 返回 502

检查 PHP-FPM 是否运行，以及 Nginx 配置中的 socket 是否为 `/run/php/php8.4-fpm.sock`：

```bash
systemctl status php8.4-fpm --no-pager
ls -l /run/php/php8.4-fpm.sock
sudo nginx -t
```

### 提示找不到 Vite manifest

根据开发方式执行以下任一命令：

```bash
npm run dev
# 或
npm run build
```

同时确认没有遗留的 Vite 热更新文件指向已停止的开发服务。不要手动修改 `public/build/` 中的构建产物。

### 数据库认证失败

核对 `.env` 中的 `DB_HOST`、`DB_PORT`、数据库名、用户名和密码，并确认 MySQL 账号主机为 `127.0.0.1`。修改后执行：

```bash
php artisan config:clear
php artisan migrate:status
```

### 域名无法访问或打开了错误的入口

检查浏览器所在机器的 hosts、`.env` 中的三个域名和 Nginx 的 `server_name`。本地域名必须与访问地址保持一致，因为项目按域名分派后台、合作端和前台路由。

## 12. 开发规范入口

项目功能、目录结构和快速命令见 [README.md](README.md)，具体编码、权限、多语言、前后端目录和构建约定见 [AGENTS.md](AGENTS.md)。
