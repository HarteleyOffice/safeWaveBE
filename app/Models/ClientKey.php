<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientKey extends Model
{
    protected $fillable = ['peer_id', 'key_value', 'availability'];
}

