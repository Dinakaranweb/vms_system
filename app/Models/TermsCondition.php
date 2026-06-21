<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TermsCondition extends Model
{
    protected $table = 'terms_conditions';

    protected $fillable = ['title', 'content', 'applicable_to', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('applicable_to', $type)->orWhere('applicable_to', 'both');
        });
    }

    /** Returns keyed array [title => content] for a given order type — used by WorkOrderController */
    public static function presetsFor(string $type): array
    {
        return static::active()->forType($type)->orderBy('sort_order')->pluck('content', 'title')->toArray();
    }
}
