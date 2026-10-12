<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'jenis' => ['nullable', Rule::enum(EventType::class)],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'masa' => ['nullable', Rule::in(['akan_datang', 'arsip', 'semua'])],
        ]);

        $masa = $filters['masa'] ?? 'akan_datang';

        $events = Event::published()
            ->with(['region.parent.parent'])
            ->withCount(['publishedReviews as reviews_count'])
            ->withAvg(['publishedReviews as reviews_avg'], 'rating')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('location_name', 'like', "%{$term}%");
                });
            })
            ->when($filters['jenis'] ?? null, fn ($query, string $type) => $query->where('event_type', $type))
            ->when($filters['bulan'] ?? null, function ($query, string $month) {
                [$year, $monthNumber] = explode('-', $month);
                $start = now()->setDate((int) $year, (int) $monthNumber, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                $query->where(function ($query) use ($start, $end) {
                    $query->whereBetween('starts_at', [$start, $end])
                        ->orWhereBetween('ends_at', [$start, $end])
                        ->orWhere(fn ($query) => $query->where('starts_at', '<=', $start)->where('ends_at', '>=', $end));
                });
            })
            ->when($masa === 'akan_datang', fn ($query) => $query->upcoming())
            ->when($masa === 'arsip', fn ($query) => $query->past())
            ->orderBy('starts_at')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'types' => EventType::options(),
            'filters' => $filters,
            'masa' => $masa,
        ]);
    }

    public function show(string $slug)
    {
        $event = Event::published()
            ->with(['region.parent.parent', 'village'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Event::published()
            ->where('id', '!=', $event->id)
            ->when($event->event_type, fn ($query, $type) => $query->where('event_type', $type))
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        return view('events.show', [
            'event' => $event,
            'related' => $related,
        ]);
    }
}
