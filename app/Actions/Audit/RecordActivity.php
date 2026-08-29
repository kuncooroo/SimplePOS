<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class RecordActivity
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $context
     */
    public function execute(
        User $actor,
        ActivityAction $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $context = null,
    ): ActivityLog {
        if ($action->value === '') {
            throw new InvalidArgumentException('Activity action is required.');
        }

        $log = new ActivityLog;
        $log->forceFill([
            'user_id' => $actor->getKey(),
            'action' => $action,
            'subject_type' => $this->subjectType($subject),
            'subject_id' => $subject?->getKey(),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'context' => $this->sanitize($context),
            'occurred_at' => now(),
        ]);
        $log->save();

        return $log;
    }

    private function subjectType(?Model $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        return match (true) {
            $subject instanceof User => 'User',
            $subject instanceof StoreSetting => 'StoreSetting',
            default => $subject->getMorphClass(),
        };
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $blocked = [
            'password',
            'password_confirmation',
            'remember_token',
            'token',
            'current_password',
        ];

        $clean = [];

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) {
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
