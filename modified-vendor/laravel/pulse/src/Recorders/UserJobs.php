<?php

namespace Laravel\Pulse\Recorders;

use Carbon\CarbonImmutable;
use Illuminate\Queue\Events\JobQueued;
use Laravel\Pulse\Pulse;
use App\Models\User;

/**
 * @internal
 */
class UserJobs
{
    use Concerns\Ignores, Concerns\Sampling;

    /**
     * The events to listen for.
     *
     * @var list<class-string>
     */
    public array $listen = [
        JobQueued::class,
    ];

    /**
     * Create a new recorder instance.
     */
    public function __construct(
        protected Pulse $pulse,
    ) {
        //
    }

    /**
     * Record the job.
     */
    public function record(JobQueued $event): void
    {
        if ($event->connectionName === 'sync') {
            return;
        }

        [$timestamp, $name, $userIdResolver] = [
            CarbonImmutable::now()->getTimestamp(),
            match (true) {
                is_string($name = $event->job) => $name,
                method_exists($event->job, 'displayName') => $event->job->displayName(),
                default => $event->job::class,
            },
            $this->pulse->authenticatedUserIdResolver(),
        ];

        $this->pulse->lazy(function () use ($timestamp, $name, $userIdResolver) {
            if (
                ($userId = $userIdResolver()) === null ||
                ! $this->shouldSample() ||
                $this->shouldIgnore($name)
            ) {
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
                type: 'user_job',
                // key: (string) $userId,
                key: json_encode($userData, JSON_THROW_ON_ERROR),
                timestamp: $timestamp,
            )->count();
        });
    }
}
