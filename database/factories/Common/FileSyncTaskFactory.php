<?php

namespace Database\Factories\Common;

use App\Models\Common\FileSyncTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileSyncTask>
 */
class FileSyncTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_disk' => 'public', 'target_disk' => 'local', 'status' => 'pending',
        ];
    }
}
