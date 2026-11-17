@extends('layouts.guest')

@section('title', 'Register')

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

        <div class="relative w-full max-w-3xl">
            {{-- mobile / tablet logo --}}
            <div class="mb-6 flex justify-center lg:hidden">
                <a href="{{ url('/') }}" class="ap-logo-tile inline-flex p-2.5 sm:p-3">
                    <img src="{{ asset('img/logo1.jpg') }}" alt="VMS Logo" width="1056" height="1228" class="ap-logo w-20 sm:w-24 md:w-28">
                </a>
            </div>

            <div class="ap-card px-5 pb-7 pt-8 sm:px-10 sm:pb-10 sm:pt-11">
                <div class="ap-card-top"></div>
                @php
                    $certOptions = ['MSME Certificate', 'Udyam Aadhaar', 'Trade License', 'Registration Certificate', 'Other'];
                @endphp

                <h1 class="ap-title text-2xl font-bold tracking-tight sm:text-[1.75rem]">Vendor registration</h1>
                <p class="ap-sub mt-2 text-sm leading-relaxed">Register your company to participate in tenders. Fields marked <span class="ap-req">*</span> are required.</p>

                @if($errors->any())
                    <div role="alert" class="ap-alert ap-alert-error mt-6">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <div>
                            <p class="font-semibold">Please correct the following:</p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="POST" action="/register" enctype="multipart/form-data" class="mt-8 space-y-10">
                    @csrf

                    {{-- 1. Personal information --}}
                    <section class="space-y-5">
                        <h3 class="ap-section"><span class="ap-section-num">1</span> Personal information</h3>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div>
                                <label for="name" class="ap-label">Full name<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-regular fa-user"></i></span>
                                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"
                                           placeholder="Your full name" class="ap-input @error('name') is-invalid @enderror">
                                </div>
                                @error('name')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="ap-label">Email<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-regular fa-envelope"></i></span>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                           placeholder="you@company.com" class="ap-input @error('email') is-invalid @enderror">
                                </div>
                                @error('email')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password" class="ap-label">Password<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-solid fa-lock"></i></span>
                                    <input id="password" type="password" name="password" required autocomplete="new-password"
                                           placeholder="Create a password" class="ap-input has-eye @error('password') is-invalid @enderror">
                                    <button type="button" class="ap-eye" data-toggle-password="password" aria-label="Show password">
                                        <i class="eye-open fa-regular fa-eye"></i><i class="eye-closed fa-regular fa-eye-slash hidden"></i>
                                    </button>
                                </div>
                                @error('password')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="ap-label">Confirm password<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-solid fa-shield-halved"></i></span>
                                    <input id="password_confirmation" type="password" name="password_confirmation" required
                                           autocomplete="new-password" placeholder="Repeat password" class="ap-input has-eye">
                                    <button type="button" class="ap-eye" data-toggle-password="password_confirmation" aria-label="Show password">
                                        <i class="eye-open fa-regular fa-eye"></i><i class="eye-closed fa-regular fa-eye-slash hidden"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 2. Company information --}}
                    <section class="space-y-5">
                        <h3 class="ap-section"><span class="ap-section-num">2</span> Company information</h3>
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="company_name" class="ap-label">Company name<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-regular fa-building"></i></span>
                                    <input id="company_name" type="text" name="company_name" value="{{ old('company_name') }}" required
                                           autocomplete="organization" placeholder="Registered company name"
                                           class="ap-input @error('company_name') is-invalid @enderror">
                                </div>
                                @error('company_name')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="ap-label">Phone<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-solid fa-phone"></i></span>
                                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required autocomplete="tel"
                                           inputmode="tel" placeholder="Contact number" class="ap-input @error('phone') is-invalid @enderror">
                                </div>
                                @error('phone')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="pan_number" class="ap-label">PAN number<span class="ap-req">*</span></label>
                                <div class="ap-field">
                                    <span class="ap-icon"><i class="fa-regular fa-id-card"></i></span>
                                    <input id="pan_number" type="text" name="pan_number" value="{{ old('pan_number') }}" required
                                           placeholder="ABCDE1234F" class="ap-input @error('pan_number') is-invalid @enderror">
                                </div>
                                @error('pan_number')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- GST / supporting certificates --}}
                            <div class="ap-box p-4 sm:p-5 md:col-span-2">
                                <label class="flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" name="has_gst" id="hasGstCheck" value="1" class="ap-check"
                                           onchange="toggleGst(this.checked)" {{ old('has_gst') ? 'checked' : '' }}>
                                    <span class="text-sm font-semibold text-slate-700">I have a GST Number</span>
                                </label>

                                <div id="gstNumberBox" class="mt-4 hidden space-y-4">
                                    <div>
                                        <label for="gstNumberInput" class="ap-label">GST number<span class="ap-req">*</span></label>
                                        <div class="ap-field">
                                            <span class="ap-icon"><i class="fa-solid fa-receipt"></i></span>
                                            <input type="text" name="gst_number" id="gstNumberInput" value="{{ old('gst_number') }}"
                                                   placeholder="e.g. 29ABCDE1234F1Z5" class="ap-input @error('gst_number') is-invalid @enderror">
                                        </div>
                                        @error('gst_number')<p class="ap-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="gstDocInput" class="ap-label">GST certificate / document<span class="ap-req">*</span></label>
                                        <input type="file" name="gst_document" id="gstDocInput" accept=".pdf,.jpg,.jpeg,.png" class="ap-file">
                                        @error('gst_document')
                                            <p class="ap-error">{{ $message }}</p>
                                        @else
                                            <p class="ap-hint">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                                        @enderror
                                    </div>
                                </div>

                                <div id="certificateBox" class="mt-4">
                                    <p class="ap-note mb-3">
                                        <i class="fa-solid fa-circle-info mt-0.5"></i>
                                        No GST? Please upload at least one supporting certificate (MSME, Udyam Aadhaar, etc.)
                                    </p>
                                    <div id="certList" class="space-y-3">
                                        <div class="cert-entry flex flex-col gap-2 sm:flex-row sm:items-center">
                                            <select name="certificate_labels[0]" class="ap-select sm:w-56 sm:flex-none">
                                                @foreach($certOptions as $opt)
                                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                            <input type="file" name="certificates[0]" accept=".pdf,.jpg,.jpeg,.png" class="ap-file flex-1">
                                        </div>
                                    </div>
                                    @error('certificates')<p class="ap-error">{{ $message }}</p>@enderror
                                    <button type="button" onclick="addCertRow()" class="ap-link mt-3 inline-flex items-center gap-1.5 text-sm">
                                        <i class="fa-solid fa-plus text-xs"></i> Add another certificate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- 3. Address --}}
                    <section class="space-y-5">
                        <h3 class="ap-section"><span class="ap-section-num">3</span> Address</h3>
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3">
                            <div>
                                <label for="city" class="ap-label">City<span class="ap-req">*</span></label>
                                <input id="city" type="text" name="city" value="{{ old('city') }}" required autocomplete="address-level2"
                                       class="ap-input no-icon @error('city') is-invalid @enderror">
                                @error('city')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="state" class="ap-label">State<span class="ap-req">*</span></label>
                                <input id="state" type="text" name="state" value="{{ old('state') }}" required autocomplete="address-level1"
                                       class="ap-input no-icon @error('state') is-invalid @enderror">
                                @error('state')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2 md:col-span-1">
                                <label for="pincode" class="ap-label">Pincode<span class="ap-req">*</span></label>
                                <input id="pincode" type="text" name="pincode" value="{{ old('pincode') }}" required autocomplete="postal-code"
                                       inputmode="numeric" class="ap-input no-icon @error('pincode') is-invalid @enderror">
                                @error('pincode')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2 md:col-span-3">
                                <label for="address" class="ap-label">Address<span class="ap-req">*</span></label>
                                <textarea id="address" name="address" rows="3" required autocomplete="street-address"
                                          placeholder="Building, street, area"
                                          class="ap-input no-icon resize-y @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                                @error('address')<p class="ap-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    {{-- 4. Supporting documents --}}
                    <section class="space-y-5">
                        <h3 class="ap-section"><span class="ap-section-num">4</span> Supporting documents</h3>
                        <div>
                            <label for="pan_document" class="ap-label">PAN document (PDF/Image)<span class="ap-req">*</span></label>
                            <input id="pan_document" type="file" name="pan_document" accept=".pdf,.jpg,.jpeg,.png" required class="ap-file">
                            @error('pan_document')
                                <p class="ap-error">{{ $message }}</p>
                            @else
                                <p class="ap-hint">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                            @enderror
                        </div>
                    </section>

                    <button type="submit" class="ap-btn">
                        <i class="fa-solid fa-paper-plane text-sm"></i> Submit registration
                    </button>
                </form>

                <div class="ap-divider mt-8 pt-6 text-center text-sm text-slate-500">
                    Already have an account?
                    <a href="/login" class="ap-link">Sign in</a>
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
let certCount = 1;
const certOptions = ['MSME Certificate', 'Udyam Aadhaar', 'Trade License', 'Registration Certificate', 'Other'];

function toggleGst(hasGst) {
    document.getElementById('gstNumberBox').classList.toggle('hidden', !hasGst);
    document.getElementById('certificateBox').classList.toggle('hidden', hasGst);
    // Toggle required on GST fields
    document.getElementById('gstNumberInput').required = hasGst;
    document.getElementById('gstDocInput').required = hasGst;
}

function addCertRow() {
    const list = document.getElementById('certList');
    const div = document.createElement('div');
    div.className = 'cert-entry flex flex-col gap-2 sm:flex-row sm:items-center';
    div.innerHTML = `<select name="certificate_labels[${certCount}]" class="ap-select sm:w-56 sm:flex-none">
        ${certOptions.map(o => `<option value="${o}">${o}</option>`).join('')}
    </select>
    <input type="file" name="certificates[${certCount}]" accept=".pdf,.jpg,.jpeg,.png" class="ap-file flex-1">
    <button type="button" onclick="this.parentElement.remove()" aria-label="Remove certificate" class="ap-remove self-end sm:self-auto">
        <i class="fa-solid fa-xmark"></i>
    </button>`;
    list.appendChild(div);
    certCount++;
}

// Restore GST state after a failed submission
toggleGst(document.getElementById('hasGstCheck').checked);
</script>
@endsection
