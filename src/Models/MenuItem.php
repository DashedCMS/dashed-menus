<?php

namespace Dashed\DashedMenus\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\App;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedMenus\Classes\Menus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Dashed\DashedCore\Models\Concerns\HasSearchScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Dashed\DashedCore\Models\Concerns\HasCustomBlocks;
use Dashed\LaravelLocalization\Facades\LaravelLocalization;

class MenuItem extends Model
{
    use SoftDeletes;
    use HasTranslations;
    use LogsActivity;
    use HasCustomBlocks;
    use HasSearchScope;

    protected static $logFillable = true;

    protected $table = 'dashed__menu_items';

    public $translatable = [
        'name',
        'url',
    ];

    protected $casts = [
        'site_ids' => 'array',
    ];

    protected static function booted()
    {
        static::created(function ($menuItem) {
            Menus::clearCache();
        });

        static::updated(function ($menuItem) {
            Menus::clearCache();
        });

        static::deleting(function ($menuItem) {
            foreach ($menuItem->getChilds() as $child) {
                $child->delete();
            }
        });

        static::deleted(function ($menuItem) {
            Menus::clearCache();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function scopeThisSite($query)
    {
        $query->whereJsonContains('site_ids', Sites::getActive());
    }

    //    public function scopeSearch($query, ?string $search = null)
    //    {
    //        if (request()->get('search') ?: $search) {
    //            $search = strtolower(request()->get('search') ?: $search);
    //            $query->where('site_ids', 'LIKE', "%$search%")
    //                ->orWhere('name', 'LIKE', "%$search%")
    //                ->orWhere('url', 'LIKE', "%$search%")
    //                ->orWhere('type', 'LIKE', "%$search%")
    //                ->orWhere('model', 'LIKE', "%$search%");
    //        }
    //    }

    public function site()
    {
        foreach (Sites::getSites() as $site) {
            if ($site['id'] == $this->site_id) {
                return $site;
            }
        }
    }

    public function activeSiteIds()
    {
        $menuItem = $this;
        while ($menuItem->parent_menu_item_id) {
            $menuItem = self::find($menuItem->parent_menu_item_id);
            if (! $menuItem) {
                return;
            }
        }

        $sites = [];
        foreach (Sites::getSites() as $site) {
            if (self::where('id', $menuItem->id)->where('site_ids->' . $site['id'], 'active')->count()) {
                array_push($sites, $site['id']);
            }
        }

        return $sites;
    }

    public function siteNames()
    {
        $menuItem = $this;
        while ($menuItem->parent_menu_item_id) {
            $menuItem = self::find($menuItem->parent_menu_item_id);
            if (! $menuItem) {
                return;
            }
        }

        $sites = [];
        foreach (Sites::getSites() as $site) {
            if (self::where('id', $menuItem->id)->where('site_ids->' . $site['id'], 'active')->count()) {
                $sites[$site['name']] = 'active';
            } else {
                $sites[$site['name']] = 'inactive';
            }
        }

        return $sites;
    }

    public function getChilds()
    {
        $childs = [];
        $childMenuItems = self::where('parent_menu_item_id', $this->id)->orderBy('order', 'desc')->get();
        while ($childMenuItems->count()) {
            $childMenuItemIds = [];
            foreach ($childMenuItems as $childMenuItem) {
                $childMenuItemIds[] = $childMenuItem->id;
                $childs[] = $childMenuItem;
            }
            $childMenuItems = self::whereIn('parent_menu_item_id', $childMenuItemIds)->get();
        }

        return $childs;
    }

    public function getUrl()
    {
        return Cache::remember("menuitem-url-$this->id-" . App::getLocale(), 60 * 60 * 24, function () {
            if (! $this->type || $this->type == 'normal' || $this->type == 'externalUrl') {
                if ($this->url && (parse_url($this->url)['host'] ?? request()->getHttpHost()) != request()->getHttpHost()) {
                    return $this->url;
                } else {
                    if (str($this->url)->contains(['tel', 'mailto'])) {
                        return $this->url;
                    }

                    return LaravelLocalization::localizeUrl($this->url ?: '/');
                }
            } else {
                $modelResult = $this->model::find($this->model_id);
                if ($modelResult) {
                    $url = $modelResult->getUrl();

                    return $url ?: '/';
                } else {
                    return '/';
                }
            }
        });
    }

    public function name(bool $cache = true): ?string
    {
        if (! $cache) {
            Cache::forget("menuitem-name-$this->id-" . App::getLocale());
        }

        return Cache::remember("menuitem-name-$this->id-" . App::getLocale(), 60 * 60 * 24, function () {
            if (! $this->type || $this->type == 'normal' || $this->type == 'externalUrl') {
                return $this->name;
            } else {
                $modelResult = $this->model::find($this->model_id);
                $replacementName = '';
                if ($modelResult) {
                    $replacementName = $modelResult->name;
                    if (! $replacementName) {
                        $replacementName = $modelResult->title;
                    }
                }

                return str_replace(':name:', $replacementName, $this->name);
            }
        });
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function childMenuItems(): HasMany
    {
        return $this->hasMany(self::class, 'parent_menu_item_id')->orderBy('order', 'asc');
    }

    public function parentMenuItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_menu_item_id');
    }
}
