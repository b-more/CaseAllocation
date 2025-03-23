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
        Schema::create('case_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('old_status')->nullable();
            $table->unsignedBigInteger('new_status');
            $table->text('reason')->nullable();
            $table->text('oic_comment')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // $table->foreign('case_id')->references('id')->on('inquiry_files')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_statuses');
    }
};
