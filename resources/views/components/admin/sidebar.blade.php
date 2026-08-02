@php
    /**
     * label, icon key, route name, required ability.
     *
     * Each item is filtered by the signed-in admin's abilities, so a role
     * never sees a link that would only answer 403. The Finance group
     * disappears entirely for coaches.
     */
    $sections = [
        'Academy' => [
            ['Students', 'students', 'admin.students.index', 'students.view'],
            ['Coaches', 'coaches', 'admin.coaches.index', 'coaches.view'],
            ['Batches', 'batches', 'admin.batches.index', 'batches.view'],
        ],
        'Operations' => [
            ['Attendance', 'attendance', 'admin.attendance.index', 'attendance.view'],
            ['Training Schedule', 'calendar', 'admin.training.index', 'training.view'],
        ],
        'Finance' => [
            ['Fee Collection', 'fees', 'admin.fees.index', 'fees.view'],
            ['Invoices', 'invoice', 'admin.fees.invoices', 'fees.view'],
            ['Pending Fees', 'pending', 'admin.fees.pending', 'fees.view'],
        ],
        'Insights' => [
            ['Reports', 'report', 'admin.reports.index', 'reports.view'],
            ['Events Calendar', 'calendar', 'admin.calendar.index', 'calendar.view'],
        ],
    ];

    $admin = auth('admin')->user();

    // Drop items this role cannot reach, then drop groups left empty.
    $sections = collect($sections)
        ->map(fn ($items) => collect($items)
            ->filter(fn ($item) => $admin?->hasAbility($item[3]) && \Illuminate\Support\Facades\Route::has($item[2]))
            ->values()
            ->all())
        ->filter(fn ($items) => count($items) > 0)
        ->all();

    $icons = [
        'students' => '<circle cx="12" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/><path opacity="0.5" d="M20 17.5C20 19.9853 20 22 12 22C4 22 4 19.9853 4 17.5C4 15.0147 7.58172 13 12 13C16.4183 13 20 15.0147 20 17.5Z" stroke="currentColor" stroke-width="1.5"/>',
        'coaches' => '<circle cx="12" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><path opacity="0.5" d="M5 21C5 17.134 8.13401 14 12 14C15.866 14 19 17.134 19 21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'batches' => '<path opacity="0.5" d="M3 7C3 5.89543 3.89543 5 5 5H19C20.1046 5 21 5.89543 21 7V17C21 18.1046 20.1046 19 19 19H5C3.89543 19 3 18.1046 3 17V7Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 5V19M16 5V19" stroke="currentColor" stroke-width="1.5"/>',
        'attendance' => '<path opacity="0.5" d="M3 6C3 4.89543 3.89543 4 5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 2V6M16 2V6M8 13L11 16L16 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'calendar' => '<path opacity="0.5" d="M3 8C3 6.89543 3.89543 6 5 6H19C20.1046 6 21 6.89543 21 8V19C21 20.1046 20.1046 21 19 21H5C3.89543 21 3 20.1046 3 19V8Z" stroke="currentColor" stroke-width="1.5"/><path d="M8 3V7M16 3V7M3 11H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'leave' => '<path opacity="0.5" d="M4 5C4 4.44772 4.44772 4 5 4H19C19.5523 4 20 4.44772 20 5V21L12 17L4 21V5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
        'fees' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" opacity="0.5"/><path d="M12 7V17M14.5 9.5C14.5 8.67157 13.3807 8 12 8C10.6193 8 9.5 8.67157 9.5 9.5C9.5 10.3284 10.6193 11 12 11C13.3807 11 14.5 11.6716 14.5 12.5C14.5 13.3284 13.3807 14 12 14C10.6193 14 9.5 13.3284 9.5 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'invoice' => '<path opacity="0.5" d="M5 3H19V21L16 19L13 21L10 19L7 21L5 19V3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M9 8H15M9 12H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'pending' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" opacity="0.5"/><path d="M12 7V12L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'match' => '<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" opacity="0.5"/><path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21" stroke="currentColor" stroke-width="1.5"/>',
        'trophy' => '<path d="M8 4H16V10C16 12.2091 14.2091 14 12 14C9.79086 14 8 12.2091 8 10V4Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path opacity="0.5" d="M8 6H5V7C5 8.65685 6.34315 10 8 10M16 6H19V7C19 8.65685 17.6569 10 16 10M12 14V18M9 21H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'team' => '<circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/><circle cx="17" cy="9" r="2" stroke="currentColor" stroke-width="1.5" opacity="0.5"/><path opacity="0.5" d="M3 20C3 16.6863 5.68629 14 9 14C12.3137 14 15 16.6863 15 20M17 14C19.2091 14 21 15.7909 21 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'chart' => '<path opacity="0.5" d="M3 21H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M7 21V11M12 21V5M17 21V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
        'report' => '<path opacity="0.5" d="M5 3C5 2.44772 5.44772 2 6 2H14L19 7V21C19 21.5523 18.5523 22 18 22H6C5.44772 22 5 21.5523 5 21V3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M14 2V7H19M9 13H15M9 17H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
    ];
@endphp

<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar"
        class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex items-center justify-between px-4 py-3">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 main-logo shrink-0">
                    <span class="grid w-10 h-10 rounded-full shrink-0 place-content-center bg-primary/10">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="text-primary">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5" />
                            <path d="M12 3C12 3 9 7 9 12C9 17 12 21 12 21M12 3C12 3 15 7 15 12C15 17 12 21 12 21"
                                stroke="currentColor" stroke-width="1.5" />
                        </svg>
                    </span>
                    <span
                        class="text-lg font-bold align-middle lg:inline dark:text-white-light leading-tight">{{ \App\Models\Setting::get('academy_name', 'Cricket Academy') }}</span>
                </a>
                <a href="javascript:;"
                    class="flex items-center w-8 h-8 transition duration-300 rounded-full collapse-icon hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light rtl:rotate-180"
                    @click="$store.app.toggleSidebar()">
                    <svg class="w-5 h-5 m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>

            <ul class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-80px)] overflow-y-auto overflow-x-hidden p-4 py-0">

                <li class="menu nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20"
                                viewBox="0 0 24 24" fill="none">
                                <path opacity="0.5"
                                    d="M2 12.2039C2 9.91549 2 8.77128 2.5192 7.82274C3.0384 6.87421 3.98695 6.28551 5.88403 5.10813L7.88403 3.86687C9.88939 2.62229 10.8921 2 12 2C13.1079 2 14.1106 2.62229 16.116 3.86687L18.116 5.10812C20.0131 6.28551 20.9616 6.87421 21.4808 7.82274C22 8.77128 22 9.91549 22 12.2039V13.725C22 17.6258 22 19.5763 20.8284 20.7881C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.7881C2 19.5763 2 17.6258 2 13.725V12.2039Z"
                                    fill="currentColor" />
                                <path
                                    d="M9 17.25C8.58579 17.25 8.25 17.5858 8.25 18C8.25 18.4142 8.58579 18.75 9 18.75H15C15.4142 18.75 15.75 18.4142 15.75 18C15.75 17.5858 15.4142 17.25 15 17.25H9Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">Dashboard</span>
                        </div>
                    </a>
                </li>

                @foreach ($sections as $heading => $items)
                    <h2
                        class="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1 text-xs tracking-wide">
                        <span>{{ $heading }}</span>
                    </h2>

                    @foreach ($items as [$label, $icon, $routeName, $ability])
                        <li class="menu nav-item">
                            <a href="{{ route($routeName) }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="20" height="20"
                                        viewBox="0 0 24 24" fill="none">{!! $icons[$icon] !!}</svg>
                                    <span
                                        class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">{{ $label }}</span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                @endforeach

            </ul>
        </div>
    </nav>
</div>

<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("sidebar", () => ({
            activeDropdown: null
        }));
    });

    document.addEventListener("DOMContentLoaded", function() {
        const currentPath = window.location.pathname.replace(/\/$/, '');
        const links = document.querySelectorAll('.sidebar a[href]');
        links.forEach(function(link) {
            let linkPath = link.getAttribute('href').replace(/\/$/, '');
            if (linkPath.startsWith('http')) {
                linkPath = new URL(linkPath).pathname.replace(/\/$/, '');
            }
            if (linkPath === currentPath) {
                link.classList.add('active');
                const parentLi = link.closest('ul.sub-menu')?.previousElementSibling;
                if (parentLi) {
                    parentLi.click();
                }
            }
        });
    });
</script>
