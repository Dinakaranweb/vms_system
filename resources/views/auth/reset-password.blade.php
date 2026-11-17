@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
{{-- Auth page styles (self-contained; solid colours only: blue #2e6f9f, maroon #a82d32) --}}
<style>
    :root {
        --ap-blue: #2e6f9f; --ap-blue-dark: #245a82; --ap-blue-darker: #1c4868; --ap-blue-soft: #eef4f9;
        --ap-maroon: #a82d32; --ap-maroon-dark: #8c2529; --ap-maroon-soft: #fbf0f0;
        --ap-ink: #1e293b; --ap-muted: #64748b; --ap-line: #dbe2ea; --ap-field: #f8fafc; --ap-page: #f1f4f8;
    }
    body { background-color: var(--ap-page); color: var(--ap-ink); }
    .ap-panel { background-color: var(--ap-blue); }
    .ap-bar { background-color: var(--ap-maroon); }
    .ap-logo-tile { background-color: #fff; border-radius: 1.25rem; box-shadow: 0 12px 30px -12px rgba(15, 40, 65, .45); }
    .ap-logo { display: block; height: auto; max-width: 100%; object-fit: contain; }
    .ap-card { position: relative; overflow: hidden; background-color: #fff; border: 1px solid var(--ap-line); border-radius: 1.25rem; box-shadow: 0 1px 2px rgba(16,24,40,.04), 0 20px 40px -24px rgba(28,72,104,.35); }
    .ap-card-top { position: absolute; inset: 0 0 auto 0; height: 4px; background-color: var(--ap-maroon); }
    .ap-title { color: var(--ap-ink); }
    .ap-sub { color: var(--ap-muted); }
    .ap-label { display: block; margin-bottom: .4rem; font-size: .875rem; font-weight: 600; color: #334155; }
    .ap-req { color: var(--ap-maroon); margin-left: 2px; }
    .ap-field { position: relative; }
    .ap-icon { position: absolute; top: 0; bottom: 0; left: 0; display: flex; align-items: center; padding-left: .95rem; color: #94a3b8; pointer-events: none; transition: color .15s; }
    .ap-field:focus-within .ap-icon { color: var(--ap-blue); }
    .ap-input, .ap-select { display: block; width: 100%; border: 1.5px solid var(--ap-line); border-radius: .75rem; background-color: var(--ap-field); color: var(--ap-ink); font-size: .9rem; line-height: 1.25rem; padding: .8rem 1rem .8rem 2.75rem; transition: border-color .15s, box-shadow .15s, background-color .15s; }
    .ap-input.no-icon, .ap-select { padding-left: 1rem; }
    .ap-input.has-eye { padding-right: 3rem; }
    .ap-input::placeholder { color: #9aa6b4; }
    .ap-input:hover, .ap-select:hover { border-color: #c3ceda; }
    .ap-input:focus, .ap-select:focus { outline: none; background-color: #fff; border-color: var(--ap-blue); box-shadow: 0 0 0 4px rgba(46,111,159,.15); }
    .ap-input.is-invalid { border-color: var(--ap-maroon); background-color: #fff; }
    .ap-input.is-invalid:focus { box-shadow: 0 0 0 4px rgba(168,45,50,.15); }
    .ap-error { margin-top: .4rem; font-size: .78rem; font-weight: 500; color: var(--ap-maroon); }
    .ap-file { display: block; width: 100%; font-size: .875rem; color: var(--ap-muted); border: 1.5px dashed #c3ceda; border-radius: .75rem; background-color: #fff; padding: .45rem; cursor: pointer; transition: border-color .15s, background-color .15s; }
    .ap-file:hover { border-color: var(--ap-blue); background-color: var(--ap-blue-soft); }
    .ap-file::file-selector-button { margin-right: .8rem; border: 0; border-radius: .5rem; cursor: pointer; background-color: var(--ap-blue); color: #fff; font-weight: 600; font-size: .8rem; padding: .55rem 1rem; transition: background-color .15s; }
    .ap-file::file-selector-button:hover { background-color: var(--ap-blue-dark); }
    .ap-hint { margin-top: .4rem; font-size: .75rem; color: var(--ap-muted); }
    .ap-check { width: 1.05rem; height: 1.05rem; accent-color: var(--ap-blue); cursor: pointer; }
    .ap-eye { position: absolute; top: 0; bottom: 0; right: 0; display: flex; align-items: center; padding: 0 1rem; color: #94a3b8; background: none; border: 0; cursor: pointer; transition: color .15s; }
    .ap-eye:hover, .ap-eye:focus-visible { color: var(--ap-blue); outline: none; }
    .ap-btn { display: inline-flex; width: 100%; align-items: center; justify-content: center; gap: .55rem; border: 0; border-radius: .75rem; padding: .9rem 1rem; font-size: .925rem; font-weight: 600; color: #fff; cursor: pointer; background-color: var(--ap-blue); box-shadow: 0 6px 16px -8px rgba(46,111,159,.75); transition: background-color .15s, box-shadow .15s; }
    .ap-btn:hover { background-color: var(--ap-blue-dark); }
    .ap-btn:active { background-color: var(--ap-blue-darker); }
    .ap-btn:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(46,111,159,.3); }
    .ap-link { color: var(--ap-blue); font-weight: 600; transition: color .15s; }
    .ap-link:hover { color: var(--ap-maroon); }
    .ap-section { display: flex; align-items: center; gap: .75rem; font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ap-blue-darker); }
    .ap-section::after { content: ''; flex: 1; height: 1px; background-color: var(--ap-line); }
    .ap-section-num { display: flex; flex: none; align-items: center; justify-content: center; width: 1.85rem; height: 1.85rem; border-radius: .5rem; background-color: var(--ap-maroon-soft); color: var(--ap-maroon); font-size: .8rem; box-shadow: inset 0 0 0 1px rgba(168,45,50,.2); }
    .ap-box { border: 1px solid var(--ap-line); border-radius: 1rem; background-color: var(--ap-field); }
    .ap-note { display: flex; align-items: flex-start; gap: .5rem; border-radius: .75rem; padding: .65rem .8rem; font-size: .78rem; line-height: 1.45; background-color: var(--ap-maroon-soft); color: var(--ap-maroon-dark); box-shadow: inset 0 0 0 1px rgba(168,45,50,.18); }
    .ap-alert { display: flex; align-items: flex-start; gap: .75rem; border-radius: .75rem; padding: .85rem 1rem; font-size: .875rem; }
    .ap-alert-error { background-color: var(--ap-maroon-soft); color: var(--ap-maroon-dark); border: 1px solid rgba(168,45,50,.25); }
    .ap-alert-success { background-color: #ecfdf3; color: #067647; border: 1px solid #abefc6; }
    .ap-divider { border-top: 1px solid #edf1f5; }
    .ap-remove { display: flex; flex: none; align-items: center; justify-content: center; width: 2.5rem; height: 2.5rem; border-radius: .5rem; color: #94a3b8; transition: color .15s, background-color .15s; }
    .ap-remove:hover { color: var(--ap-maroon); background-color: var(--ap-maroon-soft); }
</style>

<div class="min-h-screen lg:flex">

    {{-- Brand panel (laptop / desktop) --}}
    <aside class="ap-panel relative hidden text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-5/12 lg:flex-col lg:justify-between lg:p-12 xl:w-[42%] xl:p-16">
        <div class="ap-bar absolute inset-x-0 bottom-0 h-1.5"></div>

        <a href="{{ url('/') }}" class="ap-logo-tile inline-flex w-fit p-3 xl:p-4">
            <img src="{{ asset('img/logo1.jpg') }}" alt="VMS Logo" width="1056" height="1228" class="ap-logo w-28 xl:w-36">
        </a>

        <div class="max-w-md">
            <div class="ap-bar mb-6 h-1 w-14 rounded-full"></div>
            <h2 class="text-4xl font-bold leading-tight tracking-tight">Vendor management,<br>made simple.</h2>
            <p class="mt-5 text-base leading-relaxed text-white/80">
                One secure place to register, get verified and stay connected with the university.
            </p>
            <ul class="mt-10 space-y-4 text-sm text-white/90">
                @foreach([['fa-chart-line', 'Real-time application tracking'], ['fa-shield-halved', 'Secure document uploads'], ['fa-address-book', 'Centralised vendor directory']] as [$apIcon, $apText])
                    <li class="flex items-center gap-3">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/20">
                            <i class="fa-solid {{ $apIcon }} text-sm"></i>
                        </span>
                        {{ $apText }}
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="text-xs text-white/60">&copy; {{ date('Y') }} VMS Portal. All rights reserved.</p>
    </aside>

    {{-- Form side --}}
    <main class="relative flex flex-1 justify-center px-4 py-8 sm:px-8 sm:py-12 lg:items-center lg:py-14">
        {{-- mobile / tablet header band --}}
        <div class="ap-panel pointer-events-none absolute inset-x-0 top-0 h-48 sm:h-56 lg:hidden">
            <div class="ap-bar absolute inset-x-0 bottom-0 h-1"></div>
        </div>

        <div class="relative w-full max-w-md">
            {{-- mobile / tablet logo --}}
            <div class="mb-6 flex justify-center lg:hidden">
                <a href="{{ url('/') }}" class="ap-logo-tile inline-flex p-2.5 sm:p-3">
                    <img src="{{ asset('img/logo1.jpg') }}" alt="VMS Logo" width="1056" height="1228" class="ap-logo w-20 sm:w-24 md:w-28">
                </a>
            </div>

            <div class="ap-card px-5 pb-7 pt-8 sm:px-10 sm:pb-10 sm:pt-11">
                <div class="ap-card-top"></div>
                <h1 class="ap-title text-2xl font-bold tracking-tight sm:text-[1.75rem]">Reset your password</h1>
                <p class="ap-sub mt-2 text-sm leading-relaxed">Confirm your email address and choose a new password.</p>

                @if($errors->any())
                    <div role="alert" class="ap-alert ap-alert-error mt-6">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="ap-label">Email address</label>
                        <div class="ap-field">
                            <span class="ap-icon"><i class="fa-regular fa-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                                   autocomplete="username" placeholder="you@example.com"
                                   class="ap-input @error('email') is-invalid @enderror">
                        </div>
                        @error('email')<p class="ap-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="ap-label">New password</label>
                        <div class="ap-field">
                            <span class="ap-icon"><i class="fa-solid fa-lock"></i></span>
                            <input id="password" type="password" name="password" required minlength="8"
                                   autocomplete="new-password" placeholder="Min. 8 characters"
                                   class="ap-input has-eye @error('password') is-invalid @enderror">
                            <button type="button" class="ap-eye" data-toggle-password="password" aria-label="Show password">
                                <i class="eye-open fa-regular fa-eye"></i><i class="eye-closed fa-regular fa-eye-slash hidden"></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="ap-error">{{ $message }}</p>
                        @else
                            <p class="ap-hint">Use at least 8 characters.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="ap-label">Confirm new password</label>
                        <div class="ap-field">
                            <span class="ap-icon"><i class="fa-solid fa-shield-halved"></i></span>
                            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                                   autocomplete="new-password" placeholder="Repeat new password" class="ap-input has-eye">
                            <button type="button" class="ap-eye" data-toggle-password="password_confirmation" aria-label="Show password">
                                <i class="eye-open fa-regular fa-eye"></i><i class="eye-closed fa-regular fa-eye-slash hidden"></i>
                            </button>
                        </div>
                        <p id="pw-match" class="ap-hint hidden" aria-live="polite"></p>
                    </div>

                    <button type="submit" class="ap-btn">
                        <i class="fa-solid fa-key text-sm"></i> Reset password
                    </button>
                </form>

                <div class="ap-divider mt-8 pt-6 text-center">
                    <a href="{{ route('login') }}" class="ap-link inline-flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Back to login
                    </a>
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-slate-500">
                <span class="lg:hidden">&copy; {{ date('Y') }} VMS Portal &middot; </span>
                Designed &amp; Developed by
                <a href="https://vmrfdu.edu.in" target="_blank" rel="noopener" class="ap-link font-medium">VMRF(DU) IT Team</a>
            </p>
        </div>
    </main>
</div>

<script>
    // Show / hide password for any <button data-toggle-password="inputId">
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('.eye-open').classList.toggle('hidden', show);
        btn.querySelector('.eye-closed').classList.toggle('hidden', !show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
</script>
<script>
    (function () {
        var pw = document.getElementById('password');
        var cf = document.getElementById('password_confirmation');
        var msg = document.getElementById('pw-match');
        function check() {
            if (!cf.value) { msg.classList.add('hidden'); return; }
            var ok = pw.value === cf.value;
            msg.textContent = ok ? 'Passwords match' : 'Passwords do not match';
            msg.className = ok ? 'ap-hint' : 'ap-error';
            msg.style.color = ok ? '#067647' : '';
        }
        pw.addEventListener('input', check);
        cf.addEventListener('input', check);
    })();
</script>
@endsection
