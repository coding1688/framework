<?php

namespace Database\Factories\Common;

use App\Models\Common\File;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'public',
            'type' => 'general',
            'path' => 'uploads/general/test/'.$this->faker->uuid().'.jpg',
            'original_name' => $this->faker->word().'.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1024, 1048576),
            'visibility' => 'public',
            'checksum' => hash('sha256', $this->faker->uuid()),
        ];
    }
}
