<?php

use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CultureController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExploreMapController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\QrPosterController;
use App\Http\Controllers\SearchSuggestController;
use App\Http\Controllers\TripPlannerController;
use App\Http\Controllers\UmkmController;
use Illuminate\Support\Facades\Route;

// Beranda: satu-satunya halaman yang boleh dibuka tamu.
Route::get('/', [HomeController::class, 'index'])->name('home');

// Ganti bahasa tampilan (default: Inggris). Boleh diakses tamu maupun pengguna.
Route::get('bahasa/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// Rute autentikasi publik (login/daftar/reset kata sandi/verifikasi email) — standar Breeze.
require __DIR__.'/auth.php';

// QR Smart Tourism: pemindaian mencatat kunjungan lalu mengalihkan ke detail.
// Sengaja PUBLIK (di luar public.auth) agar pemindaian tamu tetap tercatat;
// tamu lalu diarahkan ke login dengan URL tujuan tersimpan.
Route::get('/q/{token}', [QrController::class, 'scan'])->name('qr.scan');
Route::get('/q/{token}/gambar', [QrController::class, 'image'])->name('qr.image');
Route::get('/q/{token}/papan', [QrPosterController::class, 'show'])->name('qr.poster');

// Halaman publik lainnya: wajib login (tamu dikembalikan ke beranda + popup).
Route::middleware('public.auth')->group(function (): void {
    Route::get('/destinasi', [DestinationController::class, 'index'])->name('destinations.index');
    Route::get('/destinasi/{slug}', [DestinationController::class, 'show'])->name('destinations.show');

    Route::get('/umkm', [UmkmController::class, 'index'])->name('umkm.index');
    Route::get('/umkm/{slug}', [UmkmController::class, 'show'])->name('umkm.show');

    Route::get('/budaya', [CultureController::class, 'index'])->name('culture.index');
    Route::get('/budaya/{slug}', [CultureController::class, 'show'])->name('culture.show');

    Route::get('/penginapan', [AccommodationController::class, 'index'])->name('accommodations.index');
    Route::get('/penginapan/{slug}', [AccommodationController::class, 'show'])->name('accommodations.show');

    Route::get('/event', [EventController::class, 'index'])->name('events.index');
    Route::get('/event/{slug}', [EventController::class, 'show'])->name('events.show');

    Route::get('/peta', [ExploreMapController::class, 'index'])->name('explore.map');

    // AI Trip Planner (chat bot) — fondasi UI, belum terhubung AI nyata.
    Route::get('/ai-trip-planner', [TripPlannerController::class, 'index'])->name('trip-planner.index');
    Route::post('/ai-trip-planner/kirim', [TripPlannerController::class, 'send'])->name('trip-planner.send');
    Route::post('/ai-trip-planner/simpan-rencana/{message}', [TripPlannerController::class, 'applyPlan'])->name('trip-planner.apply-plan');
    Route::delete('/ai-trip-planner', [TripPlannerController::class, 'clear'])->name('trip-planner.clear');

    // Saran pencarian live (mulai 2 huruf).
    Route::get('/saran/{type}', [SearchSuggestController::class, 'index'])->name('search.suggest');

    // Notifikasi in-app (lonceng header + halaman /notifikasi).
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifikasi/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Engagement wisatawan (favorit & ulasan).
    Route::get('/favorit', [EngagementController::class, 'favorites'])->name('favorites.index');
    Route::post('/favorit/{type}/{id}', [EngagementController::class, 'toggleFavorite'])->name('favorites.toggle');
    Route::post('/ulasan/{type}/{id}', [EngagementController::class, 'storeReview'])->name('reviews.store');

    // Rencana perjalanan (itinerary dasar).
    Route::get('/rencana', [ItineraryController::class, 'index'])->name('itineraries.index');
    Route::post('/rencana', [ItineraryController::class, 'store'])->name('itineraries.store');
    Route::get('/rencana/{itinerary}', [ItineraryController::class, 'show'])->name('itineraries.show');
    Route::delete('/rencana/{itinerary}', [ItineraryController::class, 'destroy'])->name('itineraries.destroy');
    Route::post('/rencana/{itinerary}/butir/{type}/{id}', [ItineraryController::class, 'addItem'])->name('itineraries.items.store');
    Route::post('/rencana/{itinerary}/kegiatan', [ItineraryController::class, 'storeActivity'])->name('itineraries.activities.store');
    Route::patch('/rencana/butir/{item}', [ItineraryController::class, 'updateItem'])->name('itineraries.items.update');
    Route::delete('/rencana/butir/{item}', [ItineraryController::class, 'removeItem'])->name('itineraries.items.destroy');
    Route::post('/rencana/cepat/{type}/{id}', [ItineraryController::class, 'quickAdd'])->name('itineraries.quick-add');

    // Pemesanan DEMO (penginapan & produk UMKM) — diblokir 404 saat fitur nonaktif.
    Route::middleware('feature:booking')->group(function (): void {
        Route::get('/pesanan', [BookingController::class, 'index'])->name('bookings.index');
        Route::post('/pesanan/penginapan/{accommodation:slug}', [BookingController::class, 'storeAccommodation'])->name('bookings.store-accommodation');
        Route::post('/pesanan/produk/{product}', [BookingController::class, 'storeProduct'])->name('bookings.store-product');
        Route::get('/pesanan/{booking:code}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/pesanan/{booking:code}/bayar', [BookingController::class, 'pay'])->name('bookings.pay');
        Route::post('/pesanan/{booking:code}/batal', [BookingController::class, 'cancel'])->name('bookings.cancel');
    });

    // Profil akun (bagian dari autentikasi standar).
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
