<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'service', 
        'arrival_at', 
        'original_priority', 
        'status'
        ];

    protected function casts(): array
    {
        return [
            'arrival_at' => 'immutable_datetime',
            'original_priority' => Priority::class,
            'status' => CustomerStatus::class,
        ];
    }
}


