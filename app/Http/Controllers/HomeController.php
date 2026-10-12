<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\HomeBanner;
use App\Models\UmkmBusiness;
use App\Services\HomeRecommendationService;
use App\Services\HomeShortcutService;
use App\Support\RegionResolver;

class HomeController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::query()->active()->ordered()->get();

        // Fallback: bila banner belum diatur admin, pakai gambar bawaan (Bromo/Semeru).
        $banners = $banners->isNotEmpty()
            ? $banners->map(fn (HomeBanner $banner): array => [
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'caption' => $banner->caption,
                'image_url' => $banner->imageUrl(),
                'link_url' => $banner->safeLink(),
            ])->all()
            : $this->defaultBanners();

        return view('home', [
            'banners' => $banners,
            'shortcuts' => HomeShortcutService::resolved(),
            'recommendations' => HomeRecommendationService::cards(8),
            'focusRegion' => RegionResolver::focus(),
            'totalDestinations' => Destination::query()->published()->count(),
            'totalUmkm' => UmkmBusiness::query()->published()->count(),
        ]);
    }

    /**
     * Banner bawaan (dipakai bila tabel banner kosong).
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultBanners(): array
    {
        return [
            [
                'title' => 'Pesona Bromo Tengger Semeru',
                'subtitle' => 'Saksikan matahari terbit paling ikonik di Jawa Timur.',
                'caption' => 'Gunung Bromo & Semeru',
                'image_url' => asset('images/auth/bromo-tengger-semeru.jpg'),
                'link_url' => route('destinations.index'),
            ],
            [
                'title' => 'Lautan Pasir & Kawah Bromo',
                'subtitle' => 'Petualangan menembus kaldera Tengger.',
                'caption' => 'Kaldera Tengger',
                'image_url' => asset('images/hero/hero-2.jpg'),
                'link_url' => route('destinations.index'),
            ],
            [
                'title' => 'Jelajahi Probolinggo',
                'subtitle' => 'Destinasi, kuliner, budaya, dan penginapan dalam satu tempat.',
                'caption' => 'Probolinggo, Jawa Timur',
                'image_url' => asset('images/hero/hero-3.jpg'),
                'link_url' => route('destinations.index'),
            ],
        ];
    }
}
