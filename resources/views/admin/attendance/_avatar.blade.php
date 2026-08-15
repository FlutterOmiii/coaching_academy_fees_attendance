@php use App\Helpers\StorageHelper; @endphp
@if ($student->photo)
    <img src="{{ StorageHelper::url($student->photo) }}" alt=""
        class="object-cover rounded-full w-11 h-11 shrink-0 ring-1 ring-black/5" />
@else
    <span class="grid text-sm font-bold rounded-full shrink-0 w-11 h-11 place-content-center bg-white/70 dark:bg-black/20">
        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
    </span>
@endif
