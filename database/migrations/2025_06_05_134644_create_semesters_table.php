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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->string('name');             // e.g. "Fall 2025" or "Spring 2026"
            $table->unsignedSmallInteger('year');
            $table->date('start_date');         // when the semester officially begins
            $table->date('midterm_date');       // when the midterm period starts (or “cutoff”)
            $table->date('end_date');           // end of semester (not strictly needed for this example)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
