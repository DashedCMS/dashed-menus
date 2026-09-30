<?php

namespace Dashed\DashedMenus\Database\Factories;

use Illuminate\Support\Str;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedMenus\Models\Menu;
use Dashed\DashedMenus\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MenuItem> */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        $naam = $this->faker->words(2, true);

        return [
            'menu_id' => Menu::factory(),
            'parent_menu_item_id' => null,
            'site_ids' => [Sites::getFirstSite()['id']],
            'name' => ['nl' => Str::title($naam)],
            'url' => ['nl' => '/' . Str::slug($naam)],
            'type' => 'normal',
            'order' => 1,
        ];
    }

    public function onder(MenuItem $ouder): static
    {
        return $this->state(fn () => [
            'menu_id' => $ouder->menu_id,
            'parent_menu_item_id' => $ouder->id,
        ]);
    }
}
