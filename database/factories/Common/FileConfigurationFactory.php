<?php

namespace Database\Factories\Common;

use App\Models\Common\FileConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileConfiguration>
 */
class FileConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'default_disk' => 'public', 'max_upload_mb' => 32, 'allowed_extensions' => ['png', 'jpg', 'pdf', 'txt'],
        ];
    }
}
