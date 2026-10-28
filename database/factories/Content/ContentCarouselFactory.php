<?php

namespace Database\Factories\Content;

use App\Models\Common\File;
use App\Models\Content\ContentCarousel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentCarousel>
 */
class ContentCarouselFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title_i18n' => ['en' => fake()->words(3, true)],
            'image_id' => File::factory(),
            'link_url' => fake()->optional()->url(),
            'sort' => 0,
            'active' => true,
        ];
    }
}
