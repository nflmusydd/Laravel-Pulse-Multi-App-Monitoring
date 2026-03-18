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
class UserRequests
{
    use Concerns\Ignores,
        Concerns\LivewireRoutes,
        Concerns\Sampling,
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
        if (
            ($userId = $this->pulse->resolveAuthenticatedUserId()) === null ||
            ! $request->route() instanceof Route ||
            ! $this->shouldSample()
        ) {
            return;
        }

        if ($request->is('ping') || $request->path() === 'ping') {
            // Log::info('Pulse: Request ping user');
            return;
        }

        if ($this->shouldIgnore($this->resolveRoutePath($request)[0])) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $userData = [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'app_name'   => config('app.name'),
        ];

        $this->pulse->record(
            type: 'user_request',
            // key: (string) $userId,
            key: json_encode($userData, JSON_THROW_ON_ERROR),
            timestamp: $startedAt->getTimestamp()
        )->count();

    }
}
