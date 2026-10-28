# Base 三端基础框架

基于 Laravel 的通用项目底座。三端指管理后台（Backend）、合作方端（Partner）和网站前台（Frontend）；Api 是独立的接口层，不是第四个网站端。项目不包含手机、产品、品牌、规格、报价等垂直业务。

## 默认域名

| 端 | 默认域名 | 环境变量 | 职责 |
| --- | --- | --- | --- |
| 管理后台 Backend | `a.base.local` | `BACKEND_DOMAIN` | 平台管理、组织权限、菜单、审计和系统配置 |
| 合作方端 Partner | `p.base.local` | `PARTNER_DOMAIN` | 合作方工作台及其独立身份、权限和业务入口 |
| 网站前台 Frontend | `www.base.local` | `FRONTEND_DOMAIN` | 面向用户的网站页面 |
| 公共接口 Api | `www.base.local/api/v1`（架构约定） | 使用前台域名 | 对外及移动端接口，独立于后台浏览器接口 |

域名默认值统一定义在 `config/platform.php`，可由 `.env` 覆盖：

```dotenv
APP_URL=http://www.base.local
BACKEND_DOMAIN=a.base.local
PARTNER_DOMAIN=p.base.local
FRONTEND_DOMAIN=www.base.local
```

本地 hosts 示例（服务运行在其他地址时替换 IP）：

```text
127.0.0.1 a.base.local p.base.local www.base.local
```

三个域名共用 Laravel 项目及 `public/` Web 根目录，通过域名路由区分入口。hosts 只负责解析域名，还需 Web 服务器配置将三个域名转发至项目入口，并将不存在的文件交给 `public/index.php`。

## 当前实现状态

当前目录是从原项目精简出的版本。**三端是项目架构约定，目前只有 Backend 完整注册了路由。** Partner、Frontend 入口在此前精简中被移除；本次补齐了域名配置与说明，尚未恢复这些入口。仅配置域名不会自动产生页面。

| 范围 | 当前状态 |
| --- | --- |
| Backend | 登录、个人资料、登录密码与安全密码、管理员、部门治理、菜单权限、操作日志 |
| 通用能力 | 文件与存储账号、文件同步、系统配置、字段组件、多语言、邮件配置、AI 基础库 |
| Partner | 默认域名已配置；路由、认证、基础层和页面入口待恢复 |
| Frontend | 默认域名已配置；路由和页面入口待恢复 |
| Api | 保留 BaseController、BaseRequest、BaseService 等基础代码；`routes/api.php` 为空，尚未注册到域名路由 |

## 目录与分层约定

```text
app/
  Http/
    Controllers/{Backend,Partner,Frontend,Api}/
    Requests/{Backend,Partner,Frontend,Api}/
    Resources/{Backend,Partner,Frontend,Api}/
    Middleware/{Backend,Partner,Frontend,Api}/
  Services/{Backend,Partner,Frontend,Api}/
  Models/                 数据模型
  Libs/                   通用能力库
routes/
  web.php                 按域名分派网站入口
  backend.php             管理后台路由（已实现）
  partner.php             合作方端路由（待恢复）
  frontend.php            网站前台路由（待恢复）
  api.php                 公共接口路由（当前为空、未挂载）
resources/
  js/{backend,partner,frontend}/
  css/{backend,partner,frontend}/
  views/{backend,partner,frontend}/
database/
  migrations/             框架表及按领域组织的迁移
  seeders/                初始数据
lang/                     Laravel 验证、响应及公共翻译
public/build/backend/     Vite 生成的后台资源
```

上面的花括号路径表示分端组织约定，不代表每个目录都已存在；当前前端源码仅保留 `backend` 和共享目录。

Controller 负责提取验证后的输入、委托 Service 并构造响应；Request 负责验证；Service 负责业务流程与事务；Resource 负责序列化。Backend、Partner、Api 的具体端点继承各自区域的 BaseController、BaseService 和 BaseRequest。公共工具不依附某一业务端。

三端恢复或扩展时应独立处理认证、权限、中间件、会话与前端构建入口，不直接复用后台登录态。后台浏览器接口与公共 Api 分开管理。

## 后台约定

- Vue 3、Vue Router、Element Plus，使用单个 Blade 入口，导航保留 SPA 外壳。
- 动态页面从认证用户的菜单加载；按钮权限不进入导航；服务端始终执行权限校验。
- 董事会为唯一根部门；董事会成员拥有全部权限。以部门及负责人管理授权，不引入角色授权。
- 管理员、部门、菜单、日志分别使用 `AdminUser`、`AdminDepartment`、`AdminMenu`、`AdminLog`。
- 后台读取接口用 GET，写入用 POST，动作显式命名为 list、detail、create、update、delete 等。
- 页面与动作权限标识对应命名路由；前端接口地址由 Laravel 的 BackendRoutes 生成。
- JSON 请求通过 `X-Lang` 传递语言；业务翻译持久化在 `_i18n` JSON 字段，由模型 accessor 处理语言回退。
- Vue 模块翻译放在模块的 `locales/` 目录；公共翻译放在后台根 `locales/`，四套界面语言键保持一致。
- 仅编辑 `resources/` 源码，`public/build/backend` 只存放构建产物。

详细开发约束见 `AGENTS.md`。

## 本地初始化

需要 PHP 8.4 或更高版本、Composer、与锁定 Vite 版本兼容的 Node.js/npm，以及项目配置所用的数据库和 Redis。当前环境已安装 Laravel 13；依赖以 `composer.lock` 和 `package-lock.json` 为准。

在 framework 目录执行：

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate --no-interaction
```

首次初始化时先确认 `.env` 指向本项目独立数据库，并配置 Redis、三个域名及邮件/存储等按需功能。不要指向 techpeek 数据库。本项目不会复制 techpeek 的 `.env`，本次整理也没有改动 `config/database.php` 或环境模板中的数据库连接项。

确认目标数据库后，再执行：

```bash
php artisan migrate --seed --no-interaction
npm run build:backend
```

默认示例配置的会话、后台缓存和系统配置缓存使用 Redis。初始数据包含部门、管理员、基础菜单、文件存储配置、系统配置和地区数据；重复执行管理员种子不会覆盖已有管理员凭据。

当前种子提供开发账号 `admin@framework.local`，初始密码为 `12345qwert`，登录页面使用邮箱。部署前应修改该密码并检查其他初始部门账号。

后台已通过 Nginx 启动，Windows 可直接打开 `http://127.0.0.1:8080/login`（仅本机监听，无需 hosts 配置）。域名入口为 `http://a.base.local/login`，Windows hosts 配置完成后可使用。站点配置源文件为 `nginx.conf`，已安装到 `/etc/nginx/sites-available/base.local`。开发模式可分别启动 Laravel 和 Vite：

```bash
composer run dev
npm run dev:backend
```

`artisan serve` 默认端口为 8000；没有反向代理时，后台访问地址为 `http://a.base.local:8000/login`。网站域名必须与路由配置一致。

## 构建、队列与验证

```bash
npm run build:backend
php artisan route:list --except-vendor --no-interaction
php artisan view:cache --no-interaction
php artisan view:clear --no-interaction
php artisan test --compact
```

当前 `npm run build` 和 `npm run dev` 也只对应后台；Partner、Frontend 恢复后需各自增加独立构建配置、产物目录及 Vite hot 文件。

文件同步与 AI 后台任务按需启动对应工作进程：

```bash
php artisan queue:work file-sync
php artisan queue:work ai
php artisan schedule:work
```

测试必须使用独立测试数据库。`phpunit.xml` 使用独立 MySQL 数据库 `framework_test`，已在本地创建，与运行库 `base` 和 techpeek 数据库分离。63 项基础功能回归测试以及真实 HTTP 登录、管理员、部门、菜单、审计、文件和系统配置读取接口已通过验证。当前 PHP 缺少 SQLite 驱动，测试使用 MySQL。

## 数据边界

保留框架基础表：Laravel 用户/会话、缓存、任务队列、后台组织权限与审计、文件、系统配置、地区和 AI 任务回执。产品、品牌、规格、商家报价、文章、轮播、评论及其关联迁移和种子已移除。

本目录独立于 techpeek。对框架的后续修改应落在 framework 内，数据库迁移与初始化也应只针对框架自己的数据库。
