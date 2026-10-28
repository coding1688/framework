<?php

namespace Database\Factories\Common;

use App\Models\Common\FileStorageAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileStorageAccount>
 */
class FileStorageAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'storage_'.fake()->unique()->lexify('??????'), 'name_i18n' => ['en' => fake()->word()],
            'provider' => 'local', 'visibility' => 'private', 'enabled' => true,
            'configuration' => ['directory' => 'test_'.fake()->unique()->lexify('??????')], 'credentials' => [],
        ];
    }
}
