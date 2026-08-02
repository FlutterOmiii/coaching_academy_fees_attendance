<x-layout.auth>
    <div x-data="loginForm()">
        <!-- Background -->
        <div class="flex items-center justify-center min-h-screen p-4 bg-gray-50 dark:bg-gray-900">

            <!-- Login Container -->
            <div class="w-full max-w-6xl">
                <div class="grid gap-0 overflow-hidden bg-white shadow-2xl lg:grid-cols-2 dark:bg-gray-800 rounded-3xl">

                    <!-- Left Panel - Branding -->
                    <div
                        class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-12 flex flex-col justify-between min-h-[600px]">
                        <!-- Logo -->
                        <div>
                            <img src="/assets/images/logo.png" alt="Logo"
                                class="w-auto h-12 mb-8 brightness-0 invert" />
                            <h1 class="mb-4 text-4xl font-bold text-white">
                                Welcome to<br />Admin Panel
                            </h1>
                            <p class="text-lg text-slate-300">
                                Access your dashboard with secure authentication
                            </p>
                        </div>

                        <!-- Decorative Elements -->
                        <div class="space-y-6">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 backdrop-blur-sm">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white">Secure Login</p>
                                    <p class="text-sm text-slate-400">Enterprise-grade protection</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/10 backdrop-blur-sm">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-white">Fast Access</p>
                                    <p class="text-sm text-slate-400">Quick and reliable</p>
                                </div>
                            </div>
                        </div>

                        <!-- Abstract Shape -->
                        <div class="absolute bottom-0 right-0 w-64 h-64 rounded-full bg-white/5 blur-3xl"></div>
                        <div class="absolute w-32 h-32 rounded-full top-20 right-10 bg-blue-500/10 blur-2xl"></div>
                    </div>

                    <!-- Right Panel - Form -->
                    <div class="flex flex-col justify-center p-12">
                        <div class="w-full max-w-md mx-auto">

                            <!-- Header -->
                            <div class="mb-10">
                                <h2 class="mb-2 text-3xl font-bold text-gray-900 dark:text-white">
                                    Sign In
                                </h2>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Enter your credentials to access your account
                                </p>
                            </div>

                            <!-- Error Messages -->
                            @if (session('error'))
                                <div class="p-4 mb-6 border-l-4 border-red-500 rounded-xl bg-red-50 dark:bg-red-900/20">
                                    <p class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="p-4 mb-6 border-l-4 border-red-500 rounded-xl bg-red-50 dark:bg-red-900/20">
                                    @foreach ($errors->all() as $error)
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Form -->
                            <form method="POST" action="{{ route('admin.signin') }}" class="space-y-6">
                                @csrf

                                <!-- Email Field -->
                                <div class="space-y-2">
                                    <label for="email"
                                        class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        EMAIL
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input id="email" name="email" type="email"
                                            value="{{ old('email') }}" required
                                            class="w-full py-4 pl-12 pr-4 text-gray-900 transition-colors border-2 border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 rounded-xl dark:text-white focus:outline-none focus:border-slate-900 dark:focus:border-slate-400"
                                            placeholder="admin@admin.com" />
                                    </div>
                                </div>

                                <!-- Password Field -->
                                <div class="space-y-2">
                                    <label for="password"
                                        class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        PASSWORD
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input id="password" name="password"
                                            x-bind:type="showPassword ? 'text' : 'password'" required
                                            class="w-full py-4 pl-12 pr-12 text-gray-900 transition-colors border-2 border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 rounded-xl dark:text-white focus:outline-none focus:border-slate-900 dark:focus:border-slate-400"
                                            placeholder="••••••••" />
                                        <button type="button" @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg x-show="!showPassword" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="showPassword" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember Me -->
                                <div class="flex items-center">
                                    <input id="remember" name="remember" type="checkbox"
                                        class="w-4 h-4 border-gray-300 rounded text-slate-900 focus:ring-slate-900 dark:border-gray-600 dark:bg-gray-700" />
                                    <label for="remember" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        Keep me signed in
                                    </label>
                                </div>

                                <!-- Submit Button -->
                                <button type="submit"
                                    class="w-full px-6 py-4 font-semibold text-white transition-all duration-200 shadow-lg bg-slate-900 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 rounded-xl hover:shadow-xl">
                                    Sign In
                                </button>
                            </form>

                            <!-- Footer -->
                            <div class="pt-6 mt-8 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-sm text-center text-gray-600 dark:text-gray-400">
                                    Protected by advanced security protocols
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loginForm() {
            return {
                showPassword: false
            }
        }
    </script>
</x-layout.auth>
