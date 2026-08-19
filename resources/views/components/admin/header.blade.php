<header class="z-40" :class="{ 'dark': $store.app.semidark && $store.app.menu === 'horizontal' }">
    <div class="shadow-sm">
        <div class="relative bg-white flex w-full items-center px-5 py-2.5 dark:bg-[#0e1726]">
            <div class="flex items-center justify-between horizontal-logo lg:hidden ltr:mr-2 rtl:ml-2">
                @php $academyLogo = \App\Models\Setting::get('academy_logo'); @endphp
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 main-logo shrink-0">
                    @if ($academyLogo)
                        <img src="{{ \App\Helpers\StorageHelper::url($academyLogo) }}" alt="Logo"
                            class="object-contain rounded-lg w-9 h-9 shrink-0" />
                    @else
                        <span class="grid rounded-full w-9 h-9 shrink-0 place-content-center bg-primary/10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-primary">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" />
                                <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21"
                                    stroke="currentColor" stroke-width="1.5" />
                            </svg>
                        </span>
                    @endif
                    <span class="text-base font-bold leading-tight truncate max-w-[44vw] dark:text-white-light">{{ \App\Models\Setting::get('academy_name', 'Cricket Academy') }}</span>
                </a>

                <a href="javascript:;"
                    class="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                    @click="$store.app.toggleSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5"
                            stroke-linecap="round" />
                        <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </a>
            </div>

            <div class="hidden ltr:mr-2 rtl:ml-2 sm:block"></div>

            <div
                class="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex items-center space-x-1.5 lg:space-x-2 rtl:space-x-reverse dark:text-[#d0d2d6]">
                <div class="sm:ltr:mr-auto sm:rtl:ml-auto"></div>

                <!-- Theme Toggle -->
                <div>
                    <a href="javascript:;" x-cloak x-show="$store.app.theme === 'light'"
                        class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                        @click="$store.app.toggleTheme('dark')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M12 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M4 12L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path d="M22 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                            <path opacity="0.5" d="M19.7778 4.22266L17.5558 6.25424" stroke="currentColor"
                                stroke-width="1.5" stroke-linecap="round" />
                            <path opacity="0.5" d="M4.22217 4.22266L6.44418 6.25424" stroke="currentColor"
                                stroke-width="1.5" stroke-linecap="round" />
                            <path opacity="0.5" d="M6.44434 17.5557L4.22211 19.7779" stroke="currentColor"
                                stroke-width="1.5" stroke-linecap="round" />
                            <path opacity="0.5" d="M19.7778 19.7773L17.5558 17.5551" stroke="currentColor"
                                stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </a>
                    <a href="javascript:;" x-cloak x-show="$store.app.theme === 'dark'"
                        class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                        @click="$store.app.toggleTheme('system')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M21.0672 11.8568L20.4253 11.469L21.0672 11.8568ZM12.1432 2.93276L11.7553 2.29085V2.29085L12.1432 2.93276ZM21.25 12C21.25 17.1086 17.1086 21.25 12 21.25V22.75C17.9371 22.75 22.75 17.9371 22.75 12H21.25ZM12 21.25C6.89137 21.25 2.75 17.1086 2.75 12H1.25C1.25 17.9371 6.06294 22.75 12 22.75V21.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75V1.25C6.06294 1.25 1.25 6.06294 1.25 12H2.75ZM15.5 14.25C12.3244 14.25 9.75 11.6756 9.75 8.5H8.25C8.25 12.5041 11.4959 15.75 15.5 15.75V14.25ZM20.4253 11.469C19.4172 13.1373 17.5882 14.25 15.5 14.25V15.75C18.1349 15.75 20.4407 14.3439 21.7092 12.2447L20.4253 11.469ZM9.75 8.5C9.75 6.41182 10.8627 4.5828 12.531 3.57467L11.7553 2.29085C9.65609 3.5593 8.25 5.86509 8.25 8.5H9.75ZM12 2.75C11.9115 2.75 11.8077 2.71008 11.7324 2.63168C11.6686 2.56527 11.6538 2.50244 11.6503 2.47703C11.6461 2.44587 11.6482 2.35557 11.7553 2.29085L12.531 3.57467C13.0342 3.27065 13.196 2.71398 13.1368 2.27627C13.0754 1.82126 12.7166 1.25 12 1.25V2.75ZM21.7092 12.2447C21.6444 12.3518 21.5541 12.3539 21.523 12.3497C21.4976 12.3462 21.4347 12.3314 21.3683 12.2676C21.2899 12.1923 21.25 12.0885 21.25 12H22.75C22.75 11.2834 22.1787 10.9246 21.7237 10.8632C21.286 10.804 20.7293 10.9658 20.4253 11.469L21.7092 12.2447Z"
                                fill="currentColor" />
                        </svg>
                    </a>
                    <a href="javascript:;" x-cloak x-show="$store.app.theme === 'system'"
                        class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60"
                        @click="$store.app.toggleTheme('light')">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M3 9C3 6.17157 3 4.75736 3.87868 3.87868C4.75736 3 6.17157 3 9 3H15C17.8284 3 19.2426 3 20.1213 3.87868C21 4.75736 21 6.17157 21 9V14C21 15.8856 21 16.8284 20.4142 17.4142C19.8284 18 18.8856 18 17 18H7C5.11438 18 4.17157 18 3.58579 17.4142C3 16.8284 3 15.8856 3 14V9Z"
                                stroke="currentColor" stroke-width="1.5" />
                            <path opacity="0.5" d="M22 21H2" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                            <path opacity="0.5" d="M15 15H9" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                    </a>
                </div>

                <!-- User Dropdown -->
                <div class="flex-shrink-0 dropdown" x-data="dropdown" @click.outside="open = false">
                    <a href="javascript:;" class="relative group" @click="toggle()">
                        <span
                            class="flex items-center justify-center object-cover w-10 h-10 text-base text-white text-center rounded-full bg-primary">
                            {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                        </span>
                    </a>
                    <ul x-cloak x-show="open" x-transition x-transition.duration.300ms
                        class="ltr:right-0 rtl:left-0 text-dark dark:text-white-dark top-11 !py-0 w-[230px] font-semibold dark:text-white-light/90">
                        <li>
                            <div class="flex items-center px-4 py-4">
                                <div class="flex-none">
                                    <span
                                        class="flex items-center justify-center object-cover w-10 h-10 text-base text-white text-center rounded-full bg-primary">
                                        {{ strtoupper(substr(Auth::guard('admin')->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div class="truncate ltr:pl-4 rtl:pr-4">
                                    <h4 class="text-base">{{ Auth::guard('admin')->user()->name }}</h4>
                                    <a class="text-black/60 hover:text-primary dark:text-dark-light/60 dark:hover:text-white"
                                        href="javascript:;">{{ Auth::guard('admin')->user()->email }}</a>
                                </div>
                            </div>
                        </li>
                        @ability('settings.manage')
                            <li class="border-t border-white-light dark:border-white-light/10">
                                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-2 px-4 py-3 hover:text-primary">
                                    <svg class="w-4.5 h-4.5" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
                                        <path opacity="0.5" d="M12 2C11.068 2 10.602 2 10.235 2.152C9.745 2.355 9.356 2.744 9.153 3.234C9.032 3.527 9.008 3.888 9.003 4.5C8.995 5.451 8.492 6.319 7.669 6.795C6.845 7.271 5.842 7.26 5.009 6.8C4.473 6.504 4.134 6.379 3.82 6.42C3.296 6.489 2.83 6.784 2.549 7.232C2.336 7.572 2.336 8.038 2.336 8.97V15.03C2.336 15.962 2.336 16.428 2.549 16.768C2.83 17.216 3.296 17.511 3.82 17.58C4.134 17.621 4.473 17.496 5.009 17.2C5.842 16.74 6.845 16.729 7.669 17.205C8.492 17.681 8.995 18.549 9.003 19.5C9.008 20.112 9.032 20.473 9.153 20.766C9.356 21.256 9.745 21.645 10.235 21.848C10.602 22 11.068 22 12 22C12.932 22 13.398 22 13.765 21.848C14.255 21.645 14.644 21.256 14.847 20.766C14.968 20.473 14.992 20.112 14.997 19.5C15.005 18.549 15.508 17.681 16.331 17.205C17.155 16.729 18.158 16.74 18.991 17.2C19.527 17.496 19.866 17.621 20.18 17.58C20.704 17.511 21.17 17.216 21.451 16.768C21.664 16.428 21.664 15.962 21.664 15.03V8.97C21.664 8.038 21.664 7.572 21.451 7.232C21.17 6.784 20.704 6.489 20.18 6.42C19.866 6.379 19.527 6.504 18.991 6.8C18.158 7.26 17.155 7.271 16.331 6.795C15.508 6.319 15.005 5.451 14.997 4.5C14.992 3.888 14.968 3.527 14.847 3.234C14.644 2.744 14.255 2.355 13.765 2.152C13.398 2 12.932 2 12 2Z" stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                    Settings
                                </a>
                            </li>
                        @endability
                        <li class="border-t border-white-light dark:border-white-light/10">
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-danger !py-3 w-full text-left flex items-center">
                                    <svg class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0 rotate-90" width="18"
                                        height="18" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.5"
                                            d="M17 9.00195C19.175 9.01406 20.3529 9.11051 21.1213 9.8789C22 10.7576 22 12.1718 22 15.0002V16.0002C22 18.8286 22 20.2429 21.1213 21.1215C20.2426 22.0002 18.8284 22.0002 16 22.0002H8C5.17157 22.0002 3.75736 22.0002 2.87868 21.1215C2 20.2429 2 18.8286 2 16.0002L2 15.0002C2 12.1718 2 10.7576 2.87868 9.87889C3.64706 9.11051 4.82497 9.01406 7 9.00195"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M12 15L12 2M12 2L15 5.5M12 2L9 5.5" stroke="currentColor"
                                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
