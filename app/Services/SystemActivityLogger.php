<?php

namespace App\Services;

use App\Models\SystemActivityLog;
use Illuminate\Support\Facades\Auth;

class SystemActivityLogger
{
    /**
     * Persist a system activity event.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?string $subjectLabel = null,
        ?array $meta = null
    ): SystemActivityLog {
        $actor = Auth::guard('admin')->user() ?? Auth::user();

        return SystemActivityLog::query()->create(array(
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? $actor?->email ?? ($actor ? ('User #' . $actor->id) : 'system'),
            'meta' => $meta,
            'created_at' => now(),
        ));
    }
}
