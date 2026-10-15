<?php

namespace Dashed\DashedMenus\Classes;

use Dashed\DashedMenus\Models\Menu;
use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Helper;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedMenus\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

class Menus
{
    /**
     * Alle menu-sleutels dragen een versienummer. Leegmaken is dan één bump
     * van dat nummer in plaats van elke sleutel per menu, per taal en per item
     * opsommen (en als vangnet de hele cache weggooien): een hernoemd of
     * verwijderd menu, een taal die er later bij kwam en de 24-uurs caches
     * per item (naam en url) vallen zo allemaal in één keer weg.
     */
    public const VERSION_KEY = 'dashed-menus-version';

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function cacheKey(string $key): string
    {
        return $key . '-v' . self::version();
    }

    public static function clearCache(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);

        if (class_exists(CacheInvalidator::class)) {
            foreach (Sites::getSites() as $site) {
                CacheInvalidator::flushSite($site['id']);
            }
        }
    }

    /**
     * Een menu-item dat naar een pagina, product of categorie wijst, cachet
     * de naam en de url van dat model mee. Verandert dat model, dan moet het
     * menu dus ook opnieuw.
     */
    public static function clearCacheForLinkedModel(Model $model, bool $deleted = false): void
    {
        if ($model instanceof Menu || $model instanceof MenuItem) {
            return;
        }

        if (! in_array(get_class($model), self::linkedModelClasses(), true)) {
            return;
        }

        if (! $deleted && ! self::affectsMenu($model)) {
            return;
        }

        self::clearCache();
    }

    protected static function affectsMenu(Model $model): bool
    {
        // Na een insert is wasChanged() leeg, dus een nieuw model bumpt niet;
        // wasRecentlyCreated is hier onbruikbaar, die blijft op dezelfde
        // instantie ook na een latere save() op true staan.
        $watched = method_exists($model, 'getUrlHistoryWatchedAttributes')
            ? $model->getUrlHistoryWatchedAttributes()
            : [];

        return $model->wasChanged(array_unique(array_merge($watched, ['name', 'title', 'slug'])));
    }

    /**
     * @return array<int, string>
     */
    public static function linkedModelClasses(): array
    {
        // Niet cachen als de tabel er (nog) niet is: bij een verse installatie
        // of een test zonder migraties mag een opgeslagen model niet klappen.
        return rescue(
            fn () => Cache::rememberForever(self::cacheKey('menus-linked-models'), function () {
                return MenuItem::query()
                    ->whereNotNull('model')
                    ->where('model', '!=', '')
                    ->distinct()
                    ->pluck('model')
                    ->all();
            }),
            [],
            false,
        );
    }

    public static function getMenuItems($menuName)
    {
        $menuItems = Cache::rememberForever(self::cacheKey("menu-$menuName-" . App::getLocale()), function () use ($menuName) {
            $menu = Menu::where('name', $menuName)->first();

            if (! $menu) {
                return [];
            }

            $topLevelMenuItems = $menu->parentMenuItems()->with(['childMenuItems'])->thisSite()->get();

            return self::processMenuItems($topLevelMenuItems);
        });

        $menuItems = self::setActiveStatus($menuItems);

        return $menuItems;
    }

    public static function processMenuItems($menuItems)
    {
        $result = [];
        foreach ($menuItems as $menuItem) {
            $childMenuItems = $menuItem->childMenuItems()->with(['childMenuItems'])->get();
            $result[] = [
                'id' => $menuItem->id,
                'name' => $menuItem->name(),
                'url' => $menuItem->getUrl(),
                'type' => $menuItem->type,
                'contentBlocks' => $menuItem->contentBlocks,
                'hasChilds' => $childMenuItems->isNotEmpty(),
                'childs' => $childMenuItems->isNotEmpty() ? self::processMenuItems($childMenuItems) : [],
            ];
        }

        $result = self::setActiveStatus($result);

        return $result;
    }

    public static function setActiveStatus($menuItems): array
    {
        foreach ($menuItems as &$menuItem) {
            $menuItem['active'] = Helper::urlIsActive($menuItem['url'], true);
            if ($menuItem['hasChilds']) {
                self::setActiveStatus($menuItem['childs']);
            }
        }

        return $menuItems;
    }
}
