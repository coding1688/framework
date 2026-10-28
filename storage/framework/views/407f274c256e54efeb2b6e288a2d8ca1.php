<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo e(__('backend/layout.brand')); ?> · Framework</title>
    <script>window.backendConfig = <?php echo e(Illuminate\Support\Js::from(['locale' => app()->getLocale(), 'locales' => config('platform.locales'), 'translationLocales' => config('platform.locales'), 'interfaceLocales' => config('platform.backend_ui.locales'), 'ui' => config('platform.backend_ui'), 'routes' => app(App\Services\Backend\Common\BackendRoutes::class)->all(), 'pages' => ['home' => route('backend.home', [], false), 'login' => route('backend.login', [], false), 'profile' => route('backend.profile', [], false)]])); ?>;</script>
    <script>
    (() => {
        const root = document.documentElement;
        const theme = window.backendConfig.ui.theme;
        let preferences = {};
        try { preferences = JSON.parse(localStorage.getItem('backend:themePreferences') || '{}'); } catch {}
        const selected = key => theme.options[key].includes(preferences[key]) ? preferences[key] : theme.defaults[key];
        const selectedMode = selected('mode');
        const mode = selectedMode === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : selectedMode;
        root.dataset.theme = mode;
        root.dataset.preset = selected('preset');
        root.dataset.density = selected('density');
        root.dataset.radius = selected('radius');
        root.dataset.buttonSize = selected('button_size');
        root.style.colorScheme = mode;
    })();
    </script>
    <?php echo e(Illuminate\Support\Facades\Vite::useHotFile(public_path('backend.hot'))->withEntryPoints(['resources/js/backend/spa.js'])->useBuildDirectory('build/backend')); ?>

</head>
<body><div id="backend-app"></div><noscript><?php echo e(__('backend/common.javascript_required')); ?></noscript></body>
</html>
<?php /**PATH /www/framework/resources/views/backend/spa.blade.php ENDPATH**/ ?>