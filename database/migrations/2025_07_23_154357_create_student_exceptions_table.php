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
        Schema::create('student_exceptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id'); // Changed from string to match students table
            $table->unsignedBigInteger('semester_id');
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason')->nullable();
            $table->timestamps();
        
            $table->foreign('student_id')
                ->references('student_id')
                ->on('students');
        
            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_exceptions');
    }
};
