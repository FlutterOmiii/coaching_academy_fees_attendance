<x-layout.auth>
    @php $academy = \App\Models\Setting::get('academy_name', 'Cricket Academy'); @endphp
    <div x-data="loginForm()">
        <div class="flex items-center justify-center min-h-screen p-4 bg-[#f6f7fb] dark:bg-[#060818]">

            <div class="w-full max-w-5xl">
                <div class="grid gap-0 overflow-hidden bg-white border shadow-2xl border-black/5 lg:grid-cols-2 dark:border-white/5 dark:bg-[#0e1726] rounded-3xl">

                    {{-- Left — brand (hidden on phones; the form leads there) --}}
                    <div class="relative hidden min-w-0 overflow-hidden p-10 lg:p-12 lg:flex flex-col justify-between min-h-[560px] bg-gradient-to-br from-[#0d1030] via-[#1b2358] to-[#3b52c9]">
                        {{-- Decorative brand glows --}}
                        <div class="absolute rounded-full pointer-events-none -top-16 -right-16 w-72 h-72 bg-primary/30 blur-3xl"></div>
                        <div class="absolute w-56 h-56 rounded-full pointer-events-none bottom-0 -left-10 bg-secondary/20 blur-3xl"></div>
                        {{-- Faint pitch/seam arc motif --}}
                        <svg class="absolute inset-0 w-full h-full opacity-[0.06]" viewBox="0 0 400 400" fill="none" preserveAspectRatio="xMidYMid slice">
                            <circle cx="200" cy="200" r="150" stroke="white" stroke-width="1.5" />
                            <circle cx="200" cy="200" r="110" stroke="white" stroke-width="1.5" stroke-dasharray="6 8" />
                            <path d="M50 200h300M200 50v300" stroke="white" stroke-width="1" />
                        </svg>

                        <div class="relative">
                            <div class="flex items-center gap-3 mb-10">
                                <span class="grid rounded-2xl w-12 h-12 shrink-0 place-content-center bg-white/10 ring-1 ring-white/20 backdrop-blur-sm">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" class="text-white">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                                        <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21"
                                            stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>
                                <span class="text-lg font-bold tracking-tight text-white">{{ $academy }}</span>
                            </div>

                            <h1 class="mb-4 text-3xl font-extrabold leading-tight text-white lg:text-4xl">
                                Run your whole<br />academy in one place.
                            </h1>
                            <p class="max-w-sm text-base leading-relaxed text-white/70">
                                Attendance, batches, fees and player progress — all from a single, simple dashboard.
                            </p>
                        </div>

                        <div class="relative space-y-4">
                            @foreach ([
                                ['M9 12l2 2 4-4', 'One-tap attendance', 'Mark a whole batch in seconds'],
                                ['M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6', 'Fees & reminders', 'Collect payments, nudge on WhatsApp'],
                                ['M3 3v18h18M7 14l3-3 3 3 5-6', 'Player insights', 'See every student\'s progress'],
                            ] as $f)
                                <div class="flex items-center gap-3.5">
                                    <span class="grid w-10 h-10 rounded-xl shrink-0 place-content-center bg-white/10 ring-1 ring-white/15">
                                        <svg class="w-[18px] h-[18px] text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="{{ $f[0] }}" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ $f[1] }}</p>
                                        <p class="text-xs text-white/55">{{ $f[2] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Right — form --}}
                    <div class="flex flex-col justify-center min-w-0 p-6 sm:p-10 lg:p-12">
                        <div class="w-full max-w-md min-w-0 mx-auto">
                            {{-- Compact brand header — phones/tablets only --}}
                            <div class="flex items-center gap-2.5 mb-8 lg:hidden">
                                <span class="grid rounded-xl w-11 h-11 shrink-0 place-content-center bg-primary/10">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="text-primary">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
                                        <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>
                                <span class="text-lg font-bold leading-tight tracking-tight text-black dark:text-white">{{ $academy }}</span>
                            </div>

                            <div class="mb-8">
                                <h2 class="mb-1.5 text-2xl font-extrabold text-black dark:text-white">Welcome back</h2>
                                <p class="text-sm text-white-dark">Sign in to your {{ $academy }} dashboard.</p>
                            </div>

                            @if (session('error'))
                                <div class="flex items-start gap-2 p-3.5 mb-5 text-sm rounded-xl bg-danger/10 text-danger">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01" stroke-linecap="round"/></svg>
                                    <span>{{ session('error') }}</span>
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="p-3.5 mb-5 text-sm rounded-xl bg-danger/10 text-danger">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('admin.signin') }}" class="space-y-5">
                                @csrf

                                <div>
                                    <label for="email" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-white-dark">Email</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-white-dark">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        </span>
                                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                            class="form-input !pl-11" placeholder="you@academy.com" />
                                    </div>
                                </div>

                                <div>
                                    <label for="password" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-white-dark">Password</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-white-dark">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        </span>
                                        <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required
                                            class="form-input !pl-11 !pr-11" placeholder="••••••••" />
                                        <button type="button" @click="showPassword = !showPassword" tabindex="-1"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-white-dark hover:text-primary">
                                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <label for="remember" class="flex items-center gap-2 text-sm cursor-pointer text-white-dark">
                                        <input id="remember" name="remember" type="checkbox" class="form-checkbox !h-4 !w-4" />
                                        Keep me signed in
                                    </label>
                                </div>

                                <button type="submit" class="w-full btn btn-primary btn-lg">Sign In</button>
                            </form>

                            <p class="pt-6 mt-8 text-xs text-center border-t text-white-dark border-black/5 dark:border-white/10">
                                🔒 Secured with encrypted, role-based access.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loginForm() {
            return { showPassword: false }
        }
    </script>
</x-layout.auth>
