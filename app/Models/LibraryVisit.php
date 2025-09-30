<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryVisit extends Model
{
    protected $guarded = [];

    protected $casts = [
        'auto_logged_out' => 'boolean',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visitPurpose(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(VisitPurpose::class);
    }
}
