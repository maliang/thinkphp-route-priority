# ThinkPHP 8 Route Priority Patch

这个 Composer 包用于修复 ThinkPHP 8 路由的声明顺序遮挡问题：

- `/path/<id>` 写在 `/path/you` 前面时，不再抢先匹配 `/path/you`
- `/path` 写在 `/path/you` 前面时，不再因为非完整匹配抢先命中 `/path/you`
- 同等优先级的动态规则仍保持原声明顺序

## 安装

```bash
composer require lartrix/thinkphp-route-priority
```

如果希望安装或更新 Composer 依赖后自动刷新 ThinkPHP 服务发现，请在宿主项目的 `composer.json` 增加：

```json
{
    "scripts": {
        "post-autoload-dump": [
            "@php think service:discover"
        ]
    }
}
```

之后执行 `composer require lartrix/thinkphp-route-priority` 时会自动生成 `vendor/services.php`，无需再手动执行 `php think service:discover`。

如果你的项目没有自动发现服务，也可以手动在 `app/provider.php` 中绑定：

```php
<?php

return [
    'route' => \Lartrix\ThinkPHPRoutePriority\Route::class,
    \think\Route::class => \Lartrix\ThinkPHPRoutePriority\Route::class,
];
```

## 使用

安装并发现服务后，原来的路由文件无需改写：

```php
use think\facade\Route;

Route::get('/path/<id>', 'index/read');
Route::get('/path/you', 'index/you');
Route::get('/path', 'index/path');
```

访问 `/path/you` 时会优先选择静态且更具体的 `/path/you`，不会被前面的 `/path/<id>` 或 `/path` 覆盖。

## 工作方式

包会把 ThinkPHP 容器里的 `route` 服务替换为自定义 `Route`，并让默认域名、域名路由、普通分组在匹配前按“更具体的规则优先”排序：

1. 静态片段更多的规则优先；
2. 动态变量更少的规则优先；
3. 路径更长、更具体的规则优先；
4. 同等优先级保持原声明顺序。

## 验证

在本包目录运行：

```bash
composer test
```

## 注意

这个补丁面向 ThinkPHP `topthink/framework:^8.0`。如果你的项目已经自定义绑定了 `route` 服务，请合并本包的 `Route` 继承逻辑，或确保最终绑定到 `Lartrix\ThinkPHPRoutePriority\Route`。
