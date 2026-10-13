<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemesanan (booking) DEMO untuk penginapan & produk UMKM.
 *
 * Pembayaran bersifat simulasi (kolom `payment_method`/`paid_at`). Tidak ada
 * transaksi keuangan nyata. `bookable` polymorphic: Accommodation (menginap,
 * dengan tanggal & jumlah malam) atau UmkmProduct (beli, dengan jumlah).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique(); // kode demo, mis. LK-XXXXXXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookable');

            // Pemilik konten (penginapan/UMKM) untuk pantau trafik penjualan.
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1); // malam / jumlah unit
            $table->date('start_date')->nullable(); // check-in (penginapan)
            $table->date('end_date')->nullable();   // check-out (penginapan)
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('total_price')->default(0);

            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable(); // simulasi: transfer/qris/ewallet
            $table->timestamp('paid_at')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
