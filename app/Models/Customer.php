<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'pilot_id',
        'display_name',
        'email',
        'phone',
        'notes',
    ];

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function grinds()
    {
        return $this->hasMany(Grind::class, 'customer_id');
    }
}