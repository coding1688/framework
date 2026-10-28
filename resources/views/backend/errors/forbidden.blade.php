<!doctype html>
<html><head><meta charset="utf-8"><title>{{ __('backend/common.forbidden') }}</title></head>
<body>
<p>{{ __('backend/common.forbidden') }}</p>
<a href="{{ route('backend.home') }}">{{ __('backend/common.home') }}</a>
{{ Illuminate\Support\Facades\Vite::useHotFile(public_path('backend.hot'))->withEntryPoints(['resources/js/backend/forbidden.js'])->useBuildDirectory('build/backend') }}
</body></html>
