<?php

namespace Database\Factories\Content;

use App\Models\Content\ContentDocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentDocumentCategory>
 */
class ContentDocumentCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => 'development',
            'parent_id' => null,
            'slug' => fake()->unique()->slug(2),
            'name_i18n' => ['en' => fake()->words(3, true)],
            'description_i18n' => ['en' => fake()->sentence()],
            'sort' => 0,
            'active' => true,
        ];
    }
}
