<?php

declare(strict_types=1);

namespace Lartrix\ThinkPHPRoutePriority;

use think\route\Rule;
use think\route\RuleGroup;
use think\route\RuleItem;

final class RoutePriorityMatcher
{
    /**
     * Sort route definitions from most specific to least specific.
     *
     * The input can be ThinkPHP Rule objects or simple arrays containing a
     * "rule" key. Sorting is stable for routes with equal specificity.
     *
     * @param array<int, mixed> $routes
     * @return array<int, mixed>
     */
    public static function sort(array $routes): array
    {
        $indexed = [];

        foreach (array_values($routes) as $index => $route) {
            $indexed[] = [
                'index' => $index,
                'route' => $route,
                'score' => self::score(self::ruleOf($route)),
            ];
        }

        usort($indexed, static function (array $left, array $right): int {
            foreach ($left['score'] as $key => $leftValue) {
                $rightValue = $right['score'][$key];

                if ($leftValue === $rightValue) {
                    continue;
                }

                return $rightValue <=> $leftValue;
            }

            return $left['index'] <=> $right['index'];
        });

        return array_column($indexed, 'route');
    }

    /**
     * @param array<int, array{rule:string,target:mixed}> $routes
     * @return array{rule:string,target:mixed}|null
     */
    public static function match(array $routes, string $path): ?array
    {
        foreach (self::sort($routes) as $route) {
            if (self::matches((string) $route['rule'], $path)) {
                return $route;
            }
        }

        return null;
    }

    public static function matches(string $rule, string $path): bool
    {
        $rule = trim($rule, '/');
        $path = trim($path, '/');

        if ($rule === $path) {
            return true;
        }

        $ruleSegments = $rule === '' ? [] : explode('/', $rule);
        $pathSegments = $path === '' ? [] : explode('/', $path);

        if (count($ruleSegments) !== count($pathSegments)) {
            return false;
        }

        foreach ($ruleSegments as $index => $segment) {
            if (preg_match('/^<[^>]+\??>$/', $segment)) {
                continue;
            }

            if (strcasecmp($segment, $pathSegments[$index]) !== 0) {
                return false;
            }
        }

        return true;
    }

    private static function ruleOf(mixed $route): string
    {
        if ($route instanceof RuleItem) {
            return (string) $route->getRule();
        }

        if ($route instanceof RuleGroup) {
            return $route->getFullName();
        }

        if ($route instanceof Rule) {
            return (string) $route->getRule();
        }

        if (is_array($route) && isset($route['rule'])) {
            return (string) $route['rule'];
        }

        return '';
    }

    /**
     * @return array{static:int, required:int, length:int, segments:int, optional:int, wildcard:int}
     */
    private static function score(string $rule): array
    {
        $segments = array_values(array_filter(explode('/', trim($rule, '/')), static fn (string $part): bool => $part !== ''));
        $static = 0;
        $required = 0;
        $optional = 0;
        $wildcard = 0;
        $length = 0;

        foreach ($segments as $segment) {
            if (preg_match('/^<[^>]+>$/', $segment)) {
                if (str_ends_with($segment, '?>')) {
                    $optional++;
                } else {
                    $required++;
                }
                continue;
            }

            if (str_contains($segment, '<')) {
                $wildcard++;
                $length += strlen(preg_replace('/<[^>]+>/', '', $segment) ?? '');
                continue;
            }

            $static++;
            $length += strlen($segment);
        }

        return [
            'static' => $static,
            'required' => -$required,
            'length' => $length,
            'segments' => count($segments),
            'optional' => -$optional,
            'wildcard' => -$wildcard,
        ];
    }
}
