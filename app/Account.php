<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends APIModel
{
    protected $table = 'accounts';
    protected $hidden = array('password');
    protected $fillable = ['id', 'username', 'password', 'account_type'];
}