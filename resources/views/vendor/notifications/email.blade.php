{{--
    The template EVERY notification email renders through.

    This file overrides Laravel's built-in "notifications::email" view. The framework looks
    for that override at exactly resources/views/vendor/notifications/email.blade.php, so one
    file brands every mail a Notification sends - the query threads, the opening codes, the
    shared-documents alert, the award result - and brands any notification added later for
    free, with no change to the notification classes themselves.

    Note on the path: "resources/views/vendor/" is Laravel's package-override directory, and
    in this project it is ALSO where the vendor role's own screens live (vendor/notifications/
    index.blade.php is the vendor's bell page). The two are unrelated despite sitting in the
    same folder. Do not move or rename this file: the override only works at this exact path.

    It receives the standard MailMessage fields: $greeting, $introLines, $outroLines,
    $actionText, $actionUrl, $level and $salutation. Lines arrive as Markdown, so **bold**
    is converted below after escaping - escaping first, so a message typed by a vendor or an
    officer into a query box cannot inject markup into everyone else's inbox.
--}}
@php
    $brand = config('app.name');

    $accent = match ($level ?? 'info') {
        'error'   => '#dc2626',
        'success' => '#059669',
        default   => '#1d4ed8',
    };

    $render = function ($line) {
        $text = e(is_string($line) ? $line : (string) $line);
        // Markdown emphasis, after escaping, so only these two become markup.
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $text);
        return nl2br($text);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $brand }}</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1e293b}
.wrapper{max-width:600px;margin:40px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{padding:32px 40px;text-align:center}
.header h1{color:#fff;margin:0;font-size:20px;font-weight:700}
.header p{color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px}
.body{padding:32px 40px}
.greeting{font-size:16px;font-weight:600;color:#0f172a;margin:0 0 18px}
.line{font-size:14px;line-height:1.7;color:#334155;margin:0 0 14px}
.btn-wrap{text-align:center;margin:28px 0}
.btn{display:inline-block;padding:13px 30px;border-radius:8px;color:#fff !important;text-decoration:none;font-size:14px;font-weight:600}
.fallback{font-size:12px;color:#94a3b8;line-height:1.6;margin:0 0 14px;word-break:break-all}
.divider{border:none;border-top:1px solid #e2e8f0;margin:26px 0}
.salutation{font-size:14px;color:#334155;margin:0}
.footer{background:#f8fafc;padding:20px 40px;text-align:center;font-size:12px;color:#94a3b8}
@media (max-width:600px){.wrapper{margin:0;border-radius:0}.header,.body,.footer{padding-left:22px;padding-right:22px}}
</style>
</head>
<body>
<div class="wrapper">
    <div class="header" style="background:linear-gradient(135deg,{{ $accent }},{{ $accent }}cc);">
        <h1>{{ $brand }}</h1>
        <p>Vendor Management &amp; Tender Portal</p>
    </div>

    <div class="body">
        @if(!empty($greeting))
            <p class="greeting">{{ $greeting }}</p>
        @endif

        @foreach(($introLines ?? []) as $line)
            <p class="line">{!! $render($line) !!}</p>
        @endforeach

        @isset($actionUrl)
            <div class="btn-wrap">
                <a href="{{ $actionUrl }}" class="btn" style="background:{{ $accent }};">{{ $actionText }}</a>
            </div>
            {{-- Some mail clients strip buttons, and a notification nobody can act on is
                 worse than a plain link. --}}
            <p class="fallback">If the button does not work, open this link:<br>{{ $actionUrl }}</p>
        @endisset

        @foreach(($outroLines ?? []) as $line)
            <p class="line">{!! $render($line) !!}</p>
        @endforeach

        <hr class="divider">
        {{-- A notification that sets its own salutation replaces the whole sign-off, which
             is how the framework's own template reads it - appending the brand underneath a
             custom one would print the name twice. --}}
        <p class="salutation">
            @if(!empty($salutation))
                {{ $salutation }}
            @else
                Regards,<br>{{ $brand }}
            @endif
        </p>
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ $brand }} &middot; This is an automated notification.
    </div>
</div>
</body>
</html>
