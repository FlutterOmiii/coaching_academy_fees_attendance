@php
    $icons = [
        'students' => 'text-primary',
        'attendance' => 'text-success',
        'fees' => 'text-warning',
        'coaches' => 'text-info',
        'matches' => 'text-secondary',
        'performance' => 'text-danger',
    ];
@endphp

<x-layout.admin title="Reports">

    <x-admin.page-header title="Reports" subtitle="Download academy data as PDF or Excel-ready CSV" :breadcrumbs="['Dashboard' => route('admin.dashboard'), 'Reports' => null]" />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $slug => [$title, $description])
            <div class="panel h-full" x-data="{
                batch: '',
                month: '{{ now()->format('Y-m') }}',
                status: '',
                url(format) {
                    const p = new URLSearchParams({ format });
                    if (this.batch) p.set('batch_id', this.batch);
                    if (this.status) p.set('status', this.status);
                    @if (in_array($slug, ['attendance', 'fees']))
                        if (this.month) p.set('month', this.month);
                    @endif
                    return '{{ route('admin.reports.generate', $slug) }}?' + p.toString();
                }
            }">
                <div class="flex items-start gap-3 mb-4">
                    <span class="grid w-10 h-10 rounded-lg shrink-0 place-content-center bg-primary/10 {{ $icons[$slug] }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path opacity="0.5" d="M5 3C5 2.4 5.4 2 6 2H14L19 7V21C19 21.6 18.6 22 18 22H6C5.4 22 5 21.6 5 21V3Z"
                                stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                            <path d="M14 2V7H19M9 13H15M9 17H13" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h5 class="font-bold dark:text-white-light">{{ $title }}</h5>
                        <p class="text-xs text-white-dark">{{ $description }}</p>
                    </div>
                </div>

                {{-- Only show filters this report actually uses. --}}
                <div class="space-y-2">
                    @if (in_array($slug, ['students', 'attendance', 'fees']))
                        <select x-model="batch" class="text-xs form-select py-1.5">
                            <option value="">All batches</option>
                            @foreach ($batches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if (in_array($slug, ['attendance', 'fees']))
                        <input type="month" x-model="month" max="{{ now()->format('Y-m') }}"
                            class="text-xs form-input py-1.5" />
                    @endif

                    @if ($slug === 'students')
                        <select x-model="status" class="text-xs form-select py-1.5">
                            <option value="">Any status</option>
                            <option value="active">Active only</option>
                            <option value="inactive">Inactive only</option>
                        </select>
                    @endif

                    @if ($slug === 'fees')
                        <select x-model="status" class="text-xs form-select py-1.5">
                            <option value="">All invoices</option>
                            @foreach (\App\Models\FeeInvoice::STATUSES as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="flex gap-2 mt-4">
                    <a :href="url('pdf')" class="btn btn-sm btn-outline-danger flex-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="mr-1">
                            <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        PDF
                    </a>
                    <a :href="url('csv')" class="btn btn-sm btn-outline-success flex-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" class="mr-1">
                            <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Excel/CSV
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel mt-6">
        <p class="text-xs text-white-dark">
            <strong>Note:</strong> CSV files open directly in Excel, Numbers and Google Sheets.
            PDFs are formatted for printing, switching to landscape automatically for wider reports.
        </p>
    </div>

</x-layout.admin>
