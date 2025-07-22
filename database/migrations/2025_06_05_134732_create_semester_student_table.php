<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('semester_student', function (Blueprint $table) {
            $table->foreignId('semester_id')->constrained();
            $table->foreignId('student_id')->constrained('students','student_id'); // Explicit if table/key names differ
            $table->integer('percentage');
            $table->timestamps();
            $table->primary(['semester_id', 'student_id']); // Composite primary key
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_student');
    }
};
