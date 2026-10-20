<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationQuery extends Model
{
    protected $fillable = [
        'application_id',
        'sender_id',
        'sender_role',
        'message',
        'attachment_path',
        'attachment_name',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'application_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
