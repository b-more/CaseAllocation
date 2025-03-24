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
        Schema::create('pink_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pink_file_type_id')->nullable();
            $table->string('ig_folio')->nullable();
            $table->string('commissioner_cid_folio')->nullable();
            $table->string('director_c2_folio')->nullable();
            $table->text('assistant_director_c2_comment')->nullable();
            $table->text('oic_comment')->nullable();
            $table->unsignedBigInteger('complainant_type_id')->nullable();
            $table->string('complainant_name');
            $table->dateTime('date_time_of_occurrence')->nullable();
            $table->unsignedBigInteger('crime_type_id')->nullable();
            $table->string('priority')->default('normal'); // very_high, high, normal, low
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();

            // $table->foreign('pink_file_type_id')->references('id')->on('pink_file_types')->onDelete('set null');
            // $table->foreign('complainant_type_id')->references('id')->on('complainant_types')->onDelete('set null');
            // $table->foreign('crime_type_id')->references('id')->on('crime_types')->onDelete('set null');
            // $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pink_files');
    }
};
