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
        Schema::create('welfare_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('month_id')->constrained('welfare_months');
            $table->integer('year');
            $table->enum('status', ['unpaid', 'paid', 'excused'])->default('unpaid');
            $table->timestamp('payment_date')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users');
            $table->decimal('amount', 8, 2)->default(100.00); // K100 is the default amount
            $table->timestamps();

            // Add a unique constraint to prevent duplicate entries
            $table->unique(['user_id', 'month_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('welfare_contributions');
    }
};
