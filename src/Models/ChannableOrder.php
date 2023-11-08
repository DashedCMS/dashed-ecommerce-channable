<?php

namespace Dashed\DashedEcommerceChannable\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Dashed\DashedEcommerceCore\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannableOrder extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'dashed__order_channable';
    protected $fillable = [
        'order_id',
        'channable_id',
        'project_id',
        'platform_id',
        'platform_name',
        'channel_id',
        'channel_name',
        'status_paid',
        'status_shipped',
        'tracking_code',
        'tracking_original',
        'transporter',
        'transporter_original',
        'commission',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
