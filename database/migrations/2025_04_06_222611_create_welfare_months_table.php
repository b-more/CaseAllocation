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
        Schema::create('welfare_months', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('month_number');
            $table->timestamps();
        });

        // Insert the months
        $months = [
            ['id' => 1, 'name' => 'January', 'month_number' => 1],
            ['id' => 2, 'name' => 'February', 'month_number' => 2],
            ['id' => 3, 'name' => 'March', 'month_number' => 3],
            ['id' => 4, 'name' => 'April', 'month_number' => 4],
            ['id' => 5, 'name' => 'May', 'month_number' => 5],
            ['id' => 6, 'name' => 'June', 'month_number' => 6],
            ['id' => 7, 'name' => 'July', 'month_number' => 7],
            ['id' => 8, 'name' => 'August', 'month_number' => 8],
            ['id' => 9, 'name' => 'September', 'month_number' => 9],
            ['id' => 10, 'name' => 'October', 'month_number' => 10],
            ['id' => 11, 'name' => 'November', 'month_number' => 11],
            ['id' => 12, 'name' => 'December', 'month_number' => 12],
        ];

        DB::table('welfare_months')->insert($months);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('welfare_months');
    }
};
