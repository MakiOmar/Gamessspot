<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAction extends Model
{
    public const ACTION_LABELS = array(
        'created'    => 'Reported',
        'solved'     => 'Solved',
        'archived'   => 'Archived',
        'unarchived' => 'Unarchived',
    );

    protected $fillable = array(
        'report_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
    );

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }
}
