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
        Schema::create('inquiry_files', function (Blueprint $table) {
            $table->id();
            $table->string('if_number')->unique();
            $table->time('time')->nullable();
            $table->date('date')->nullable();
            $table->string('cr_number')->nullable();
            $table->string('police_station')->nullable();
            $table->string('complainant')->nullable();
            $table->string('offence')->nullable();
            $table->decimal('value_of_property_stolen', 15, 2)->nullable();
            $table->decimal('value_of_property_recovered', 15, 2)->nullable();
            $table->text('accused')->nullable();
            $table->unsignedBigInteger('if_status_id')->nullable();
            $table->text('case_close_reason')->nullable();
            $table->boolean('contacted_complainant')->default(false);
            $table->boolean('recorded_statement')->default(false);
            $table->boolean('apprehended_suspects')->default(false);
            $table->boolean('warned_cautioned')->default(false);
            $table->boolean('released_on_bond')->default(false);
            $table->unsignedBigInteger('court_type_id')->nullable();
            $table->unsignedBigInteger('court_stage_id')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('dealing_officer')->nullable();
            $table->json('meta_data')->nullable();
            $table->unsignedBigInteger('pink_file_id')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();

            // $table->foreign('if_status_id')->references('id')->on('if_statuses')->onDelete('set null');
            // $table->foreign('court_type_id')->references('id')->on('court_types')->onDelete('set null');
            // $table->foreign('court_stage_id')->references('id')->on('court_stages')->onDelete('set null');
            // $table->foreign('dealing_officer')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('pink_file_id')->references('id')->on('pink_files')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_files');
    }
};
