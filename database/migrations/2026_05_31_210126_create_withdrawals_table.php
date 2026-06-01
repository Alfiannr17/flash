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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference');
            $table->unsignedBigInteger('user_id')->index('withdrawals_user_id_foreign');
            $table->decimal('amount', 15);
            $table->decimal('fee', 15)->default(1000);
            $table->string('destination_number');
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
