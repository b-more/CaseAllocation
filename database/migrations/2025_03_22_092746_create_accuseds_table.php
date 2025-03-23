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
        Schema::create('accuseds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->string('name');
            $table->string('identification')->nullable();
            $table->string('contact')->nullable();
            $table->text('address')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            //$table->foreign('case_id')->references('id')->on('inquiry_files')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accuseds');
    }
};
