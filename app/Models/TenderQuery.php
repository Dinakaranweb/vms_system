<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderQuery extends Model
{
    protected $fillable = [
        'tender_id',
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

    public function tender()
    {
        return $this->belongsTo(Tender::class, 'tender_id');
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
