<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderView extends Model
{
    protected $table    = 'tender_views';
    public    $timestamps = false;

    protected $fillable = ['tender_id', 'user_id', 'ip_address', 'user_agent'];

    protected $casts = ['viewed_at' => 'datetime'];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function parseBrowser(?string $ua): string
    {
        if (!$ua) return 'Unknown';
        if (str_contains($ua, 'Edg/'))    return 'Edge';
        if (str_contains($ua, 'OPR/'))    return 'Opera';
        if (str_contains($ua, 'Chrome/')) return 'Chrome';
        if (str_contains($ua, 'Firefox/'))return 'Firefox';
        if (str_contains($ua, 'Safari/') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident/')) return 'IE';
        if (str_contains($ua, 'curl'))    return 'curl/API';
        if (str_contains($ua, 'PostmanRuntime')) return 'Postman';
        return 'Other';
    }

    public static function parseOs(?string $ua): string
    {
        if (!$ua) return '';
        if (str_contains($ua, 'Windows NT')) return 'Windows';
        if (str_contains($ua, 'Mac OS X'))  return 'macOS';
        if (str_contains($ua, 'Android'))   return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux'))     return 'Linux';
        return '';
    }
}
