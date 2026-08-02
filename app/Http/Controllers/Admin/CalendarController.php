<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $events = Event::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->whereBetween('start_at', [
                $month->copy()->startOfMonth()->startOfDay(),
                $month->copy()->endOfMonth()->endOfDay(),
            ])
            ->orderBy('start_at')
            ->get()
            ->groupBy(fn ($e) => $e->start_at->day);

        // Build the month grid, padded to whole weeks (Monday start).
        $first = $month->copy()->startOfMonth();
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $days[] = [
                'date' => $d->copy(),
                'inMonth' => $d->month === $month->month,
                'events' => $d->month === $month->month ? ($events[$d->day] ?? collect()) : collect(),
            ];
        }

        return view('admin.calendar.index', [
            'month' => $month,
            'days' => $days,
            'upcoming' => Event::upcoming()->limit(8)->get(),
            'eventCount' => $events->flatten()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Event::create($data + [
            'color' => Event::TYPE_COLORS[$data['type']] ?? '#4361ee',
            'created_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Event added to the calendar.');
    }

    public function update(Request $request, Event $event)
    {
        $data = $this->validated($request);

        $event->update($data + ['color' => Event::TYPE_COLORS[$data['type']] ?? $event->color]);

        return back()->with('success', 'Event updated.');
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return back()->with('success', 'Event removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:match,tournament,camp,trial,meeting,holiday,other',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_all_day' => 'nullable|boolean',
            'venue' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);
    }
}
