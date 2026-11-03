<?php

namespace Database\Seeders\backend\content;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use Illuminate\Database\Seeder;

class ContentDocumentSeeder extends Seeder
{
    /** @var array<string, ContentDocumentCategory> */
    private array $categories = [];

    public function run(): void
    {
        foreach ($this->categoryDefinitions() as $definition) {
            $category = ContentDocumentCategory::firstOrCreate(
                ['type' => $definition['type'], 'slug' => $definition['slug']],
                [
                    'parent_id' => $definition['parent'] ? $this->categories[$definition['parent']]->id : null,
                    'name_i18n' => ['en' => $definition['name'], 'zh_CN' => $definition['name_zh']],
                    'description_i18n' => ['en' => $definition['description'], 'zh_CN' => $definition['description_zh']],
                    'sort' => $definition['sort'],
                    'active' => true,
                ],
            );
            $this->categories[$definition['type'].'.'.$definition['slug']] = $category;
        }

        foreach ($this->documentDefinitions() as $definition) {
            ContentDocument::firstOrCreate(
                ['type' => $definition['type'], 'slug' => $definition['slug']],
                [
                    'category_id' => $this->categories[$definition['type'].'.'.$definition['category']]->id,
                    'title_i18n' => ['en' => $definition['title'], 'zh_CN' => $definition['title_zh']],
                    'summary_i18n' => ['en' => $definition['summary'], 'zh_CN' => $definition['summary_zh']],
                    'content_i18n' => ['en' => $this->content($definition['sections']), 'zh_CN' => $this->content($definition['sections_zh'])],
                    'published_at' => now(),
                    'sort' => $definition['sort'],
                    'active' => true,
                ],
            );
        }
    }

    private function categoryDefinitions(): array
    {
        return [
            $this->category('development', 'development', null, 'Development Documentation', '开发文档', 'Architecture, APIs and module development.', '架构、API 与模块开发指南。', 10),
            $this->category('development', 'getting-started', 'development', 'Getting Started', '快速开始', 'Install and understand the project.', '安装项目并了解基本结构。', 10),
            $this->category('development', 'architecture', 'development', 'Architecture', '架构与约定', 'Application layers and shared conventions.', '应用分层与公共开发约定。', 20),
            $this->category('development', 'backend-modules', 'development', 'Backend Modules', '后台模块开发', 'Authentication, members and business modules.', '认证、会员与业务模块开发。', 30),
            $this->category('development', 'operations', 'development', 'Operations', '运维与任务', 'Files, queues and scheduled work.', '文件、队列与计划任务。', 40),
            $this->category('usage', 'usage', null, 'User Guides', '使用文档', 'Member and administrator guides.', '会员与管理员使用指南。', 10),
            $this->category('usage', 'member-center', 'usage', 'Member Center', '会员中心', 'Account, wallet and notifications.', '账户、钱包与通知。', 10),
            $this->category('usage', 'administration', 'usage', 'Administration', '后台管理', 'Departments, administrators and members.', '部门、管理员与会员管理。', 20),
            $this->category('usage', 'content-management', 'usage', 'Content Management', '内容与文档', 'Documentation, announcements and files.', '文档、公告与文件管理。', 30),
            $this->category('usage', 'system-management', 'usage', 'System Management', '系统管理', 'Settings, queues and schedules.', '设置、队列与计划任务。', 40),
        ];
    }

    private function category(string $type, string $slug, ?string $parent, string $name, string $nameZh, string $description, string $descriptionZh, int $sort): array
    {
        return ['type' => $type, 'slug' => $slug, 'parent' => $parent ? $type.'.'.$parent : null, 'name' => $name, 'name_zh' => $nameZh, 'description' => $description, 'description_zh' => $descriptionZh, 'sort' => $sort];
    }

    private function documentDefinitions(): array
    {
        return [
            $this->document('development', 'quick-start', 'getting-started', 'Quick Start', '快速开始', 'Install Ubuntu, PHP, MySQL, Redis, Nginx and Node.js, then initialize and verify the application.', '完成 Ubuntu、PHP、MySQL、Redis、Nginx、Node.js 环境搭建，并初始化和验证应用。', [
                ['Recommended environment', '<p>Use a 64-bit Ubuntu LTS release and keep all services on supported stable versions. The exact PHP and JavaScript dependency versions are fixed by <code>composer.lock</code> and <code>package-lock.json</code>.</p><ul><li><strong>Ubuntu:</strong> 26.04 LTS recommended; 24.04 LTS is also supported.</li><li><strong>PHP:</strong> 8.4 with the latest patches, plus Composer 2.</li><li><strong>MySQL:</strong> 8.4 LTS recommended; MySQL 8.0 or later is required.</li><li><strong>Redis:</strong> 8.0 recommended; Redis 7.0 or later with the PHP <code>phpredis</code> extension.</li><li><strong>Nginx:</strong> 1.24 or later, using PHP-FPM.</li><li><strong>Node.js and npm:</strong> Node.js 24 LTS and npm 11 or later recommended. Vite 8 requires Node.js <code>^20.19.0</code> or <code>&gt;=22.12.0</code>.</li><li><strong>Git:</strong> a current 2.x release.</li></ul>'],
                ['Step 1: Install basic tools', '<p>Refresh the APT package index and install the download, version-control and archive tools needed by the later steps.</p>'.$this->code("sudo apt update\nsudo apt install -y ca-certificates curl git unzip software-properties-common")],
                ['Step 2: Install PHP 8.4 and PHP-FPM', '<p>Add the Ondřej PHP repository when Ubuntu does not provide PHP 8.4, then install PHP, PHP-FPM and the extensions used by the application. Start PHP-FPM and verify the version and extensions before continuing.</p>'.$this->code("sudo add-apt-repository ppa:ondrej/php\nsudo apt update\nsudo apt install -y php8.4-cli php8.4-fpm php8.4-mysql php8.4-redis \\\n  php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd \\\n  php8.4-intl php8.4-bcmath php8.4-opcache\nsudo systemctl enable --now php8.4-fpm\nphp -v\nphp -m | grep -E 'mbstring|pdo_mysql|redis|xml|curl'")],
                ['Step 3: Install Composer', '<p>Install Composer with the official installer after PHP is available. Confirm that the global <code>composer</code> command works.</p>'.$this->code("cd /tmp\nphp -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"\nphp composer-setup.php --quiet\nrm composer-setup.php\nsudo mv composer.phar /usr/local/bin/composer\ncomposer --version")],
                ['Step 4: Install MySQL', '<p>Install and start MySQL, then verify its version and service status. Create the application database and account in the next section.</p>'.$this->code("sudo apt install -y mysql-server\nsudo systemctl enable --now mysql\nmysql --version\nsystemctl status mysql --no-pager")],
                ['Step 5: Install Redis', '<p>Install and start Redis separately from MySQL. The <code>redis-cli ping</code> command must return <code>PONG</code>, and <code>php -m | grep redis</code> must show the PHP extension.</p>'.$this->code("sudo apt install -y redis-server\nsudo systemctl enable --now redis-server\nredis-cli ping\nphp -m | grep redis")],
                ['Step 6: Install Node.js and npm', '<p>Install Node.js 24 LTS from NodeSource. npm is included with Node.js; verify both versions before installing project dependencies.</p>'.$this->code("curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -\nsudo apt install -y nodejs\nnode --version\nnpm --version")],
                ['Step 7: Install Nginx', '<p>Install and start Nginx after PHP-FPM is available. Check that the service is running; the project virtual host is configured in the later hosts and Nginx section.</p>'.$this->code("sudo apt install -y nginx\nsudo systemctl enable --now nginx\nnginx -v\nsystemctl status nginx --no-pager")],
                ['Create the development database', '<p>Create an isolated database and a dedicated application account. Replace the example password before running these statements through <code>sudo mysql</code>; do not use the MySQL root account in the application configuration.</p>'.$this->code("CREATE DATABASE `framework` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nCREATE USER 'framework'@'127.0.0.1' IDENTIFIED BY 'replace-with-a-strong-password';\nGRANT ALL PRIVILEGES ON `framework`.* TO 'framework'@'127.0.0.1';")],
                ['Install project dependencies', '<p>Clone the repository into <code>/www/framework</code>, or enter the existing project directory. Install dependencies from the lock files, create the local environment file and generate the application key. Do not overwrite an existing <code>.env</code> or regenerate a key that is already in use.</p>'.$this->code("cd /www/framework\ncomposer install\nnpm ci\ncp .env.example .env\nphp artisan key:generate --no-interaction")],
                ['Configure environment variables', '<p>Edit <code>.env</code> and check the application domains, MySQL and Redis settings. Keep <code>.env</code> out of Git and configure mail, object storage, OAuth and AI only when those features are needed.</p>'.$this->code("APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://www.base.local\nBACKEND_DOMAIN=a.base.local\nPARTNER_DOMAIN=p.base.local\nFRONTEND_DOMAIN=www.base.local\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=framework\nDB_USERNAME=framework\nDB_PASSWORD=replace-with-a-strong-password\n\nSESSION_DRIVER=redis\nSESSION_STORE=backend\nCACHE_STORE=backend\nREDIS_CLIENT=phpredis\nREDIS_HOST=127.0.0.1\nREDIS_PORT=6379\nREDIS_PASSWORD=null\nQUEUE_CONNECTION=sync\nSESSION_SECURE_COOKIE=false")],
                ['Configure hosts and Nginx', '<p>Add <code>127.0.0.1 a.base.local p.base.local www.base.local</code> to the hosts file on the machine running the browser. For WSL2, a virtual machine or a remote development host, point these names to the machine running Nginx instead.</p><p>The repository includes an Nginx configuration for <code>/www/framework</code> and PHP 8.4. Enable it and test the configuration before reloading. If the project path differs, update the configured <code>root</code>.</p>'.$this->code("sudo cp /www/framework/nginx.conf /etc/nginx/sites-available/framework.conf\nsudo ln -s /etc/nginx/sites-available/framework.conf /etc/nginx/sites-enabled/framework.conf\nsudo nginx -t\nsudo systemctl reload nginx")],
                ['Initialize the application', '<p>Allow PHP-FPM to write to the storage and cache directories, then migrate and seed the database, create the public storage link and build both frontend bundles. Do not make the entire project directory world-writable.</p>'.$this->code("cd /www/framework\nsudo chgrp -R www-data storage bootstrap/cache\nsudo chmod -R g+rwX storage bootstrap/cache\nphp artisan migrate --seed --no-interaction\nphp artisan storage:link --no-interaction\nnpm run build").'<p>Development and testing seeds create the administrator <code>admin@framework.local</code> with password <code>12345qwert</code>. Sign in at <code>http://a.base.local/login</code> and change this password immediately. Production environments should create an administrator with <code>php artisan backend:create-admin</code> instead.</p>'],
                ['Run development services', '<p>With Nginx running, use <code>npm run dev</code> for hot module replacement. It starts the backend Vite server on port 5174 and the frontend server on port 5175. Continue to open the application through the configured local domains. Use <code>npm run build</code> instead when testing generated assets.</p>'.$this->code("npm run dev\n# Or build backend and frontend assets separately:\nnpm run build:backend\nnpm run build:frontend").'<p>For temporary development without Nginx, run <code>composer run dev</code> and <code>npm run dev</code> in separate terminals, then use port 8000 on the application URLs. Start queue workers and the scheduler only when working on features that need them.</p>'.$this->code("php artisan queue:work\nphp artisan queue:work ai\nphp artisan queue:work maintenance\nphp artisan queue:work file-sync\nphp artisan schedule:work")],
                ['Verify the installation and run tests', '<p>Check the application, migrations, PHP platform requirements and HTTP health endpoint. Tests use the separate MySQL database <code>framework_test</code>; create it and grant the same development account access before running the suite. Never point tests at a development or production database.</p>'.$this->code("php artisan about\nphp artisan migrate:status\ncomposer check-platform-reqs\ncurl -I http://www.base.local/up\n\n-- Run through sudo mysql first:\nCREATE DATABASE `framework_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL PRIVILEGES ON `framework_test`.* TO 'framework'@'127.0.0.1';\n\nphp artisan test --compact\nvendor/bin/pint --dirty --format agent")],
            ], [
                ['推荐环境', '<p>使用 64 位 Ubuntu LTS，并保持各服务处于受支持的稳定版本。PHP 和 JavaScript 依赖的准确版本分别以 <code>composer.lock</code> 和 <code>package-lock.json</code> 为准。</p><ul><li><strong>Ubuntu：</strong>推荐 26.04 LTS，也可使用 24.04 LTS。</li><li><strong>PHP：</strong>8.4 最新补丁版，搭配 Composer 2。</li><li><strong>MySQL：</strong>推荐 8.4 LTS，要求 8.0 或更高版本。</li><li><strong>Redis：</strong>推荐 8.0，要求 7.0 或更高版本，并安装 PHP <code>phpredis</code> 扩展。</li><li><strong>Nginx：</strong>1.24 或更高版本，通过 PHP-FPM 提供 Web 服务。</li><li><strong>Node.js 和 npm：</strong>推荐 Node.js 24 LTS、npm 11 或更高版本；Vite 8 要求 Node.js <code>^20.19.0</code> 或 <code>&gt;=22.12.0</code>。</li><li><strong>Git：</strong>当前稳定的 2.x 版本。</li></ul>'],
                ['第 1 步：安装基础工具', '<p>先更新 APT 软件包索引，再安装后续步骤需要的下载、版本控制和压缩包工具。</p>'.$this->code("sudo apt update\nsudo apt install -y ca-certificates curl git unzip software-properties-common")],
                ['第 2 步：安装 PHP 8.4 和 PHP-FPM', '<p>当 Ubuntu 官方源未提供 PHP 8.4 时，先添加 Ondřej PHP 源，再安装 PHP、PHP-FPM 和应用所需扩展。启动 PHP-FPM，并核对 PHP 版本与扩展后再继续。</p>'.$this->code("sudo add-apt-repository ppa:ondrej/php\nsudo apt update\nsudo apt install -y php8.4-cli php8.4-fpm php8.4-mysql php8.4-redis \\\n  php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd \\\n  php8.4-intl php8.4-bcmath php8.4-opcache\nsudo systemctl enable --now php8.4-fpm\nphp -v\nphp -m | grep -E 'mbstring|pdo_mysql|redis|xml|curl'")],
                ['第 3 步：安装 Composer', '<p>确认 PHP 可用后，使用官方安装程序安装 Composer，并检查全局 <code>composer</code> 命令是否正常。</p>'.$this->code("cd /tmp\nphp -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"\nphp composer-setup.php --quiet\nrm composer-setup.php\nsudo mv composer.phar /usr/local/bin/composer\ncomposer --version")],
                ['第 4 步：安装 MySQL', '<p>单独安装并启动 MySQL，然后核对版本和服务状态。应用数据库与账号在下一节创建。</p>'.$this->code("sudo apt install -y mysql-server\nsudo systemctl enable --now mysql\nmysql --version\nsystemctl status mysql --no-pager")],
                ['第 5 步：安装 Redis', '<p>将 Redis 与 MySQL 分开安装并启动。<code>redis-cli ping</code> 必须返回 <code>PONG</code>，<code>php -m | grep redis</code> 必须显示 PHP Redis 扩展。</p>'.$this->code("sudo apt install -y redis-server\nsudo systemctl enable --now redis-server\nredis-cli ping\nphp -m | grep redis")],
                ['第 6 步：安装 Node.js 和 npm', '<p>通过 NodeSource 安装 Node.js 24 LTS。npm 随 Node.js 一起安装，安装项目依赖前先核对两者版本。</p>'.$this->code("curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -\nsudo apt install -y nodejs\nnode --version\nnpm --version")],
                ['第 7 步：安装 Nginx', '<p>确认 PHP-FPM 可用后，安装并启动 Nginx，检查服务状态。项目站点配置在后面的“配置本地域名和 Nginx”一节完成。</p>'.$this->code("sudo apt install -y nginx\nsudo systemctl enable --now nginx\nnginx -v\nsystemctl status nginx --no-pager")],
                ['创建开发数据库', '<p>创建独立数据库和应用专用账号。通过 <code>sudo mysql</code> 执行以下语句前，先替换示例密码；应用配置不要使用 MySQL root 账号。</p>'.$this->code("CREATE DATABASE `framework` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nCREATE USER 'framework'@'127.0.0.1' IDENTIFIED BY 'replace-with-a-strong-password';\nGRANT ALL PRIVILEGES ON `framework`.* TO 'framework'@'127.0.0.1';")],
                ['安装项目依赖', '<p>将仓库克隆到 <code>/www/framework</code>，或进入已有项目目录。按照锁文件安装依赖，创建本地环境文件并生成应用密钥。不要覆盖已有 <code>.env</code>，也不要重新生成正在使用的密钥。</p>'.$this->code("cd /www/framework\ncomposer install\nnpm ci\ncp .env.example .env\nphp artisan key:generate --no-interaction")],
                ['配置环境变量', '<p>编辑 <code>.env</code>，核对应用域名、MySQL 和 Redis 配置。<code>.env</code> 不得提交到 Git；邮件、对象存储、OAuth 和 AI 仅在需要时配置。</p>'.$this->code("APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=http://www.base.local\nBACKEND_DOMAIN=a.base.local\nPARTNER_DOMAIN=p.base.local\nFRONTEND_DOMAIN=www.base.local\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=framework\nDB_USERNAME=framework\nDB_PASSWORD=replace-with-a-strong-password\n\nSESSION_DRIVER=redis\nSESSION_STORE=backend\nCACHE_STORE=backend\nREDIS_CLIENT=phpredis\nREDIS_HOST=127.0.0.1\nREDIS_PORT=6379\nREDIS_PASSWORD=null\nQUEUE_CONNECTION=sync\nSESSION_SECURE_COOKIE=false")],
                ['配置本地域名和 Nginx', '<p>在运行浏览器的机器上，将 <code>127.0.0.1 a.base.local p.base.local www.base.local</code> 添加到 hosts 文件。使用 WSL2、虚拟机或远程开发机时，应将这些域名指向实际运行 Nginx 的机器。</p><p>仓库已提供适用于 <code>/www/framework</code> 和 PHP 8.4 的 Nginx 配置。启用后先检查配置再重新加载；项目目录不同时，需要同步修改 <code>root</code>。</p>'.$this->code("sudo cp /www/framework/nginx.conf /etc/nginx/sites-available/framework.conf\nsudo ln -s /etc/nginx/sites-available/framework.conf /etc/nginx/sites-enabled/framework.conf\nsudo nginx -t\nsudo systemctl reload nginx")],
                ['初始化应用', '<p>允许 PHP-FPM 写入存储和缓存目录，然后执行数据库迁移与种子、创建公开存储链接并构建前后台资源。不要将整个项目目录设置为任何人可写。</p>'.$this->code("cd /www/framework\nsudo chgrp -R www-data storage bootstrap/cache\nsudo chmod -R g+rwX storage bootstrap/cache\nphp artisan migrate --seed --no-interaction\nphp artisan storage:link --no-interaction\nnpm run build").'<p>开发和测试种子会创建管理员 <code>admin@framework.local</code>，初始密码为 <code>12345qwert</code>。登录地址为 <code>http://a.base.local/login</code>，首次登录后应立即修改密码。生产环境应使用 <code>php artisan backend:create-admin</code> 创建管理员。</p>'],
                ['启动开发服务', '<p>Nginx 运行时，使用 <code>npm run dev</code> 开启热更新。后台 Vite 服务端口为 5174，前台为 5175；页面仍通过配置的本地域名访问。需要验证构建产物时，改用 <code>npm run build</code>。</p>'.$this->code("npm run dev\n# 也可以分别构建前后台资源：\nnpm run build:backend\nnpm run build:frontend").'<p>临时不使用 Nginx 时，在两个终端分别运行 <code>composer run dev</code> 和 <code>npm run dev</code>，并在应用地址后使用 8000 端口。仅在开发相关功能时启动队列 Worker 和调度器。</p>'.$this->code("php artisan queue:work\nphp artisan queue:work ai\nphp artisan queue:work maintenance\nphp artisan queue:work file-sync\nphp artisan schedule:work")],
                ['验证安装并运行测试', '<p>检查应用信息、迁移状态、PHP 平台要求和 HTTP 健康端点。测试使用独立的 MySQL 数据库 <code>framework_test</code>，先创建该库并向同一开发账号授权，再运行测试。不要将测试指向开发库或生产库。</p>'.$this->code("php artisan about\nphp artisan migrate:status\ncomposer check-platform-reqs\ncurl -I http://www.base.local/up\n\n-- 先通过 sudo mysql 执行：\nCREATE DATABASE `framework_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL PRIVILEGES ON `framework_test`.* TO 'framework'@'127.0.0.1';\n\nphp artisan test --compact\nvendor/bin/pint --dirty --format agent")],
            ], 10),
            $this->document('development', 'project-structure', 'getting-started', 'Project Structure', '项目结构', 'Understand the Laravel, backend SPA and frontend SPA boundaries.', '了解 Laravel、后台 SPA 与前台 SPA 的目录边界。', [
                ['Backend application', '<p>Controllers, Requests, Resources and Services are grouped by area and business model under <code>app/Http</code> and <code>app/Services</code>. Models live under <code>app/Models</code>. Backend routes are in <code>routes/backend.php</code> and <code>routes/backend/system.php</code>.</p>'],
                ['Vue sources and assets', '<p>Backend Vue code belongs in <code>resources/js/backend</code>; public member pages belong in <code>resources/js/frontend</code>. Their styles use the matching <code>resources/css</code> directories. Only Vite output is stored in <code>public/build</code>; never edit generated assets.</p>'],
            ], [
                ['后台应用', '<p>控制器、请求、资源和服务按区域及业务模型组织在 <code>app/Http</code> 与 <code>app/Services</code> 中，模型位于 <code>app/Models</code>。后台路由位于 <code>routes/backend.php</code> 和 <code>routes/backend/system.php</code>。</p>'],
                ['Vue 源码与构建产物', '<p>后台 Vue 源码位于 <code>resources/js/backend</code>，前台会员页面位于 <code>resources/js/frontend</code>，样式使用对应的 <code>resources/css</code> 目录。<code>public/build</code> 只保存 Vite 产物，不要直接修改。</p>'],
            ], 20),
            $this->document('development', 'application-layers', 'architecture', 'Application Layers', '应用分层', 'Keep HTTP, validation, business workflows and serialization in their own layers.', '让 HTTP、校验、业务流程和序列化各归其层。', [
                ['Request to response', '<p>Controllers constructor-inject services, accept validated input and return Resources or JSON responses. Requests own validation. Services own transactions and domain rules and must not depend on FormRequest or return HTTP responses. Resources define the external field contract.</p>'],
                ['Models and persistence', '<p>Use Eloquent relationships and explicit foreign keys where model naming cannot infer them. Business writes use transactions, and backend changes record the existing Audit event. Keep initial data in seeders and schema definitions in migrations.</p>'.$this->code("public function create(ContentDocumentRequest \$request): JsonResponse\n{\n    return apiSuccess(new ContentDocumentResource(\n        \$this->service->create(\$request->validated())\n    ), status: 201);\n}")],
            ], [
                ['从请求到响应', '<p>控制器通过构造函数注入服务，只接收已校验输入并返回 Resource 或 JSON。Request 负责校验，Service 负责事务和领域规则，不依赖 FormRequest，也不返回 HTTP 响应；Resource 定义外部字段契约。</p>'],
                ['模型与持久化', '<p>使用 Eloquent 关联，模型名无法推断外键时显式指定。业务写入使用事务，后台变更记录现有 Audit 审计。初始数据放在 Seeder，表结构放在 Migration。</p>'.$this->code("public function create(ContentDocumentRequest \$request): JsonResponse\n{\n    return apiSuccess(new ContentDocumentResource(\n        \$this->service->create(\$request->validated())\n    ), status: 201);\n}")],
            ], 10),
            $this->document('development', 'routing-localization-testing', 'architecture', 'Routing, Localization and Testing', '路由、多语言与测试', 'Follow the shared API, translation and verification conventions.', '遵循统一的 API、翻译和验证约定。', [
                ['Routes and JSON', '<p>Backend reads use GET and writes use POST with explicit action names. Route names mirror menu permissions. Use <code>apiSuccess</code>, <code>apiError</code> and <code>apiPaginate</code> for responses. Frontend code obtains endpoint URLs from named routes rather than duplicating them.</p>'],
                ['Translations and tests', '<p>Translatable business fields use JSON columns ending in <code>_i18n</code>. English is required as the source translation; display accessors handle locale fallback. Backend module locales sit beside their pages in all six configured languages. Run focused PHPUnit tests and Pint, then build affected Vite bundles.</p>'.$this->code("php artisan test --compact tests/Feature/DocumentManagementTest.php\nvendor/bin/pint --dirty --format agent\nnpm run build")],
            ], [
                ['路由与 JSON', '<p>后台读取使用 GET，写入使用 POST 和显式动作名，路由名与菜单权限保持一致。响应统一使用 <code>apiSuccess</code>、<code>apiError</code> 和 <code>apiPaginate</code>，前端通过命名路由获取地址，不重复维护端点。</p>'],
                ['翻译与测试', '<p>可翻译业务字段使用以 <code>_i18n</code> 结尾的 JSON 列，英文为必填源语言，显示访问器负责语言回退。后台模块文案按六种配置语言放在页面旁。提交前运行聚焦的 PHPUnit 测试、Pint 和受影响的 Vite 构建。</p>'.$this->code("php artisan test --compact tests/Feature/DocumentManagementTest.php\nvendor/bin/pint --dirty --format agent\nnpm run build")],
            ], 20),
            $this->document('development', 'field-system', 'architecture', 'Field System', '字段系统', 'Extend field types and keep backend metadata, frontend components, validation and dependencies aligned.', '扩展字段类型，并保持后端元数据、前端组件、校验与依赖规则一致。', [
                ['Architecture and storage', '<p><code>FieldRegistry</code> is the backend source for field keys, components, data types, multiple-selection support and validation schemas. <code>FieldRenderer</code> resolves the frontend component, while <code>components/Fields/value.js</code> provides the shared storage-key and value-validation contract.</p><ul><li><code>text</code> and single <code>select</code>/<code>radio</code> use <code>value_string</code>.</li><li><code>number</code> uses <code>value_number</code>; <code>switch</code> uses <code>value_boolean</code>.</li><li><code>localized_text</code> uses <code>value_i18n</code>; checkbox and multiple select use <code>value_json</code>.</li></ul><p>Values are encrypted at rest. The runtime store revalidates saved and default values before returning them.</p>'],
                ['Required rules and options', '<p>The backend Resource computes <code>required</code> from the field type and normalized validation. Text uses minimum length, multiple choice uses minimum selections, localized text uses required locales, and number, radio, switch and single select are required. Frontend required indicators and clearable behavior must use this value.</p><p>Options can be manual, constant or database-backed. Register shared sources in <code>FieldOptionServiceProvider</code> and reference the exact registered key. An invalid source produces <code>options_error</code> and a visible error instead of a silently empty control.</p>'],
                ['Option cache', '<p>The Field module does not depend on setting models. Constant and database option results are cached by source, locale, scope, actor and trusted parameters; manual options are resolved directly. Configure the store and TTL in <code>config/fields.php</code>. Call <code>OptionSourceRegistry::forget()</code> after changing source data. The shared <code>App\Libs\Common\VersionedCache</code> provides versioned keys and TTL handling for any module without cache tags; modules only provide business keys and value resolvers.</p>'],
                ['Field dependencies', '<p>Use <code>depends_on</code> and <code>depends_value</code> to make a setting depend on another non-secret setting in the same group. An empty expected value accepts any nonempty value, including <code>false</code> and <code>0</code>. Arrays and translation dictionaries match when any item equals the expected value.</p><p>Dependencies control configuration-page visibility and are checked again before saving. They do not disable an existing runtime value. Self references, cycles, cross-group references and secret sources are rejected. Definitions with dependents cannot be deleted, renamed or moved to another group.</p>'],
                ['Register a new field type', '<p>A new type requires both backend and frontend registration. Implement every <code>FieldType</code> method, including required-state calculation, and provide a validator. Register the frontend async component before rendering forms that use it.</p>'.$this->code("\$fieldRegistry->register(new CustomField);\n\nregisterFieldComponent('custom-component', () => import('./CustomField.vue'));").'<p>Also add storage-key mapping, validation metadata, six-locale labels, default-value handling and contract tests. The backend remains the final validation authority; client checks only improve feedback.</p>'],
                ['Verification', '<p>Run the field contract tests, related setting tests and the backend frontend build after changing the field protocol.</p>'.$this->code("php artisan test --compact tests/Feature/FieldSystemTest.php\nphp artisan test --compact tests/Feature/FrameworkExtensionTest.php\nphp artisan test --compact tests/Feature/SystemSettingTest.php\nvendor/bin/pint --dirty --format agent\nnpm run build:backend")],
            ], [
                ['架构与存储', '<p><code>FieldRegistry</code> 是后端字段标识、组件、数据类型、多选能力和校验规则的来源。<code>FieldRenderer</code> 负责解析前端组件，<code>components/Fields/value.js</code> 统一维护存储键和值校验契约。</p><ul><li><code>text</code> 与单选 <code>select</code>/<code>radio</code> 使用 <code>value_string</code>。</li><li><code>number</code> 使用 <code>value_number</code>，<code>switch</code> 使用 <code>value_boolean</code>。</li><li><code>localized_text</code> 使用 <code>value_i18n</code>，复选框和多选下拉使用 <code>value_json</code>。</li></ul><p>设置值加密存储，运行时读取保存值或默认值前会重新校验。</p>'],
                ['必填规则与选项', '<p>后端 Resource 根据字段类型和规范化校验规则计算 <code>required</code>。文本看最小长度，多选看最少选择数，多语言文本看必填语言；数字、单选按钮、开关和单选下拉均为必填。前端必填标识和清空行为必须使用这一字段。</p><p>选项支持自定义、常量和数据库来源。共享来源在 <code>FieldOptionServiceProvider</code> 注册，引用时必须使用准确的注册键。无效来源会返回 <code>options_error</code> 并显示明确错误，不再静默呈现空控件。</p>'],
                ['选项缓存', '<p>Field 模块不依赖设置模型。常量与数据库选项按来源、语言、scope、actor 和可信参数缓存，自定义选项直接解析。缓存驱动与 TTL 在 <code>config/fields.php</code> 配置。来源数据变化后调用 <code>OptionSourceRegistry::forget()</code>。共享 <code>App\Libs\Common\VersionedCache</code> 为任意模块提供版本键与 TTL 处理，不依赖 Cache tags；模块只需提供业务键和值加载逻辑。</p>'],
                ['字段依赖', '<p>通过 <code>depends_on</code> 和 <code>depends_value</code>，让配置项依赖同分组内的另一个非敏感配置。依赖值留空表示接受任意非空值，其中 <code>false</code> 和 <code>0</code> 也是有效值。数组和多语言字典在任一值匹配时满足依赖。</p><p>依赖控制配置页面的显示，并在保存前由后端再次检查；它不会禁用已有的运行时配置值。禁止自引用、循环、跨分组和敏感字段依赖。仍被依赖的定义不能删除、改名或移动到其他分组。</p>'],
                ['注册新字段类型', '<p>新类型必须同时完成后端和前端注册。实现 <code>FieldType</code> 的全部方法（包括必填计算），提供对应 Validator，并在渲染相关表单前注册前端异步组件。</p>'.$this->code("\$fieldRegistry->register(new CustomField);\n\nregisterFieldComponent('custom-component', () => import('./CustomField.vue'));").'<p>同时补齐存储键映射、校验元数据、六语言文案、默认值处理和契约测试。后端始终是最终校验方，前端校验只用于改善反馈。</p>'],
                ['验证', '<p>修改字段协议后，运行字段契约测试、相关设置测试和后台前端构建。</p>'.$this->code("php artisan test --compact tests/Feature/FieldSystemTest.php\nphp artisan test --compact tests/Feature/FrameworkExtensionTest.php\nphp artisan test --compact tests/Feature/SystemSettingTest.php\nvendor/bin/pint --dirty --format agent\nnpm run build:backend")],
            ], 30),
            $this->document('development', 'authentication-and-acl', 'backend-modules', 'Authentication and ACL', '认证与访问控制', 'Work with backend guards, departments, menus and named-route permissions.', '了解后台 Guard、部门、菜单与命名路由权限。', [
                ['Backend middleware', '<p>The backend uses <code>backend.auth</code>, <code>backend.permission</code>, locale, logging and security-password middleware. ACL resolves the current named route against <code>admin_menus.permission</code> or its aliases. Board members retain full access; ordinary accounts require an unambiguous grant.</p>'],
                ['Department scope', '<p>Department grants intersect ancestor grants. Leaders only manage their subtree and cannot manage their own account or peer leaders. Preserve the protected board and first super administrator, and never introduce role-based authorization.</p>'],
            ], [
                ['后台中间件', '<p>后台使用 <code>backend.auth</code>、<code>backend.permission</code>、语言、日志和安全密码中间件。ACL 根据 <code>admin_menus.permission</code> 或路由别名解析当前命名路由。董事会成员拥有全部权限，普通账号必须获得明确且无歧义的授权。</p>'],
                ['部门范围', '<p>部门权限与所有祖先部门权限求交集。负责人只能管理部门子树，不能管理本人或同级负责人。保护董事会和首位超级管理员，不要引入角色权限体系。</p>'],
            ], 10),
            $this->document('development', 'menu-management', 'backend-modules', 'Menus and Permissions', '菜单与权限', 'Register backend pages and button permissions through discovery.', '通过自动发现注册后台页面与按钮权限。', [
                ['Attribute discovery', '<p>Use <code>MenuPage</code> on a backend controller and <code>MenuAction</code> on endpoint methods. Permission identifiers use directory, module.resource and module.resource.action forms. The component path must point to an existing module <code>List.vue</code>.</p>'],
                ['Seed and navigation', '<p>AdminMenuSeeder creates top-level directories and invokes MenuDiscovery. Repeated seeding fills missing translations without replacing custom hierarchy. The SPA builds business routes from authenticated userinfo menus, so no duplicate frontend route table is required.</p>'],
            ], [
                ['属性自动发现', '<p>在后台控制器上使用 <code>MenuPage</code>，在接口方法上使用 <code>MenuAction</code>。权限标识采用目录、module.resource、module.resource.action 形式，组件路径必须指向真实存在的模块 <code>List.vue</code>。</p>'],
                ['播种与导航', '<p>AdminMenuSeeder 创建顶级目录并调用 MenuDiscovery。重复播种只补充缺失翻译，不替换自定义层级。SPA 根据登录用户信息中的菜单构建业务路由，无需重复维护前端路由表。</p>'],
            ], 20),
            $this->document('development', 'members-and-wallets', 'backend-modules', 'Members and Wallets', '会员与钱包', 'Understand member restrictions, multi-currency balances and transaction records.', '了解会员限制、多币种余额和账变记录。', [
                ['Member accounts', '<p>Member users keep authentication identity separate from profiles. Restrictions are configured in <code>config/member.php</code>; login restrictions can revoke sessions. Status changes, verification and administrative actions are recorded in user logs and backend audit records.</p>'],
                ['Wallet transactions', '<p>Wallet currencies and precision are configured under <code>member.wallet</code>. Credit and debit operations run through the shared member wallet service in transactions. Each operation records direction, amount, type, reference, actor and resulting balance; do not update balances directly.</p>'],
            ], [
                ['会员账户', '<p>会员认证身份与资料分离。限制类型配置在 <code>config/member.php</code>，登录限制可撤销会话。状态变更、邮箱验证和管理员操作会写入会员日志与后台审计。</p>'],
                ['钱包账变', '<p>钱包币种和精度配置在 <code>member.wallet</code> 下。充值与扣款必须通过共享会员钱包服务在事务中执行，每次操作记录方向、金额、类型、关联号、操作人和变更后余额，不要直接修改余额字段。</p>'],
            ], 30),
            $this->document('development', 'content-and-documents', 'backend-modules', 'Content and Documents', '内容与文档模块', 'Manage localized content and expose only published documentation.', '管理多语言内容，并仅公开已发布文档。', [
                ['Content models', '<p>Announcements, carousels, document categories and documents use the Content model namespace. Carousel files use the existing file-reference service. Category deletion is blocked while children or documents exist, and parent/category types must match.</p>'],
                ['Public documentation', '<p>The public navigation and detail endpoints only return active documents whose publication time has arrived. Disabled categories and future or draft documents are excluded. Editors manage complete translation dictionaries; public resources return localized display values.</p>'],
            ], [
                ['内容模型', '<p>公告、轮播、文档分类和文档使用 Content 模型命名空间。轮播图片使用现有文件引用服务；分类仍有子分类或文档时禁止删除，父分类和文档类型必须一致。</p>'],
                ['公开文档', '<p>公开导航和详情接口只返回已启用且到达发布时间的文档，停用分类、未来发布和草稿不会公开。后台编辑完整翻译字典，公开 Resource 返回当前语言的显示值。</p>'],
            ], 40),
            $this->document('development', 'files-and-storage', 'operations', 'Files and Storage', '文件与存储', 'Use the file manager, storage accounts and reference tracking.', '使用文件管理器、存储账号和引用追踪。', [
                ['Uploads and accounts', '<p>File uploads are handled by the existing FileManager and UploadConfiguration. Storage accounts define disks and synchronization behavior. Keep credentials in environment-backed configuration and never expose secret keys in Resources or frontend state.</p>'],
                ['Module boundary and cache', '<p>Storage providers and <code>StorageRegistry</code> depend on the generic <code>StorageAccount</code> DTO and <code>StorageConfigurationRepository</code>, not Eloquent models. <code>EloquentStorageConfigurationRepository</code> is the project adapter. Upload settings and non-sensitive provider summaries are cached; access keys and secrets are never written to the shared cache.</p><p>Configure the store and TTL in <code>config/files.php</code>. Saved or deleted configuration models invalidate the versioned cache automatically. Disk instances are reused only within the current Registry instance. Custom persistence can implement the repository interface without changing storage providers.</p>'],
                ['References and cleanup', '<p>Models using files declare their reference fields so shared files cannot be removed while in use. Replacing an image releases the old reference without deleting the physical shared file. Use the provided reference rebuild command only when repair is necessary.</p>'],
            ], [
                ['上传与存储账号', '<p>文件上传统一经过 FileManager 和 UploadConfiguration，存储账号定义磁盘与同步行为。凭证放在环境配置中，不要通过 Resource 或前端状态泄露密钥。</p>'],
                ['模块边界与缓存', '<p>存储提供方和 <code>StorageRegistry</code> 只依赖通用 <code>StorageAccount</code> DTO 与 <code>StorageConfigurationRepository</code>，不直接依赖 Eloquent 模型；<code>EloquentStorageConfigurationRepository</code> 是项目适配器。上传设置和非敏感的 Provider 汇总使用缓存，访问密钥和 Secret 不写入共享缓存。</p><p>缓存驱动与 TTL 在 <code>config/files.php</code> 配置。配置模型保存或删除时自动让版本缓存失效，磁盘实例仅在当前 Registry 实例内复用。自定义持久化只需实现 Repository 接口，无需修改存储提供方。</p>'],
                ['引用与清理', '<p>使用文件的模型声明引用字段，共享文件被引用时不能删除。替换图片会释放旧引用，但不会直接删除物理共享文件。仅在需要修复时使用现有引用重建命令。</p>'],
            ], 10),
            $this->document('development', 'queues-and-schedules', 'operations', 'Queues and Schedules', '队列与计划任务', 'Operate background jobs, retries and scheduled task runs.', '管理后台任务、失败重试和计划任务执行记录。', [
                ['Queues', '<p>The queue module shows pending and failed jobs, details, individual or batch retry and deletion. Use <code>QUEUE_CONNECTION=sync</code> in tests. Production workers and retry policy belong to deployment configuration.</p>'],
                ['Schedules', '<p>Scheduled tasks have definitions, settings and run history. <code>system-schedule:dispatch</code> dispatches due tasks; prune commands clean old notifications, schedule logs and AI receipts. Use the backend run action for controlled manual execution and inspect run details before changing a task.</p>'.$this->code('php artisan system-schedule:dispatch')],
            ], [
                ['队列', '<p>队列模块展示等待中和失败任务、详情、单个或批量重试及删除。测试使用 <code>QUEUE_CONNECTION=sync</code>，生产 Worker 与重试策略由部署配置负责。</p>'],
                ['计划任务', '<p>计划任务包含定义、设置和执行记录。<code>system-schedule:dispatch</code> 分发到期任务，清理命令处理旧通知、任务日志和 AI 回执。手动执行前检查配置，异常时先查看执行详情。</p>'.$this->code('php artisan system-schedule:dispatch')],
            ], 20),
            $this->document('development', 'cache-management', 'operations', 'Cache Management', '缓存管理', 'Reuse versioned caching and expose module-owned invalidation in the backend.', '复用版本缓存，并将模块自有的失效能力接入后台。', [
                ['Reusable cache', '<p>Use <code>App\Libs\Common\VersionedCache</code> for arbitrary cached values. Inject a Laravel cache Repository, a unique module prefix and a TTL. <code>remember()</code> accepts a business key and resolver; TTL zero bypasses caching. <code>forget()</code> advances only the current prefix version, and old entries expire through the TTL.</p>'.$this->code("\$cache = new VersionedCache(\$repository, 'module-name', 300);\n\$value = \$cache->remember('business-key', fn () => loadValue());\n\$cache->forget();")],
                ['Register backend entries', '<p>Modules register their own group and cache entries through <code>CacheGroupRegistry</code>. Provide localized labels, store, TTL and an invalidation callback. The backend only lists registered entries and never accepts arbitrary cache keys or performs a global flush.</p>'.$this->code("\$cacheRegistry->registerGroup('module', \$groupLabels);\n\$cacheRegistry->register('module.entries', 'module', \$labels, \$descriptions, 'redis', 300, fn () => \$moduleCache->forget());")],
                ['Operations and safety', '<p>The cache management page groups entries by module and shows store and TTL. Manual invalidation is board-only, rate-limited, audited and protected by the configured security-password workflow. Keep credentials and sensitive payloads out of cached values and labels. Run <code>CacheManagementTest</code> and <code>VersionedCacheTest</code> after changing registration or invalidation.</p>'],
            ], [
                ['通用缓存', '<p>任意模块都可使用 <code>App\Libs\Common\VersionedCache</code>。注入 Laravel 缓存 Repository、唯一模块前缀和 TTL；<code>remember()</code> 接收业务键和加载逻辑，TTL 为 0 时不缓存。<code>forget()</code> 只递增当前前缀版本，旧条目通过 TTL 过期。</p>'.$this->code("\$cache = new VersionedCache(\$repository, 'module-name', 300);\n\$value = \$cache->remember('business-key', fn () => loadValue());\n\$cache->forget();")],
                ['注册后台缓存项', '<p>模块通过 <code>CacheGroupRegistry</code> 注册自己的分组和缓存项，提供多语言名称、驱动、TTL 与失效回调。后台只展示注册项，不接受任意缓存键，也不执行全局清空。</p>'.$this->code("\$cacheRegistry->registerGroup('module', \$groupLabels);\n\$cacheRegistry->register('module.entries', 'module', \$labels, \$descriptions, 'redis', 300, fn () => \$moduleCache->forget());")],
                ['运维与安全', '<p>缓存管理页面按模块分组并展示驱动和 TTL。手动失效仅限董事会成员，具有限流、审计和安全密码保护。缓存值与标签不得包含凭据或敏感内容。修改注册或失效逻辑后，运行 <code>CacheManagementTest</code> 和 <code>VersionedCacheTest</code>。</p>'],
            ], 30),
            $this->document('usage', 'registration-and-login', 'member-center', 'Registration and Login', '注册与登录', 'Create an account, sign in and recover access.', '注册账户、登录并找回访问权限。', [
                ['Create and use an account', '<p>Register with the required account information, then sign in with your email and password. The member center keeps the same session across account, wallet and notification pages. Email verification may be required before sensitive operations.</p>'],
                ['Recovery and connected accounts', '<p>Use password reset if you cannot sign in. Supported OAuth providers can be connected or disconnected from account security settings. Keep at least one usable sign-in method and review active restrictions if access is denied.</p>'],
            ], [
                ['注册与使用账户', '<p>填写所需账户信息完成注册，然后使用邮箱和密码登录。会员中心在资料、钱包和通知页面间保持同一会话，敏感操作前可能需要完成邮箱验证。</p>'],
                ['找回与第三方账户', '<p>无法登录时使用密码重置。可在账户安全设置中绑定或解绑已支持的 OAuth 平台，至少保留一种可用登录方式；访问被拒绝时检查账户限制状态。</p>'],
            ], 10),
            $this->document('usage', 'profile-and-security', 'member-center', 'Profile and Security', '资料与账户安全', 'Update your profile, avatar, email and password safely.', '安全地维护资料、头像、邮箱和密码。', [
                ['Profile', '<p>Open the member center to update your nickname and avatar. Personal identifiers such as username and email are authentication fields rather than translatable profile content. Changes are saved to the current account only.</p>'],
                ['Security', '<p>Use a unique password and verify new email addresses. Password or email changes may require the current password or verification. Sign out other sessions if you suspect unauthorized access and contact an administrator when a restriction remains.</p>'],
            ], [
                ['个人资料', '<p>进入会员中心可修改昵称和头像。用户名、邮箱等属于认证字段，不是多语言资料。所有修改仅作用于当前登录账户。</p>'],
                ['账户安全', '<p>使用独立密码并完成新邮箱验证。修改密码或邮箱可能需要当前密码或验证信息。怀疑账户被他人使用时退出其他会话；限制仍未解除时联系管理员。</p>'],
            ], 20),
            $this->document('usage', 'wallet-and-notifications', 'member-center', 'Wallet and Notifications', '钱包与通知', 'Review balances, transactions and notification preferences.', '查看余额、账变和通知偏好。', [
                ['Wallet', '<p>The wallet page lists balances by currency, recent transactions and daily statistics. Amounts use the precision configured for each currency. Transaction history is read-only for members; contact support if an adjustment needs review.</p>'],
                ['Notifications', '<p>Open the notification center to read items, mark one or all as read and update preferences. Operational messages may include account, wallet or system events. Notification preferences do not change security restrictions.</p>'],
            ], [
                ['钱包', '<p>钱包页面按币种显示余额、最近账变和每日统计，金额精度由币种配置决定。会员账变记录为只读，如需核对调整请联系支持人员。</p>'],
                ['通知', '<p>通知中心支持查看消息、单条或全部标记已读以及修改通知偏好。消息可包含账户、钱包或系统事件，通知偏好不会改变账户安全限制。</p>'],
            ], 30),
            $this->document('usage', 'backend-overview', 'administration', 'Backend Workspace', '后台工作台', 'Navigate the backend, language options and personal security settings.', '了解后台导航、语言选项和个人安全设置。', [
                ['Navigation', '<p>The backend menu is generated from your effective permissions. Direct links, browser history and session-expiry redirects use the same SPA shell. A page hidden by permissions cannot be opened by entering its URL manually.</p>'],
                ['Preferences and security', '<p>Use the profile area to update account details, login password and security password. Theme, density, radius and button-size preferences affect the console appearance. Language changes reload the backend entry so server and module translations stay aligned.</p>'],
            ], [
                ['后台导航', '<p>后台菜单根据有效权限生成，直达链接、浏览器历史和会话过期跳转共用同一 SPA 外壳。没有权限的页面即使手动输入地址也无法访问。</p>'],
                ['偏好与安全', '<p>在个人资料中维护账户信息、登录密码和安全密码。主题、密度、圆角和按钮大小只影响控制台外观。切换语言会重新加载后台入口，确保服务端与模块翻译一致。</p>'],
            ], 10),
            $this->document('usage', 'departments-and-permissions', 'administration', 'Departments and Permissions', '部门与权限', 'Manage department leadership, subtree scope and effective grants.', '管理部门负责人、子树范围和有效权限。', [
                ['Department governance', '<p>The board is the unique root department. A department may be created without a leader, but once leaders are assigned it retains one or two active leaders. Locking and governance checks prevent removing the last leader or managing peer accounts.</p>'],
                ['Permission grants', '<p>Only the board edits global menus. Leaders manage users in their subtree and delegate grants to direct child departments. Effective permissions intersect every ancestor grant; revoked ancestor permissions are pruned from descendants and do not silently return after regranting.</p>'],
            ], [
                ['部门治理', '<p>董事会是唯一根部门。创建部门时可暂不设置负责人，但分配后必须保留一至两名启用负责人。治理锁与检查会阻止移除最后负责人或管理同级账号。</p>'],
                ['权限分配', '<p>只有董事会可编辑全局菜单。负责人管理子树用户，并仅向直接子部门授权。有效权限与所有祖先权限求交集；祖先权限被撤销后会从后代清理，重新授权时不会自动恢复旧授权。</p>'],
            ], 20),
            $this->document('usage', 'administrator-management', 'administration', 'Administrator Management', '管理员管理', 'Create and maintain administrator accounts within your scope.', '在权限范围内创建和维护管理员账户。', [
                ['Accounts', '<p>Administrators sign in with email. Create and edit only the fields exposed by the current form; leader assignment belongs to department management. Ordinary employees cannot grant themselves department or administrator mutation permissions.</p>'],
                ['Passwords and deletion', '<p>Use the dedicated password action for resets and complete security-password confirmation when requested. Disable an account before deletion. Protected board membership and the first super administrator cannot be removed through ordinary management actions.</p>'],
            ], [
                ['账户管理', '<p>管理员使用邮箱登录，只编辑当前表单开放的字段，负责人分配在部门管理中完成。普通员工不能为自己授予部门或管理员变更权限。</p>'],
                ['密码与删除', '<p>通过独立密码操作重置密码，并按要求完成安全密码确认。删除前必须先停用账户。董事会保护成员和首位超级管理员不能通过普通管理操作删除。</p>'],
            ], 30),
            $this->document('usage', 'member-administration', 'administration', 'Member Administration', '会员管理', 'Manage member status, restrictions, VIP data and wallet adjustments.', '管理会员状态、限制、VIP 数据和钱包调整。', [
                ['Member records', '<p>Search by account information and filter by status, test-user flag, verification or VIP level. Details, settings, status and logs have separate permissions. Use restriction types deliberately and review immutable audit actor snapshots when tracing changes.</p>'],
                ['Wallet operations', '<p>Credit and debit actions require their own permissions. Select currency, amount and transaction type carefully and add a useful reference or remark. Review the transaction history and daily statistics after saving; never treat a wallet adjustment as a password or status change.</p>'],
            ], [
                ['会员记录', '<p>可按账户信息搜索，并按状态、测试用户、认证状态或 VIP 等级筛选。详情、设置、状态和日志分别授权。谨慎使用限制类型，追溯变更时查看不可变的操作人审计快照。</p>'],
                ['钱包操作', '<p>充值和扣款分别需要独立权限。仔细选择币种、金额和账变类型，并填写有用的关联号或备注。保存后检查账变历史与每日统计，不要把钱包调整当作密码或状态操作。</p>'],
            ], 40),
            $this->document('usage', 'documentation-management', 'content-management', 'Documentation Management', '文档管理', 'Organize development and user documentation for the public documentation site.', '组织开发和使用文档并发布到前台文档站。', [
                ['Categories and articles', '<p>Create development or usage categories and arrange them into a hierarchy. Documents require a matching category type, a unique slug within their type and an English title and body. Summary, publication time, sort and active status control presentation and ordering.</p>'],
                ['Translate and publish', '<p>English is the source translation; add other languages through the language tabs. Use headings, links, inline code and code blocks for readable guides. Preview before publishing. Draft, disabled or future-dated content is excluded from public navigation and detail pages.</p>'],
            ], [
                ['分类与文章', '<p>创建开发或使用文档分类并组织层级。文档必须选择相同类型的分类，标识在类型内唯一，英文标题和正文必填。摘要、发布时间、排序和启用状态共同控制展示与顺序。</p>'],
                ['翻译与发布', '<p>英文为源语言，通过语言标签维护其他翻译。合理使用标题、链接、行内代码和代码块，发布前先预览。草稿、停用或未来发布的内容不会出现在公开导航和详情页。</p>'],
            ], 10),
            $this->document('usage', 'content-and-file-management', 'content-management', 'Content and File Management', '内容与文件管理', 'Publish announcements, carousels and shared files.', '发布公告、轮播和共享文件。', [
                ['Content publishing', '<p>Announcements support localized title and body, publication time, ordering and status. Carousels additionally require an image and may include a link. Use preview-safe URLs and keep English source content complete before enabling a record.</p>'],
                ['Files and storage', '<p>Upload allowed file types through the file library and manage storage accounts separately. Files referenced by content are protected from deletion. Storage synchronization and retry actions are operational tasks; check account settings and task results before retrying.</p>'],
            ], [
                ['内容发布', '<p>公告支持多语言标题与正文、发布时间、排序和状态；轮播还需要图片并可配置链接。启用前补全英文源内容并检查链接安全。</p>'],
                ['文件与存储', '<p>通过文件库上传允许的类型，存储账号单独管理。被内容引用的文件受删除保护。存储同步与重试属于运维操作，重试前先检查账号配置和任务结果。</p>'],
            ], 20),
            $this->document('usage', 'settings-and-maintenance', 'system-management', 'Settings and Maintenance', '设置与系统运维', 'Maintain settings, notification templates, queues and schedules.', '维护设置、通知模板、队列和计划任务。', [
                ['Settings and templates', '<p>Development-managed setting groups and definitions determine the editable system settings. Update values through the system settings page rather than changing definitions during routine operations. Notification templates use localized subjects and bodies and should be checked before enabling related workflows.</p>'],
                ['Queues and scheduled tasks', '<p>Inspect queue failures before retrying or deleting them. Scheduled task changes should include schedule, handler and enabled-state review. Use run history to confirm outcomes, and perform manual runs only when you understand the task side effects.</p>'],
            ], [
                ['设置与模板', '<p>开发管理中的设置分组和定义决定系统设置可编辑项。日常运维应在系统设置页修改值，而不是修改定义。通知模板使用多语言主题与正文，启用相关流程前先检查内容。</p>'],
                ['队列与计划任务', '<p>重试或删除失败队列前先检查异常。修改计划任务时核对调度、处理器和启用状态，通过执行记录确认结果；只有明确任务副作用时才手动执行。</p>'],
            ], 10),
        ];
    }

    private function document(string $type, string $slug, string $category, string $title, string $titleZh, string $summary, string $summaryZh, array $sections, array $sectionsZh, int $sort): array
    {
        return ['type' => $type, 'slug' => $slug, 'category' => $category, 'title' => $title, 'title_zh' => $titleZh, 'summary' => $summary, 'summary_zh' => $summaryZh, 'sections' => $sections, 'sections_zh' => $sectionsZh, 'sort' => $sort];
    }

    private function content(array $sections): string
    {
        return collect($sections)->map(fn (array $section): string => '<h2>'.$section[0].'</h2>'.$section[1])->implode('');
    }

    private function code(string $code): string
    {
        return '<pre><code>'.htmlspecialchars($code, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8').'</code></pre>';
    }
}
