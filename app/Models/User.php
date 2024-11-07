<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable; // Use the Authenticatable trait

    protected $connection = 'mongodb';
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
}
