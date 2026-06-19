<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->string('item_id'); // Dummy service sebelumnya
            $table->string('user_id'); // Dummy
            $table->decimal('bid_amount', 15, 2);
            $table->string('status')->default('winning');
            $table->timestamps();
            $table->string('soap_receipt_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
