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
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('orders_user_id_foreign');
            $table->string('service_code');
            $table->string('activation_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('otp_code')->nullable();
            $table->decimal('price', 10);
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->string('country_id')->nullable();
            $table->string('api_version')->nullable();
            $table->string('selected_id')->nullable();
            $table->string('payment_ref')->nullable();
            $table->text('payment_qr')->nullable();
            $table->decimal('total_payment', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
