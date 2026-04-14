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

        cms()->registerResourceDocs(
            resource: \Dashed\DashedMenus\Filament\Resources\MenuResource::class,
            title: 'Menu\'s',
            intro: 'Op deze pagina beheer je de navigatiemenus van de website, zoals het hoofdmenu bovenin en het footermenu onderaan. Per menu bepaal je welke menu items er in staan en in welke volgorde.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Een nieuw menu aanmaken, bijvoorbeeld voor een extra navigatiezone.
- Een bestaand menu openen om de items te beheren.
- Zien hoeveel menu items elk menu bevat.
- Menu\'s die je niet meer nodig hebt verwijderen.
MARKDOWN,
                ],
                [
                    'heading' => 'Menu items beheren',
                    'body' => 'Bij elk menu staan de bijbehorende menu items. Open een menu en ga naar de relatie met menu items om nieuwe items toe te voegen, bestaande aan te passen of de volgorde te veranderen. De items zelf beheer je dus altijd vanuit het menu waar ze bij horen, niet los.',
                ],
            ],
            tips: [
                'Houd het hoofdmenu kort, bezoekers kunnen niet tien items tegelijk onthouden.',
                'Gebruik het footermenu voor minder belangrijke links zoals voorwaarden en privacy.',
                'Controleer na een wijziging meteen op de website of de navigatie nog klopt.',
            ],
        );

        cms()->registerResourceDocs(
            resource: \Dashed\DashedMenus\Filament\Resources\MenuItemResource::class,
            title: 'Menu items',
            intro: 'Menu items zijn de losse links binnen een menu. Per item kies je welke soort verwijzing het is en waar de bezoeker naartoe gaat als hij erop klikt. Je beheert menu items vanuit het bijbehorende menu.',
            sections: [
                [
                    'heading' => 'Wat kun je hier doen?',
                    'body' => <<<MARKDOWN
- Nieuwe items toevoegen aan een menu.
- Per item een label, type en bestemming instellen.
- De volgorde van items veranderen door ze te slepen.
- Items onder elkaar hangen als sub-menu.
MARKDOWN,
                ],
                [
                    'heading' => 'Types menu items',
                    'body' => <<<MARKDOWN
Per item kies je hoe de link werkt:

- **Gewone link**: een verwijzing naar een pagina binnen de eigen website.
- **Externe URL**: een volledige link naar een andere website, bijvoorbeeld een social media pagina.
- **Dynamische verwijzing**: een koppeling met bijvoorbeeld een product, productcategorie of een specifieke pagina in het CMS. Wijzigt de URL van dat onderliggende item, dan past het menu item zich automatisch aan.
MARKDOWN,
                ],
                [
                    'heading' => 'Sub-menu\'s maken',
                    'body' => 'Menu items kunnen onder een ander item hangen zodat er een uitklapmenu ontstaat. Zo kun je bijvoorbeeld onder "Producten" verschillende categorieen neerzetten. Je maakt een sub-menu door een item als kind van een ander item te slepen.',
                ],
            ],
            tips: [
                'Gebruik korte labels, maximaal een of twee woorden per item.',
                'Kies een dynamische verwijzing waar dat kan, dat voorkomt kapotte links bij wijzigingen.',
                'Zet externe links bij voorkeur open in een nieuw tabblad.',
            ],
        );
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
