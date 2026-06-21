<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibilityQuestion extends Model
{
    protected $fillable = [
        'tender_id', 'question', 'description', 'field_type',
        'options', 'eligible_options', 'is_required', 'order', 'max_size', 'validation_rules', 'determines_eligibility'
    ];

    protected $casts = [
        'options' => 'array',
        'eligible_options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'determines_eligibility' => 'boolean',
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function answers()
    {
        return $this->hasMany(EligibilityAnswer::class);
    }

    public function getFieldTypeLabel()
    {
        $labels = [
            'text' => 'Short Text',
            'textarea' => 'Paragraph Text',
            'select' => 'Dropdown Select',
            'radio' => 'Radio Buttons',
            'checkbox' => 'Checkboxes',
            'number' => 'Number',
            'date' => 'Date',
            'file' => 'File Upload',
        ];
        return $labels[$this->field_type] ?? $this->field_type;
    }
}