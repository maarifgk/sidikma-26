<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('attendance_events', fn (Blueprint $t) => $t->unsignedBigInteger('client_geofence_version')->nullable()->after('geofence_version')); }
 public function down(): void { Schema::table('attendance_events', fn (Blueprint $t) => $t->dropColumn('client_geofence_version')); }
};
