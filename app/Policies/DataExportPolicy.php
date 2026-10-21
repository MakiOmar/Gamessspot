<?php

namespace App\Policies;

use App\Models\DataExport;
use App\Models\User;

/**
 * Exports can contain account passwords, so only the manager who requested one may poll or download it.
 */
class DataExportPolicy
{
    public function view(User $user, DataExport $export): bool
    {
        return (int) $export->user_id === (int) $user->id;
    }

    public function download(User $user, DataExport $export): bool
    {
        return $this->view($user, $export) && $export->isReady();
    }
}
