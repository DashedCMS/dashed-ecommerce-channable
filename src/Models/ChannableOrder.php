<?php

namespace Qubiqx\QcommerceEcommerceChannable\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannableOrder extends Model
{
    use LogsActivity;

    protected static $logFillable = true;

    protected $table = 'qcommerce__order_channable';
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

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
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
