<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public const STATUSES = array( 'has_problem', 'needs_return', 'solved', 'archived' );

    public static function isReportStatus(?string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    protected $fillable = array(
        'order_id',
        'seller_id',
        'status',
        'note',
    );

    /**
     * Define the relationship between Report and Order.
     * A report belongs to an order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Define the relationship between Report and User (Seller).
     * A report belongs to a seller.
     */
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function actions()
    {
        return $this->hasMany(ReportAction::class)->latest('id');
    }

    public function latestAction()
    {
        return $this->hasOne(ReportAction::class)->latestOfMany();
    }

    /**
     * Change status and log who did it in one step.
     */
    public function transitionTo(string $toStatus, string $action, ?int $userId): void
    {
        $fromStatus = $this->status;
        $this->update(array( 'status' => $toStatus ));
        $this->recordAction($action, $fromStatus, $toStatus, $userId);
    }

    public function recordAction(string $action, ?string $fromStatus, ?string $toStatus, ?int $userId): ReportAction
    {
        return $this->actions()->create(array(
            'user_id'     => $userId,
            'action'      => $action,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
        ));
    }
}
