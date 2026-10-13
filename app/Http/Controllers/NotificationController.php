<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi in-app pengguna (lonceng header + halaman /notifikasi).
 */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    /**
     * Halaman daftar seluruh notifikasi pengguna.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        return view('notifications.index', [
            'notifications' => $this->service->paginate($user),
            'unreadCount' => $this->service->unreadCount($user),
        ]);
    }

    /**
     * Data ringkas untuk dropdown lonceng (jumlah belum dibaca + daftar).
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $this->service->unreadCount($user),
            'items' => $this->service->recent($user)->map(fn ($notification): array => [
                'id' => $notification->getKey(),
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'url' => $notification->url,
                'icon' => $notification->icon,
                'read' => $notification->isRead(),
                'time' => $notification->created_at?->diffForHumans(),
            ])->all(),
        ]);
    }

    /**
     * Tandai satu notifikasi telah dibaca.
     */
    public function markRead(Request $request, int $notification): JsonResponse
    {
        $this->service->markRead($request->user(), $notification);

        return response()->json([
            'ok' => true,
            'unread' => $this->service->unreadCount($request->user()),
        ]);
    }

    /**
     * Tandai semua notifikasi telah dibaca.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->service->markAllRead($request->user());

        return response()->json(['ok' => true, 'unread' => 0]);
    }

    /**
     * Hapus satu notifikasi milik pengguna.
     */
    public function destroy(Request $request, int $notification): JsonResponse
    {
        $this->service->delete($request->user(), $notification);

        return response()->json([
            'ok' => true,
            'unread' => $this->service->unreadCount($request->user()),
        ]);
    }
}
