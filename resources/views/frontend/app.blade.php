<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#176b52">
    <meta name="description" content="Framework 框架开发文档，了解项目结构、快速开始、认证权限、前后台开发和多端 API。">
    <title>开发文档 · {{ config('app.name') }}</title>
    <script>window.frontendConfig = {{ Illuminate\Support\Js::from(['oauth' => ['google' => route('oauth.redirect', ['provider' => 'google']), 'facebook' => route('oauth.redirect', ['provider' => 'facebook'])], 'oauthBind' => ['google' => route('oauth.bind', ['provider' => 'google']), 'facebook' => route('oauth.bind', ['provider' => 'facebook'])]]) }};</script>
    {{ Illuminate\Support\Facades\Vite::useHotFile(public_path('frontend.hot'))->withEntryPoints(['resources/js/frontend/app.js'])->useBuildDirectory('build/frontend') }}
</head>
<body>
    <div id="frontend-app"></div>
    <noscript>请启用 JavaScript 后浏览首页及使用账户功能。</noscript>
</body>
</html>
