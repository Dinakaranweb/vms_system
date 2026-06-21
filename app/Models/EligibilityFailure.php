<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibilityFailure extends Model
{
    protected $table = 'eligibility_failures';

    protected $fillable = [
        'tender_id',
        'vendor_id',
        'failed_questions',
        'submitted_answers',
        'ip_address',
    ];

    protected $casts = [
        'failed_questions' => 'array',
        'submitted_answers' => 'array',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
