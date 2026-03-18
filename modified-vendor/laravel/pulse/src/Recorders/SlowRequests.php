<?php

namespace Laravel\Pulse\Recorders;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Concerns\ConfiguresAfterResolving;
use Laravel\Pulse\Pulse;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

/**
 * @internal
 */
class SlowRequests
{
    use Concerns\Ignores,
        Concerns\LivewireRoutes,
        Concerns\Sampling,
        Concerns\Thresholds,
        ConfiguresAfterResolving;

    /**
     * Create a new recorder instance.
     */
    public function __construct(
        protected Pulse $pulse,
    ) {
        //
    }

    /**
     * Register the recorder.
     */
    public function register(callable $record, Application $app): void
    {
        $this->afterResolving(
            $app,
            Kernel::class,
            fn (Kernel $kernel) => $kernel->whenRequestLifecycleIsLongerThan(-1, $record) // @phpstan-ignore method.notFound
        );
    }

    /**
     * Record the request.
     */
    public function record(Carbon $startedAt, Request $request, Response $response): void
    {
        if (! $request->route() instanceof Route || ! $this->shouldSample()) {
            return;
        }

        [$path, $via] = $this->resolveRoutePath($request);

        if (
            $this->shouldIgnore($path) ||
            $this->underThreshold($duration = ((int) $startedAt->diffInMilliseconds()), $path)
        ) {
            return;
        }

        if ($request->is('ping') || $request->path() === 'ping') {
            // Log::info('Pulse: Request ping user');
            return;
        }

        $this->pulse->record(
            type: 'slow_request',
            key: json_encode([$request->method(), $path, $via], flags: JSON_THROW_ON_ERROR),
            value: $duration,
            timestamp: $startedAt,
        )->max()->count();

        if ($userId = $this->pulse->resolveAuthenticatedUserId()) {
            $user = User::find($userId);
            if (! $user) {
                return;
            }
            $userData = [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'app_name'   => config('app.name'),
            ];

            $this->pulse->record(
                type: 'slow_user_request',
                // key: (string) $userId,
                key: json_encode($userData, JSON_THROW_ON_ERROR),
                timestamp: $startedAt,
            )->count();
        }
    }
}
