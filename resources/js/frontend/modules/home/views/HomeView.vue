<template>
    <section class="docs-home">
        <div class="docs-hero">
            <span class="docs-kicker">FRAMEWORK DOCUMENTATION</span>
            <h1>清晰、灵活的<br><em>应用开发框架。</em></h1>
            <p>从项目搭建、开发规范到 API 与部署，使用统一的 Laravel 和 Vue 技术栈，高效构建可持续扩展的 Web 应用。</p>
            <div class="docs-hero-actions"><a class="button button--primary" href="#getting-started">快速开始</a><a class="docs-text-link" href="#documentation">浏览核心文档 <span>→</span></a></div>
            <div class="docs-hero-meta"><span>Laravel</span><span>Vue 3</span><span>API First</span></div>
        </div>
        <section id="getting-started" class="docs-section">
            <div class="docs-section-heading"><span>GETTING STARTED</span><h2>从零开始，快速运行项目</h2><p>按照项目默认命令完成初始化，并分别启动后端和前端开发服务。</p></div>
            <div class="docs-quickstart">
                <article class="docs-step"><span class="docs-step-number">01</span><h3>初始化项目</h3><p>安装依赖、生成密钥并初始化数据库。</p><pre><code>composer run setup</code></pre></article>
                <article class="docs-step"><span class="docs-step-number">02</span><h3>启动后端服务</h3><p>运行 Laravel 开发服务器。</p><pre><code>composer run dev</code></pre></article>
                <article class="docs-step"><span class="docs-step-number">03</span><h3>启动前端资源</h3><p>启动前后台 Vite 开发服务。</p><pre><code>npm run dev</code></pre></article>
            </div>
        </section>
        <section id="documentation" class="docs-section">
            <div class="docs-section-heading"><span>DOCUMENTATION</span><h2>覆盖完整开发流程</h2><p>围绕实际项目结构组织文档，让每一层职责清晰、易于查找。</p></div>
            <div class="docs-cards">
                <article class="docs-card"><span>01</span><h3>应用结构</h3><p>了解目录划分、服务层、请求校验、资源配置及业务模块的组织方式。</p></article>
                <article class="docs-card"><span>02</span><h3>认证与用户</h3><p>使用注册、登录、账户安全、用户冻结和操作日志等完整账户能力。</p></article>
                <article class="docs-card"><span>03</span><h3>权限与菜单</h3><p>基于部门、菜单权限和命名路由构建清晰可控的后台访问体系。</p></article>
                <article class="docs-card"><span>04</span><h3>多端 API</h3><p>通过独立 JSON API 连接 Web 前台，并为后续 uni-app 手机端扩展保留边界。</p></article>
            </div>
        </section>
        <section id="guides" class="docs-guide">
            <div><span>DEVELOPMENT GUIDE</span><h2>约定优于重复实现</h2><p>遵循项目已有的技术边界，让新功能自然融入框架。</p></div>
            <ul><li>业务逻辑集中在服务层，控制器只处理 HTTP 输入输出</li><li>前后台 Vue 源码独立，通过 Vite 分别构建</li><li>业务接口保持一致的 JSON 响应与命名路由</li><li>数据库字段、请求校验和前端输入长度保持一致</li></ul>
        </section>
    </section>
</template>
