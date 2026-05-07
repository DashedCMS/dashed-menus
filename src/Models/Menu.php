<?php

namespace Dashed\DashedMenus\Models;

use Spatie\Activitylog\LogOptions;
use Dashed\DashedMenus\Classes\Menus;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
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
}
