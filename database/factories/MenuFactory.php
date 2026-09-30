<?php

namespace Dashed\DashedMenus\Database\Factories;

use Illuminate\Support\Str;
use Dashed\DashedMenus\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Menu> */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        // De naam is in het CMS altijd een slug en uniek; de resource slugt
        // bij het typen, dus de factory levert wat een beheerder overhoudt.
        return [
            'name' => Str::slug($this->faker->unique()->words(2, true)),
        ];
    }
}
