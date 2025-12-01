<?php

namespace App\Observers;

use App\Models\UserGuides;
use Illuminate\Support\Facades\Cache;

class UserGuidesObserver
{
    private const CACHE_LIST_NAME = 'list:userguides';
    private const CACHE_MENU_NAME = 'menu:userguides';

    public function created(UserGuides $guide)
    {
        Cache::forget(self::CACHE_LIST_NAME);
        Cache::forget(self::CACHE_MENU_NAME);
    }

    public function updated(UserGuides $guide)
    {
        Cache::forget(self::CACHE_LIST_NAME);
        Cache::forget(self::CACHE_MENU_NAME);
    }

    public function deleted(UserGuides $guide)
    {
        Cache::forget(self::CACHE_LIST_NAME);
        Cache::forget(self::CACHE_MENU_NAME);
    }
}
