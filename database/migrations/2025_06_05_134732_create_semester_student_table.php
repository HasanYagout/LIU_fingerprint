<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('semester_student', function (Blueprint $table) {
            $table->foreignId('semester_id')->constrained();
            
            // Explicitly define to match students.student_id type
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')
                  ->references('student_id')
                  ->on('students');
            
            $table->integer('percentage');
            $table->timestamps();
            $table->primary(['semester_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_student');
    }
};
