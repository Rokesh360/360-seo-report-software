<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GstMaster extends Model
{
    protected $fillable = ['name', 'percentage', 'status'];

    protected $casts = [
        'percentage' => 'decimal:2',
        'status'     => 'boolean',
    ];
}