<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('attendance_events', function(Blueprint $t): void { $t->foreignId('attendance_id')->nullable()->after('id')->constrained('attendance_records')->nullOnDelete(); $t->string('event_status',20)->default('rejected')->after('event_type'); $t->timestampTz('checked_at')->nullable(); $t->boolean('is_inside_geofence')->nullable(); $t->string('selfie_path')->nullable(); $t->string('device_info')->nullable(); $t->unsignedBigInteger('geofence_version')->nullable(); $t->renameColumn('accuracy','gps_accuracy'); }); }
 public function down(): void { Schema::table('attendance_events', function(Blueprint $t): void { $t->dropForeign(['attendance_id']); $t->dropColumn(['attendance_id','event_status','checked_at','is_inside_geofence','selfie_path','device_info','geofence_version']); $t->renameColumn('gps_accuracy','accuracy'); }); }
};
