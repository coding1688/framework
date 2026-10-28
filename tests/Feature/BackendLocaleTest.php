<?php

namespace Tests\Feature;

use App\Http\Resources\Backend\Admin\AdminDepartmentResource;
use App\Models\Admin\AdminDepartment;
use Tests\TestCase;

class BackendLocaleTest extends TestCase
{
    public function test_only_the_six_supported_locales_are_configured(): void
    {
        $expected = ['zh_CN', 'zh_TW', 'en', 'th', 'ja', 'ko'];

        $this->assertSame($expected, array_keys(config('platform.locales')));
        $this->assertSame($expected, array_keys(config('platform.backend_ui.locales')));
    }

    public function test_thai_japanese_and_korean_are_accepted(): void
    {
        foreach (['th', 'ja', 'ko'] as $locale) {
            $this->withHeader('X-Lang', $locale)->get('http://a.base.local/login')->assertOk();

            $this->assertSame($locale, app()->getLocale());
            $this->assertSame($locale, session()->get('backend.locale'));
        }
    }

    public function test_retired_locale_falls_back_to_the_default(): void
    {
        $this->withHeader('X-Lang', 'tl')->get('http://a.base.local/login')->assertOk();

        $this->assertSame(config('app.locale'), app()->getLocale());
        $this->assertSame(config('app.locale'), session()->get('backend.locale'));
    }

    public function test_resources_hide_retired_translation_keys_without_deleting_model_data(): void
    {
        $department = new AdminDepartment([
            'name_i18n' => ['en' => 'Operations', 'th' => 'การดำเนินงาน', 'tl' => 'Operasyon'],
        ]);

        $data = (new AdminDepartmentResource($department))->toArray(request());

        $this->assertSame(['en' => 'Operations', 'th' => 'การดำเนินงาน'], $data['name_i18n']);
        $this->assertSame('Operasyon', $department->name_i18n['tl']);
    }

    public function test_all_frontend_namespaces_have_complete_six_locale_dictionaries(): void
    {
        $root = resource_path('js/backend');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'json' || ($file->getFilename() !== 'en.json' && basename($file->getPath()) !== 'en')) {
                continue;
            }
            $english = json_decode(file_get_contents($file->getPathname()), true, flags: JSON_THROW_ON_ERROR);
            $expected = array_keys($english);
            sort($expected);
            foreach (['zh_CN', 'zh_TW', 'en', 'th', 'ja', 'ko'] as $locale) {
                $path = $file->getFilename() === 'en.json'
                    ? $file->getPath().'/'.$locale.'.json'
                    : dirname($file->getPath()).'/'.$locale.'/'.$file->getFilename();
                $this->assertFileExists($path);
                $localized = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
                $actual = array_keys($localized);
                sort($actual);
                $this->assertSame($expected, $actual, $path);
            }
        }
    }

    public function test_all_backend_namespaces_have_complete_six_locale_dictionaries(): void
    {
        $root = lang_path('en');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $english = require $file->getPathname();
            $expected = $this->translationKeys($english);
            foreach (['zh_CN', 'zh_TW', 'en', 'th', 'ja', 'ko'] as $locale) {
                $path = lang_path($locale.'/'.$relative);
                $this->assertFileExists($path);
                $localized = require $path;
                $this->assertSame($expected, $this->translationKeys($localized), $path);
            }
        }
    }

    private function translationKeys(array $translations, string $prefix = ''): array
    {
        $keys = [];
        foreach ($translations as $key => $value) {
            $path = $prefix.$key;
            if (is_array($value)) {
                $keys = [...$keys, ...$this->translationKeys($value, $path.'.')];
            } else {
                $keys[] = $path;
            }
        }
        sort($keys);

        return $keys;
    }
}
