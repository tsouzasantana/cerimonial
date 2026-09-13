<?php

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyPublicContractAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'public.contract' => VerifyPublicContractAccess::class,
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $routeName = $request->route()?->getName();

            if ($routeName && str_starts_with($routeName, 'public.') && ! $request->expectsJson()) {
                return response()->view('public.link-invalido', [], 404);
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->route()?->getName() === 'public.verify' && ! $request->expectsJson()) {
                return redirect()->route('public.gate', $request->route('token'))
                    ->withErrors(['document' => 'Muitas tentativas. Aguarde um minuto e tente novamente.']);
            }
        });
    })->create();
