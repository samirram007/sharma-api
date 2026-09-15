<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Routing\Route;

/*
 * A route that references a middleware alias the HTTP kernel never
 * registered (e.g. 'feature.permission' on a bootstrap/app.php from an older
 * deploy) does NOT fail at route:list time — it blows up on the first
 * request with:
 *
 *   ReflectionException: Class "feature.permission" does not exist
 *
 * …which surfaces as a generic 500 on every route using it (seen on
 * /api/states in production after a partial deploy). This smoke test walks
 * every registered route and asserts each string middleware resolves to a
 * registered alias (or group) whose class exists, so the mismatch breaks CI
 * instead of production.
 */
test('every route middleware alias resolves to an existing class', function () {
    $router = app('router');
    $aliases = $router->getMiddleware();
    $groups = $router->getMiddlewareGroups();

    $isKnown = fn (string $name): bool =>
        isset($aliases[$name]) && class_exists($aliases[$name])
        || isset($groups[$name])
        || class_exists($name);

    $broken = collect($router->getRoutes()->getRoutes())
        ->flatMap(function (Route $route) {
            return collect($route->gatherMiddleware())
                ->flatten()
                ->map(fn ($middleware) => [$middleware, $route->uri()]);
        })
        ->filter(function (array $item) use ($isKnown) {
            [$middleware] = $item;

            if (! is_string($middleware)) {
                return false; // closure / callable
            }

            $name = strtok($middleware, ':');

            return $name === false || ! $isKnown((string) $name);
        })
        ->map(fn (array $item) => sprintf('%s (route: %s)', $item[0], $item[1]))
        ->unique()
        ->values();

    foreach ($broken as $entry) {
        $this->fail("Unregistered middleware on {$entry}");
    }

    expect($broken)->toBeEmpty();
});