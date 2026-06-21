<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibilityAnswer extends Model
{
    protected $fillable = [
        'tender_application_id', 'question_id', 'answer', 'file_path', 'is_eligible'
    ];

    protected $casts = [
        'is_eligible' => 'boolean',
    ];

    public function application()
    {
        return $this->belongsTo(TenderApplication::class, 'tender_application_id');
    }

    public function question()
    {
        return $this->belongsTo(EligibilityQuestion::class);
    }
}