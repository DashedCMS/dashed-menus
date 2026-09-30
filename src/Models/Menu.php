<?php

namespace Dashed\DashedMenus\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedMenus\Classes\Menus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

class Menu extends Model
{
    use SoftDeletes;
    use LogsActivity;
    use HasFactory;

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

        static::saved(function ($menu) {
            if (class_exists(CacheInvalidator::class)) {
                foreach (Sites::getSites() as $site) {
                    CacheInvalidator::flushSite($site['id']);
                }
            }
        });

        static::deleting(function ($menu) {
            $menu->menuItems()->delete();
        });

        static::deleted(function ($menu) {
            if (class_exists(CacheInvalidator::class)) {
                foreach (Sites::getSites() as $site) {
                    CacheInvalidator::flushSite($site['id']);
                }
            }
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

    protected static function newFactory(): \Dashed\DashedMenus\Database\Factories\MenuFactory
    {
        return \Dashed\DashedMenus\Database\Factories\MenuFactory::new();
    }
}
