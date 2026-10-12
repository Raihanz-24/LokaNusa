<?php

namespace App\Http\Controllers;

use App\Models\Accommodation;
use App\Models\Destination;
use App\Models\UmkmBusiness;
use Illuminate\Http\Request;

class ExploreMapController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->string('tipe')->toString();

        $destinations = Destination::published()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($type !== '' && $type !== 'destinasi', fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['id', 'name', 'slug', 'region_id', 'destination_category_id', 'latitude', 'longitude']);

        $umkms = UmkmBusiness::published()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($type !== '' && $type !== 'umkm', fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['id', 'name', 'region_id', 'business_type', 'latitude', 'longitude']);

        $accommodations = Accommodation::published()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($type !== '' && $type !== 'penginapan', fn ($query) => $query->whereRaw('1 = 0'))
            ->get(['id', 'name', 'region_id', 'type', 'latitude', 'longitude']);

        $markers = collect()
            ->merge($destinations->map(fn (Destination $d): array => [
                'id' => 'destinasi-'.$d->id,
                'type' => 'destinasi',
                'type_label' => 'Destinasi',
                'name' => $d->name,
                'lat' => (float) $d->latitude,
                'lng' => (float) $d->longitude,
                'color' => '#047857',
                'url' => route('destinations.show', $d->slug),
            ]))
            ->merge($umkms->map(fn (UmkmBusiness $u): array => [
                'id' => 'umkm-'.$u->id,
                'type' => 'umkm',
                'type_label' => $u->business_type?->label() ?? 'UMKM',
                'name' => $u->name,
                'lat' => (float) $u->latitude,
                'lng' => (float) $u->longitude,
                'color' => '#b45309',
                'url' => null,
            ]))
            ->merge($accommodations->map(fn (Accommodation $a): array => [
                'id' => 'penginapan-'.$a->id,
                'type' => 'penginapan',
                'type_label' => $a->type?->label() ?? 'Penginapan',
                'name' => $a->name,
                'lat' => (float) $a->latitude,
                'lng' => (float) $a->longitude,
                'color' => '#1d4ed8',
                'url' => null,
            ]))
            ->values()
            ->all();

        return view('explore.index', [
            'markers' => $markers,
            'type' => $type,
            'total' => count($markers),
        ]);
    }
}
