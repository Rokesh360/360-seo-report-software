<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignService extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assign_services';

    protected $fillable = [
        'client_id',
        'service_id',
        'from_date',
        'to_date',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    /* -------------------------
     |  Relationships
     ------------------------- */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /* -------------------------
     |  Accessors
     ------------------------- */
    public function getDurationAttribute(): string
    {
        return $this->from_date->format('d M Y') . ' - ' . $this->to_date->format('d M Y');
    }
}