<?php

namespace Database\Factories\Content;

use App\Models\Content\ContentAnnouncement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentAnnouncement>
 */
class ContentAnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title_i18n' => ['en' => fake()->words(3, true)],
            'content_i18n' => ['en' => fake()->paragraph()],
            'published_at' => now(),
            'sort' => 0,
            'active' => true,
        ];
    }
}
