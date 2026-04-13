<?php

namespace Dashed\DashedMenus;

use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DashedMenusServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-menus';

    public function bootingPackage(): void
    {
        Gate::policy(\Dashed\DashedMenus\Models\Menu::class, \Dashed\DashedMenus\Policies\MenuPolicy::class);
        Gate::policy(\Dashed\DashedMenus\Models\MenuItem::class, \Dashed\DashedMenus\Policies\MenuItemPolicy::class);

        cms()->registerRolePermissions('Menu', [
            'view_menu' => 'Menu\'s bekijken',
            'edit_menu' => 'Menu\'s bewerken',
            'delete_menu' => 'Menu\'s verwijderen',
            'view_menu_item' => 'Menu-items bekijken',
            'edit_menu_item' => 'Menu-items bewerken',
            'delete_menu_item' => 'Menu-items verwijderen',
        ]);
    }

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $package
            ->name('dashed-menus');

        cms()->builder('plugins', [
            new DashedMenusPlugin(),
        ]);
    }
}
