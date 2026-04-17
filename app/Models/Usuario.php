<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nome',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
    ];
}