<?php

namespace Database\Seeders\common\location;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PhilippineLocationSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/common/location/philippines.json');
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (count($data['regions']) !== 18 || count($data['cities']) !== 1656) {
            throw new RuntimeException('The bundled Philippine PSGC dataset is incomplete.');
        }

        DB::transaction(function () use ($data): void {
            $now = now();
            DB::table('common_regions')->upsert(collect($data['regions'])->map(fn (array $row, int $sort): array => [
                'country_code' => 'PH',
                'code' => $row['code'],
                'name_i18n' => json_encode(['en' => $row['name']], JSON_UNESCAPED_UNICODE),
                'sort' => $sort,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(), ['code'], ['country_code', 'name_i18n', 'sort', 'active', 'updated_at']);
            $regionIds = DB::table('common_regions')->where('country_code', 'PH')->pluck('id', 'code');

            DB::table('common_provinces')->upsert(collect($data['provinces'])->map(fn (array $row, int $sort): array => [
                'region_id' => $regionIds[$row['region_code']],
                'code' => $row['code'],
                'type' => $row['type'],
                'name_i18n' => json_encode(['en' => $row['name']], JSON_UNESCAPED_UNICODE),
                'sort' => $sort,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(), ['code'], ['region_id', 'type', 'name_i18n', 'sort', 'active', 'updated_at']);
            $provinceIds = DB::table('common_provinces')->pluck('id', 'code');

            collect($data['cities'])->values()->map(fn (array $row, int $sort): array => [...$row, '_sort' => $sort])
                ->chunk(500)->each(function ($rows) use ($regionIds, $provinceIds, $now): void {
                    DB::table('common_cities')->upsert($rows->map(fn (array $row): array => [
                        'region_id' => $regionIds[$row['region_code']],
                        'province_id' => $provinceIds[$row['province_code']],
                        'code' => $row['code'],
                        'type' => $row['type'],
                        'name_i18n' => json_encode(['en' => $row['name']], JSON_UNESCAPED_UNICODE),
                        'sort' => $row['_sort'],
                        'active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(), ['code'], ['region_id', 'province_id', 'type', 'name_i18n', 'sort', 'active', 'updated_at']);
                });
        });
    }
}
