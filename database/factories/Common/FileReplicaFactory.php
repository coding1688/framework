<?php

namespace Database\Factories\Common;

use App\Models\Common\File;
use App\Models\Common\FileReplica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileReplica>
 */
class FileReplicaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_id' => File::factory(), 'disk' => 'local',
            'path' => 'uploads/'.fake()->uuid().'.txt', 'checksum' => hash('sha256', 'replica'),
        ];
    }
}
