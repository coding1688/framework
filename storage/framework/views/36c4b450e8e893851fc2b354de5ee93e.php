<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#176b52">
    <title>用户中心 · <?php echo e(config('app.name')); ?></title>
    <script>window.frontendConfig = <?php echo e(Illuminate\Support\Js::from(['oauth' => ['google' => route('oauth.redirect', ['provider' => 'google']), 'facebook' => route('oauth.redirect', ['provider' => 'facebook'])], 'oauthBind' => ['google' => route('oauth.bind', ['provider' => 'google']), 'facebook' => route('oauth.bind', ['provider' => 'facebook'])]])); ?>;</script>
    <?php echo e(Illuminate\Support\Facades\Vite::useHotFile(public_path('frontend.hot'))->withEntryPoints(['resources/js/frontend/app.js'])->useBuildDirectory('build/frontend')); ?>

</head>
<body>
    <div id="frontend-app"></div>
    <noscript>请启用 JavaScript 后使用登录和注册功能。</noscript>
</body>
</html>
<?php /**PATH /www/framework/resources/views/frontend/app.blade.php ENDPATH**/ ?>