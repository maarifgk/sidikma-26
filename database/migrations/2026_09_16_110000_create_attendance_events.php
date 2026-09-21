<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('attendance_events', function(Blueprint $t): void { $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->foreignId('employee_id')->constrained()->cascadeOnDelete(); $t->foreignId('school_id')->constrained()->cascadeOnDelete(); $t->date('attendance_date'); $t->string('event_type',20); $t->string('rejection_code',64)->nullable(); $t->text('rejection_reason')->nullable(); $t->boolean('is_mock_location')->nullable(); $t->string('mock_detection_source')->nullable(); $t->decimal('latitude',10,7)->nullable(); $t->decimal('longitude',10,7)->nullable(); $t->decimal('accuracy',10,2)->nullable(); $t->timestampsTz(); $t->index(['employee_id','attendance_date']); $t->index(['rejection_code','created_at']); }); }
 public function down(): void { Schema::dropIfExists('attendance_events'); }
};
