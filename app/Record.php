<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Record extends APIModel
{
    protected $table = 'records';
    protected $fillable = ['account_id', 'url', 'code'];
}
