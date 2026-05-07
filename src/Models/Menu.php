<?php

namespace Dashed\DashedMenus\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedMenus\Classes\Menus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Menu extends Model
{
    // saade/filament-adjacency-list eist de trait op het owner-model van de
    // AdjacencyList-form-component, ook al is Menu zelf niet zelf-referentieel.
    // De trait-methodes worden hier niet aangeroepen; alleen de class_uses()
    // check van saade moet slagen.
    use HasRecursiveRelationships;
    use SoftDeletes;
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__menus';

    protected static function booted()
    {
        static::created(function ($menu) {
            Menus::clearCache();
        });

        static::updated(function ($menu) {
            Menus::clearCache();
        });

        static::deleting(function ($menu) {
            $menu->menuItems()->delete();
        });
    }

    protected $casts = [
        'items' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class)->with(['parentMenuItem']);
    }

    public function parentMenuItems()
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_menu_item_id')
            ->with('childMenuItems')
            ->orderBy('order', 'asc');
    }

    /**
     * Flat HasMany over alle items in dit menu, gebruikt door de
     * AdjacencyList tree-builder. saade caches deze collection en
     * Staudenmeir's `toTree()` reconstrueert vervolgens de hierarchy
     * via `parent_menu_item_id` zodat álle keys (ook genest) in de cache
     * staan en `save()` niet crasht op nested children.
     */
    public function menuItemsForTree()
    {
        return $this->hasMany(MenuItem::class)->orderBy('order', 'asc');
    }
}
