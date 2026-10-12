<?php

namespace App\Http\Controllers;

use App\Services\TripPlannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TripPlannerController extends Controller
{
    public function __construct(private readonly TripPlannerService $service) {}

    /**
     * Halaman chat bot AI Trip Planner.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('trip-planner.index', [
            'messages' => $this->service->messages($user),
            'suggestions' => $this->suggestions(),
            'remainingPrompts' => $this->service->remainingPrompts($user),
            'dailyLimit' => $this->service->dailyPromptLimit(),
        ]);
    }

    /**
     * Kirim pesan & dapatkan balasan asisten (AJAX).
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        try {
            $messages = $this->service->send($user, $data['message']);
        } catch (RuntimeException $exception) {
            $isTokenLimit = $exception->getMessage() === 'token_limit';

            return response()->json([
                'quota_exceeded' => true,
                'message' => $isTokenLimit
                    ? __('ui.trip_planner_token_limit_exceeded')
                    : __('ui.trip_planner_quota_exceeded', ['limit' => $this->service->dailyPromptLimit()]),
                'remaining' => $this->service->remainingPrompts($user),
            ], 429);
        }

        return response()->json([
            'messages' => $messages,
            'remaining' => $this->service->remainingPrompts($user),
            'limit' => $this->service->dailyPromptLimit(),
        ]);
    }

    /**
     * Kosongkan percakapan (AJAX).
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        $this->service->clear($user);

        return response()->json([
            'messages' => [],
            'welcome' => $this->service->welcomeMessage(),
            'suggestions' => $this->suggestions(),
            'remaining' => $this->service->remainingPrompts($user),
            'limit' => $this->service->dailyPromptLimit(),
        ]);
    }

    /**
     * Saran cepat yang ditampilkan sebagai chip di halaman.
     *
     * @return array<int, string>
     */
    private function suggestions(): array
    {
        return [
            __('ui.trip_planner_suggest_itinerary'),
            __('ui.trip_planner_suggest_culinary'),
            __('ui.trip_planner_suggest_nature'),
            __('ui.trip_planner_suggest_budget'),
        ];
    }
}
