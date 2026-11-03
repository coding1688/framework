<?php

namespace Database\Factories\Content;

use App\Models\Content\ContentDocument;
use App\Models\Content\ContentDocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentDocument>
 */
class ContentDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => ContentDocumentCategory::factory(),
            'type' => 'development',
            'slug' => fake()->unique()->slug(2),
            'title_i18n' => ['en' => fake()->words(4, true)],
            'summary_i18n' => ['en' => fake()->sentence()],
            'content_i18n' => ['en' => '<p>'.fake()->paragraph().'</p>'],
            'published_at' => now(),
            'sort' => 0,
            'active' => true,
        ];
    }
}
