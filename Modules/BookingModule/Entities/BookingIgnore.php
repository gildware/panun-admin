<?php

namespace Modules\BookingModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingIgnore extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'provider_id',
    ];
    
    protected static function newFactory()
    {
        return \Modules\BookingModule\Database\factories\BookingIgnoreFactory::new();
    }
}
