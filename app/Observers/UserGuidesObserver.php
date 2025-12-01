<?php

namespace App\Observers;

use App\Models\UserGuides;
use Illuminate\Support\Facades\Cache;

class UserGuidesObserver
{
    private $cacheListName = 'list:userguides';
    private $cacheMenuName = 'menu:userguides';

    public function created(UserGuides $guide)
    {
        Cache::forget($this->cacheListName);
        Cache::forget($this->cacheMenuName);
    }

    public function updated(UserGuides $guide)
    {
        Cache::forget($this->cacheListName);
        Cache::forget($this->cacheMenuName);
    }

    public function deleted(UserGuides $guide)
    {
        Cache::forget($this->cacheListName);
        Cache::forget($this->cacheMenuName);
    }
}
