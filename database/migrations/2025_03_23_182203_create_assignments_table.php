<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pink_file_id')->nullable();
            $table->unsignedBigInteger('inquiry_file_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable(); // OIC who assigned
            $table->unsignedBigInteger('assigned_to')->nullable(); // Investigator
            $table->dateTime('assigned_at');
            $table->text('assignment_notes')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->timestamps();

            // Foreign keys
            // $table->foreign('pink_file_id')->references('id')->on('pink_files')->onDelete('cascade');
            // $table->foreign('inquiry_file_id')->references('id')->on('inquiry_files')->onDelete('cascade');
            // $table->foreign('assigned_by')->references('id')->on('users');
            // $table->foreign('assigned_to')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
