<?php

declare(strict_types=1);

use Lartrix\ThinkPHPRoutePriority\RoutePriorityMatcher;
use Lartrix\ThinkPHPRoutePriority\Route;
use think\App;
use think\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

final class RoutePriorityTest
{
    private int $assertions = 0;

    public function run(): void
    {
        $this->routeWithVariableDoesNotShadowExactStaticRoute();
        $this->shorterStaticRouteDoesNotShadowLongerStaticRoute();
        $this->originalDeclarationOrderIsKeptForSameSpecificity();
        $this->thinkphpDispatchUsesPrioritizedRoutes();

        echo sprintf("OK (%d assertions)\n", $this->assertions);
    }

    private function routeWithVariableDoesNotShadowExactStaticRoute(): void
    {
        $routes = [
            ['rule' => '/path/<id>', 'target' => 'variable'],
            ['rule' => '/path/you', 'target' => 'static'],
        ];

        $matched = RoutePriorityMatcher::match($routes, '/path/you');

        $this->assertSame('static', $matched['target'] ?? null, __METHOD__);
    }

    private function shorterStaticRouteDoesNotShadowLongerStaticRoute(): void
    {
        $routes = [
            ['rule' => '/path', 'target' => 'short'],
            ['rule' => '/path/you', 'target' => 'long'],
        ];

        $matched = RoutePriorityMatcher::match($routes, '/path/you');

        $this->assertSame('long', $matched['target'] ?? null, __METHOD__);
    }

    private function originalDeclarationOrderIsKeptForSameSpecificity(): void
    {
        $routes = [
            ['rule' => '/article/<id>', 'target' => 'first'],
            ['rule' => '/article/<name>', 'target' => 'second'],
        ];

        $matched = RoutePriorityMatcher::match($routes, '/article/abc');

        $this->assertSame('first', $matched['target'] ?? null, __METHOD__);
    }

    private function thinkphpDispatchUsesPrioritizedRoutes(): void
    {
        $app = new App(dirname(__DIR__));
        $route = new Route($app);

        $route->get('/path/<id>', static fn (): string => 'variable');
        $route->get('/path', static fn (): string => 'short');
        $route->get('/path/you', static fn (): string => 'static');

        $request = new Request();
        $request->setMethod('GET');
        $request->setPathinfo('path/you');

        $response = $route->dispatch($request);

        $this->assertSame('static', $response->getContent(), __METHOD__);
    }

    private function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        $this->assertions++;

        if ($expected !== $actual) {
            throw new RuntimeException(sprintf(
                "%s failed: expected %s, got %s",
                $message,
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }
}

(new RoutePriorityTest())->run();
