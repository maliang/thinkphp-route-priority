<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use Closure;
use think\Container;
use think\Route;
use think\route\Domain;

final class PriorityDomain extends Domain
{
    use PriorityCheck;

    public function __construct(Route $router, ?string $name = null, $rule = null, bool $lazy = false)
    {
        $this->router = $router;
        $this->domain = $name;
        $this->rule = $rule;

        if (!$lazy && !is_null($rule)) {
            $this->parseGroupRule($rule);
        }
    }

    public function parseGroupRule($rule): void
    {
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if ($rule instanceof Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif ($this->config('route_auto_group')) {
            $this->loadGroupRoutes();
        }

        $this->router->setGroup($origin);
        $this->hasParsed = true;
    }

    protected function loadGroupRoutes(): void
    {
        $routePath = root_path('route');
        if (!is_dir($routePath)) {
            return;
        }

        $dirs = glob($routePath . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $groupName = str_replace('\\', '/', substr_replace($dir, '', 0, strlen($routePath)));
            if (!$this->router->getRuleName()->hasGroup($groupName)) {
                $this->router->group($groupName);
            }
        }
    }
}
