# Framework

基于 Laravel 的可复用应用基础框架，提供管理后台、网站前台和公共 API，内置组织权限、会员、内容、文件、通知及系统运维能力。

## 功能概览

### 管理后台

- 登录、密码找回、个人资料、安全密码、主题偏好和通知中心。
- 管理员、部门树、负责人、菜单权限和操作日志。
- 前台会员管理、会员等级及等级变更记录。
- 公告、轮播等内容管理。
- 文件管理、存储账号配置及文件同步。
- 系统配置、配置定义、通知模板、队列监控和计划任务管理。

### 网站前台与公共 API

- 用户注册、登录、退出、密码找回和邮箱验证。
- 个人资料、头像、登录密码及邮箱管理。
- Google、Facebook 登录与账号绑定。
- 用户通知、已读操作和通知偏好。

### 通用能力

- 六种界面语言，以及基于 `_i18n` 字段的业务内容多语言。
- 本地及 S3 兼容对象存储。
- 数据库队列、计划任务和邮件。

## 技术栈

- PHP 8.4、Laravel 13
- Vue 3、Vue Router、Element Plus
- Vite 8、Tailwind CSS 4
- MySQL 兼容数据库、Redis

依赖的准确版本以 `composer.lock` 和 `package-lock.json` 为准。

## 快速开始

### 1. 准备环境

安装 PHP 8.4、Composer、与 Vite 8 兼容的 Node.js/npm，以及 MySQL 和 Redis。默认配置的会话和缓存使用 Redis。

### 2. 安装依赖并创建配置

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate --no-interaction
```

编辑 `.env`，至少检查以下配置：

```dotenv
APP_URL=http://www.base.local
BACKEND_DOMAIN=a.base.local
FRONTEND_DOMAIN=www.base.local

DB_DATABASE=framework
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

必须将数据库配置改为本项目的独立数据库，不要直接沿用 `.env.example` 中的示例值。邮件、对象存储和 OAuth 等功能可按需配置。

### 3. 初始化数据库并构建资源

先创建空数据库，再执行：

```bash
php artisan migrate --seed --no-interaction
php artisan storage:link --no-interaction
npm run build
```

开发和测试环境会创建初始后台账号：

- 邮箱：`admin@framework.local`
- 密码：`12345qwert`
- 登录地址：`http://a.base.local/login`

首次登录后应立即修改默认密码。重复执行种子不会覆盖已存在的管理员凭据。生产环境不会创建公开密码的演示账号，应在迁移后通过交互式命令创建超级管理员：

```bash
php artisan backend:create-admin admin@example.com
```

## 本地开发

分别启动 Laravel 和前后端 Vite 服务：

```bash
# 终端一
composer run dev

# 终端二
npm run dev
```

将开发域名指向本机，例如：

```text
127.0.0.1 a.base.local www.base.local
```

使用 `artisan serve` 默认端口时，访问：

- 后台：`http://a.base.local:8000/login`
- 前台：`http://www.base.local:8000/`

使用 Nginx、Apache 等 Web 服务器时，将各域名的 Web 根目录指向 `public/`，并将不存在的文件交给 `public/index.php`。

## 入口与域名

| 入口 | 默认地址 | 环境变量 | 说明 |
| --- | --- | --- | --- |
| 管理后台 | `http://a.base.local` | `BACKEND_DOMAIN` | 已注册页面及后台浏览器接口 |
| 网站前台 | `http://www.base.local` | `FRONTEND_DOMAIN` | 已注册前台页面 |
| 公共 API | `http://www.base.local/api/v1` | 使用前台域名 | 前台账号、认证和通知接口 |

公共 API 是独立接口层，不是额外的网站端；其路由目前定义在 `routes/web.php`，`routes/api.php` 保留用于后续扩展。

## 构建命令

```bash
npm run build          # 构建后台和前台
npm run build:backend  # 仅构建后台
npm run build:frontend # 仅构建前台

npm run dev            # 同时启动两端开发服务
npm run dev:backend    # 仅启动后台开发服务，端口 5174
npm run dev:frontend   # 仅启动前台开发服务，端口 5175
```

前端源码只放在 `resources/` 中；`public/build/` 仅存放 Vite 构建产物，不要直接修改。

## 项目结构

```text
app/
  Http/
    Controllers/         Backend、Frontend、Api 控制器
    Requests/            输入验证
    Resources/           API 序列化
    Middleware/          认证、语言、权限等中间件
  Models/                数据模型
  Services/              业务流程与事务
  Jobs/                  队列任务
  Libs/                  通用能力库
config/
  platform.php           域名、语言和后台界面配置
routes/
  web.php                域名分派、前台页面和公共 API
  backend.php            管理后台路由
  backend/system.php     后台系统管理路由
  console.php            计划任务与控制台命令
resources/
  js/backend/            后台 Vue 源码
  js/frontend/           前台 Vue 源码
  css/                   前后端样式源码
  views/                 Blade 入口视图
database/
  migrations/            数据表结构
  seeders/               初始数据
lang/                    Laravel 验证与响应翻译
tests/Feature/           功能测试
public/build/            Vite 构建产物
```

Controller 只处理 HTTP 输入和响应，Request 负责验证，Service 负责业务流程与事务，Resource 负责序列化。详细开发规范见 [AGENTS.md](AGENTS.md)。

## 队列与计划任务

启用异步队列或对应后台任务时，按需运行：

```bash
php artisan queue:work             # 默认队列
php artisan queue:work maintenance # 计划任务执行队列
php artisan queue:work file-sync   # 文件同步队列
php artisan schedule:work          # 计划任务调度器
```

生产环境应使用 Supervisor、systemd 等进程管理工具保持 worker 和调度器运行。

## 测试

测试固定使用独立的 MySQL 数据库 `framework_test`，并复用 `.env` 中的数据库主机和账号。运行前请创建该测试数据库，并确保账号具有建表和迁移权限；不要将测试配置指向运行数据库。

```bash
php artisan test --compact
```

运行单个测试文件：

```bash
php artisan test --compact tests/Feature/FrontendPageTest.php
```

## 开发约定

- 后台采用单 Blade 入口的 Vue SPA，菜单和路由由服务端权限数据生成。
- 部门与负责人是后台授权核心，不使用角色授权。
- 后台读取使用 GET，写入使用 POST，接口动作显式命名。
- 多语言业务字段使用 `_i18n` JSON 列，由模型访问器处理语言回退。
- 后台 JSON 请求通过 `X-Lang` 传递语言。
- 数据库连接配置由项目维护，不要随功能开发随意修改。
