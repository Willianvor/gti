<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'issue_id',
        'card_id'
    ];
}
