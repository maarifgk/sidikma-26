<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('attendance_settings', fn(Blueprint $t) => $t->unsignedBigInteger('geofence_version')->default(1)->after('geofence_polygon')); Schema::table('attendance_records', function(Blueprint $t): void { $t->text('check_out_reason')->nullable()->after('check_out_at'); $t->boolean('check_in_fake_gps')->default(false)->after('check_in_distance'); $t->boolean('check_out_fake_gps')->default(false)->after('check_out_distance'); $t->string('check_in_fake_gps_source')->nullable(); $t->string('check_out_fake_gps_source')->nullable(); $t->unsignedBigInteger('check_in_geofence_version')->nullable(); $t->unsignedBigInteger('check_out_geofence_version')->nullable(); }); }
 public function down(): void { Schema::table('attendance_records', fn(Blueprint $t) => $t->dropColumn(['check_out_reason','check_in_fake_gps','check_out_fake_gps','check_in_fake_gps_source','check_out_fake_gps_source','check_in_geofence_version','check_out_geofence_version'])); Schema::table('attendance_settings', fn(Blueprint $t) => $t->dropColumn('geofence_version')); }
};
