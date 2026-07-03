<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use think\Service;

final class RoutePriorityService extends Service
{
    public array $bind = [
        'route' => Route::class,
        \think\Route::class => Route::class,
    ];
}
