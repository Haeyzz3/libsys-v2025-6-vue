<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodical extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'authors' => 'array', // saved as json in db
        'editors' => 'array', // saved as json in db
    ];
}
