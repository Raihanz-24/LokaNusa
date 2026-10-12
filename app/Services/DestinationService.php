<?php

namespace App\Services;

use App\Services\Concerns\ModeratesContent;

/**
 * Business logic alur moderasi destinasi.
 *
 * Dipisahkan dari Filament agar dapat dipakai ulang oleh PWA, API, dan jobs.
 */
class DestinationService
{
    use ModeratesContent;
}
