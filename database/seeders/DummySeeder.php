<?php

namespace Database\Seeders;

use App\Enums\AccommodationType;
use App\Enums\BusinessType;
use App\Enums\ContentStatus;
use App\Enums\EventType;
use App\Enums\RegionType;
use App\Enums\UserRole;
use App\Models\Accommodation;
use App\Models\AppSetting;
use App\Models\Destination;
use App\Models\DestinationCategory;
use App\Models\Event;
use App\Models\Region;
use App\Models\Review;
use App\Models\UmkmBusiness;
use App\Models\User;
use App\Support\RegionResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data dummy untuk pengembangan (masing-masing 10 destinasi, UMKM,
 * penginapan, dan event di kabupaten fokus, plus sebagian ulasan rating
 * agar seksi "Rekomendasi" tampil).
 *
 * Bersifat idempoten (aman dijalankan berulang) dan TIDAK dipanggil oleh
 * DatabaseSeeder default. Jalankan manual:
 * `php artisan db:seed --class=DummySeeder`.
 */
class DummySeeder extends Seeder
{
    public function run(): void
    {
        $focus = $this->resolveFocus();

        if ($focus === null) {
            // Wilayah belum tersedia → impor data referensi wilayah dulu.
            if (Region::query()->count() === 0) {
                $this->command?->info('Tabel wilayah kosong — menjalankan RegionSeeder...');
                $this->call(RegionSeeder::class);
                $focus = $this->resolveFocus();
            }
        }

        if ($focus === null) {
            $this->command?->warn('Kabupaten fokus belum ditetapkan. Jalankan RegionSeeder lalu atur di /admin/settings.');

            return;
        }

        $kecamatan = Region::query()
            ->where('parent_id', $focus->getKey())
            ->orderBy('name')
            ->take(5)
            ->get();

        if ($kecamatan->isEmpty()) {
            $this->command?->warn('Tidak ada kecamatan pada kabupaten fokus. Jalankan RegionSeeder lebih dulu.');

            return;
        }

        $destinationCategory = DestinationCategory::query()->firstOrCreate(
            ['slug' => 'wisata-alam'],
            ['name' => 'Wisata Alam', 'is_active' => true],
        );

        $gallery = $this->seedGallery();

        $this->seedDestinations($kecamatan, $destinationCategory, $gallery);
        $this->seedUmkm($kecamatan, $gallery);
        $this->seedAccommodations($kecamatan, $gallery);
        $this->seedEvents($kecamatan, $gallery);
        $this->seedReviews($this->reviewers());

        $this->command?->info("Data dummy untuk {$focus->name} berhasil di-seed.");
    }

    /**
     * Kabupaten/kota fokus. Bila belum ditetapkan, coba deteksi otomatis
     * (kode dapat diatur lewat env `DUMMY_FOCUS_CODE`, default Probolinggo 35.13)
     * lalu simpan sebagai fokus agar seeder jalan sekali jalan di server.
     */
    private function resolveFocus(): ?Region
    {
        $focus = RegionResolver::focus();

        if ($focus !== null) {
            return $focus;
        }

        $code = (string) env('DUMMY_FOCUS_CODE', '35.13');

        $detected = Region::query()
            ->where('code', $code)
            ->where('type', RegionType::KabupatenKota->value)
            ->first();

        if ($detected === null) {
            return null;
        }

        AppSetting::setFocusRegion($detected->getKey());
        $this->command?->info("Kabupaten fokus otomatis ditetapkan: {$detected->name} (kode {$code}).");

        return $detected;
    }

    /**
     * Salin gambar contoh ke disk publik, kembalikan daftar path relatif.
     *
     * @return array<int, string>
     */
    private function seedGallery(): array
    {
        $sources = ['images/auth/bromo-tengger-semeru.jpg', 'images/hero/hero-2.jpg', 'images/hero/hero-3.jpg'];
        $paths = [];

        foreach ($sources as $source) {
            $publicPath = public_path($source);

            if (! is_file($publicPath)) {
                continue;
            }

            $target = 'dummy/'.basename($source);
            Storage::disk('public')->put($target, file_get_contents($publicPath));
            $paths[] = $target;
        }

        return $paths;
    }

    /**
     * @param  Collection<int, Region>  $kecamatan
     * @param  array<int, string>  $gallery
     */
    private function seedDestinations(Collection $kecamatan, DestinationCategory $category, array $gallery): void
    {
        $items = [
            ['name' => 'Kawah Gunung Bromo', 'ticket' => 35000, 'lat' => -7.9425, 'lng' => 112.9530],
            ['name' => 'Penanjakan Bromo', 'ticket' => 10000, 'lat' => -7.9230, 'lng' => 112.9530],
            ['name' => 'Air Terjun Madakaripura', 'ticket' => 24000, 'lat' => -7.8620, 'lng' => 113.0230],
            ['name' => 'Bukit Teletubbies Bromo', 'ticket' => 15000, 'lat' => -7.9250, 'lng' => 112.9700],
            ['name' => 'Pasir Berbisik', 'ticket' => 15000, 'lat' => -7.9330, 'lng' => 112.9620],
            ['name' => 'Danau Ranu Pani', 'ticket' => 20000, 'lat' => -8.0000, 'lng' => 112.9300],
            ['name' => 'Puncak Rendra', 'ticket' => 12000, 'lat' => -7.9500, 'lng' => 112.9800],
            ['name' => 'Savana Teletubbies', 'ticket' => 10000, 'lat' => -7.9280, 'lng' => 112.9680],
            ['name' => 'Air Terjun Tancak', 'ticket' => 18000, 'lat' => -7.8900, 'lng' => 113.0100],
            ['name' => 'Pantai Bentar', 'ticket' => 10000, 'lat' => -7.7000, 'lng' => 113.2000],
        ];

        foreach ($items as $i => $item) {
            $kec = $kecamatan[$i % $kecamatan->count()];

            Destination::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'description' => 'Destinasi wisata '.$item['name'].' di '.$kec->name.'. Data contoh untuk pengembangan.',
                    'region_id' => $kec->getKey(),
                    'village_id' => RegionResolver::desaOptions($kec->getKey())->first()?->getKey(),
                    'destination_category_id' => $category->getKey(),
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                    'address' => $kec->name.', '.RegionResolver::focus()->name,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'opening_hours' => [['day' => 'setiap hari', 'open' => '05:00', 'close' => '17:00', 'closed' => false]],
                    'facilities' => ['Parkir', 'Toilet', 'Warung'],
                    'ticket_price' => $item['ticket'],
                    'gallery' => $gallery ? [$gallery[$i % count($gallery)]] : [],
                    'is_recommended' => true,
                    'recommendation_order' => $i + 1,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Region>  $kecamatan
     * @param  array<int, string>  $gallery
     */
    private function seedUmkm(Collection $kecamatan, array $gallery): void
    {
        $items = [
            ['name' => 'UMKM Kopi Tengger', 'type' => BusinessType::Produk],
            ['name' => 'Warung Rawon Nguling', 'type' => BusinessType::Kuliner],
            ['name' => 'Jasa Sewa Jeep Bromo', 'type' => BusinessType::Jasa],
            ['name' => 'Keripik Singkong Sumber', 'type' => BusinessType::Produk],
            ['name' => 'Sate Madura Cak Nur', 'type' => BusinessType::Kuliner],
            ['name' => 'Batik Probolinggo Asri', 'type' => BusinessType::Produk],
            ['name' => 'Jasa Fotografi Bromo', 'type' => BusinessType::Jasa],
            ['name' => 'Warung Nasi Goreng Gila', 'type' => BusinessType::Kuliner],
            ['name' => 'Madu Hutan Tengger', 'type' => BusinessType::Produk],
            ['name' => 'Bengkel Las Sumber Jaya', 'type' => BusinessType::Jasa],
        ];

        foreach ($items as $i => $item) {
            $kec = $kecamatan[$i % $kecamatan->count()];

            UmkmBusiness::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'business_type' => $item['type'],
                    'description' => 'Usaha '.$item['name'].' di '.$kec->name.'. Data contoh untuk pengembangan.',
                    'region_id' => $kec->getKey(),
                    'village_id' => RegionResolver::desaOptions($kec->getKey())->first()?->getKey(),
                    'address' => $kec->name.', '.RegionResolver::focus()->name,
                    'latitude' => -7.90 - ($i * 0.01),
                    'longitude' => 112.95 + ($i * 0.01),
                    'phone' => '08123456789'.$i,
                    'opening_hours' => [['day' => 'setiap hari', 'open' => '08:00', 'close' => '20:00', 'closed' => false]],
                    'gallery' => $gallery ? [$gallery[$i % count($gallery)]] : [],
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                    'is_recommended' => true,
                    'recommendation_order' => $i + 1,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Region>  $kecamatan
     * @param  array<int, string>  $gallery
     */
    private function seedAccommodations(Collection $kecamatan, array $gallery): void
    {
        $items = [
            ['name' => 'Hotel Bromo Permai', 'type' => AccommodationType::Hotel, 'price' => 650000],
            ['name' => 'Homestay Tosari Indah', 'type' => AccommodationType::Homestay, 'price' => 250000],
            ['name' => 'Guest House Semeru', 'type' => AccommodationType::GuestHouse, 'price' => 300000],
            ['name' => 'Villa Tengger Asri', 'type' => AccommodationType::Villa, 'price' => 900000],
            ['name' => 'Resort Ranu Pani', 'type' => AccommodationType::Resort, 'price' => 1200000],
            ['name' => 'Homestay Cemoro Lawang', 'type' => AccommodationType::Homestay, 'price' => 280000],
            ['name' => 'Hotel Probolinggo Indah', 'type' => AccommodationType::Hotel, 'price' => 450000],
            ['name' => 'Villa Bukit Teletubbies', 'type' => AccommodationType::Villa, 'price' => 850000],
            ['name' => 'Guest House Sumber', 'type' => AccommodationType::GuestHouse, 'price' => 275000],
            ['name' => 'Penginapan Ngadisari', 'type' => AccommodationType::Lainnya, 'price' => 200000],
        ];

        foreach ($items as $i => $item) {
            $kec = $kecamatan[$i % $kecamatan->count()];

            Accommodation::query()->updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'description' => 'Penginapan '.$item['name'].' di '.$kec->name.'. Data contoh untuk pengembangan.',
                    'region_id' => $kec->getKey(),
                    'village_id' => RegionResolver::desaOptions($kec->getKey())->first()?->getKey(),
                    'address' => $kec->name.', '.RegionResolver::focus()->name,
                    'latitude' => -7.88 - ($i * 0.01),
                    'longitude' => 112.97 + ($i * 0.01),
                    'phone' => '08129876543'.$i,
                    'amenities' => ['WiFi', 'Air Panas', 'Parkir'],
                    'gallery' => $gallery ? [$gallery[$i % count($gallery)]] : [],
                    'check_in_time' => '14:00',
                    'check_out_time' => '12:00',
                    'price_per_night' => $item['price'],
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                    'is_recommended' => true,
                    'recommendation_order' => $i + 1,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, Region>  $kecamatan
     * @param  array<int, string>  $gallery
     */
    private function seedEvents(Collection $kecamatan, array $gallery): void
    {
        $items = [
            ['title' => 'Festival Budaya Tengger', 'type' => EventType::Budaya, 'days' => 7, 'price' => 0, 'free' => true],
            ['title' => 'Karnaval Bromo Semeru', 'type' => EventType::Kesenian, 'days' => 21, 'price' => 15000, 'free' => false],
            ['title' => 'Pasar Malam Probolinggo', 'type' => EventType::Kuliner, 'days' => 35, 'price' => 0, 'free' => true],
            ['title' => 'Bromo Marathon', 'type' => EventType::Olahraga, 'days' => 14, 'price' => 100000, 'free' => false],
            ['title' => 'Festival Kasada', 'type' => EventType::Budaya, 'days' => 50, 'price' => 0, 'free' => true],
            ['title' => 'Pameran UMKM Lokal', 'type' => EventType::Pameran, 'days' => 28, 'price' => 0, 'free' => true],
            ['title' => 'Konser Musik Tengger', 'type' => EventType::Musik, 'days' => 42, 'price' => 50000, 'free' => false],
            ['title' => 'Lomba Foto Bromo', 'type' => EventType::Pameran, 'days' => 60, 'price' => 25000, 'free' => false],
            ['title' => 'Bazar Kuliner Nusantara', 'type' => EventType::Kuliner, 'days' => 10, 'price' => 0, 'free' => true],
            ['title' => 'Sepeda Gembira Bromo', 'type' => EventType::Olahraga, 'days' => 5, 'price' => 0, 'free' => true],
        ];

        foreach ($items as $i => $item) {
            $kec = $kecamatan[$i % $kecamatan->count()];
            $startsAt = now()->addDays($item['days'])->setTime(9, 0);

            Event::query()->updateOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'title' => $item['title'],
                    'event_type' => $item['type'],
                    'description' => 'Agenda '.$item['title'].' di '.$kec->name.'. Data contoh untuk pengembangan.',
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addDays(2)->setTime(21, 0),
                    'location_name' => 'Alun-alun '.$kec->name,
                    'region_id' => $kec->getKey(),
                    'village_id' => RegionResolver::desaOptions($kec->getKey())->first()?->getKey(),
                    'latitude' => -7.90 - ($i * 0.01),
                    'longitude' => 112.95 + ($i * 0.01),
                    'is_free' => $item['free'],
                    'ticket_price' => $item['price'],
                    'organizer_name' => 'Dinas Pariwisata '.RegionResolver::focus()->name,
                    'contact' => '08123456789'.$i,
                    'cover_image' => $gallery ? $gallery[$i % count($gallery)] : null,
                    'status' => ContentStatus::Published,
                    'published_at' => now(),
                ],
            );
        }
    }

    /**
     * Pengulas dummy (wisatawan) — dibuat sekali, idempoten.
     *
     * @return Collection<int, User>
     */
    private function reviewers(): Collection
    {
        return collect(range(1, 4))->map(fn (int $i): User => User::query()->firstOrCreate(
            ['email' => "wisatawan{$i}@loka-nusa.test"],
            [
                'name' => "Wisatawan Uji {$i}",
                'password' => Hash::make('password'),
                'role' => UserRole::Wisatawan,
                'email_verified_at' => now(),
            ],
        ));
    }

    /**
     * Sebar ulasan rating agar seksi "Rekomendasi" tiap katalog terisi.
     *
     * @param  Collection<int, User>  $reviewers
     */
    private function seedReviews(Collection $reviewers): void
    {
        $comments = [
            'Tempatnya bagus dan nyaman.',
            'Pelayanan ramah dan cepat.',
            'Pemandangan luar biasa.',
            'Harga terjangkau, layak dicoba.',
            'Akan kembali lagi bersama keluarga.',
        ];

        $maps = [
            Destination::class => [
                'kawah-gunung-bromo' => [5, 5, 4],
                'penanjakan-bromo' => [5, 4],
                'air-terjun-madakaripura' => [4, 4],
                'bukit-teletubbies-bromo' => [3],
                'pasir-berbisik' => [5, 3],
                'danau-ranu-pani' => [4],
                'puncak-rendra' => [5],
                'savana-teletubbies' => [4, 3],
                'air-terjun-tancak' => [3],
                'pantai-bentar' => [2],
            ],
            UmkmBusiness::class => [
                'umkm-kopi-tengger' => [5, 5, 4],
                'warung-rawon-nguling' => [5, 4],
                'jasa-sewa-jeep-bromo' => [4],
                'keripik-singkong-sumber' => [4, 3],
                'sate-madura-cak-nur' => [5, 5],
                'batik-probolinggo-asri' => [4],
                'jasa-fotografi-bromo' => [3],
                'warung-nasi-goreng-gila' => [4],
                'madu-hutan-tengger' => [5, 4],
                'bengkel-las-sumber-jaya' => [3],
            ],
            Accommodation::class => [
                'hotel-bromo-permai' => [5, 4, 4],
                'homestay-tosari-indah' => [5, 5],
                'guest-house-semeru' => [4],
                'villa-tengger-asri' => [4, 3],
                'resort-ranu-pani' => [5],
                'homestay-cemoro-lawang' => [4],
                'hotel-probolinggo-indah' => [3],
                'villa-bukit-teletubbies' => [4],
                'guest-house-sumber' => [3],
                'penginapan-ngadisari' => [2],
            ],
        ];

        foreach ($maps as $modelClass => $ratingsBySlug) {
            foreach ($ratingsBySlug as $slug => $ratings) {
                $model = $modelClass::query()->where('slug', $slug)->first();

                if ($model === null) {
                    continue;
                }

                foreach ($ratings as $index => $rating) {
                    $reviewer = $reviewers[$index % $reviewers->count()];

                    Review::query()->updateOrCreate(
                        [
                            'user_id' => $reviewer->getKey(),
                            'reviewable_type' => $modelClass,
                            'reviewable_id' => $model->getKey(),
                        ],
                        [
                            'rating' => $rating,
                            'comment' => $comments[$index % count($comments)],
                            'status' => ContentStatus::Published,
                            'published_at' => now(),
                        ],
                    );
                }
            }
        }
    }
}
