<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_leave_requests', fn (Blueprint $t) => $t->unsignedBigInteger('legacy_permission_id')->nullable()->unique());
        Schema::table('attendance_records', function (Blueprint $t): void {
            $t->json('legacy_snapshot')->nullable();
            $t->json('legacy_attendance_ids')->nullable();
        });
        foreach (['correspondence_requests' => 'legacy_correspondence_id', 'proposal_requests' => 'legacy_proposal_id', 'batik_orders' => 'legacy_order_id', 'secretariat_agendas' => 'legacy_agenda_id', 'learning_modules' => 'legacy_module_id', 'employee_activity_requests' => 'legacy_activity_id'] as $table => $column) {
            Schema::table($table, function (Blueprint $t) use ($column): void {
                $t->unsignedBigInteger($column)->nullable()->unique();
                $t->json('legacy_snapshot')->nullable();
            });
        }
        Schema::table('sipinter_updates', function (Blueprint $t): void {
            $t->unsignedBigInteger('legacy_update_id')->nullable()->unique();
            $t->json('legacy_snapshot')->nullable();
        });
        Schema::table('student_enrollments', fn (Blueprint $t) => $t->json('legacy_snapshot')->nullable());
        Schema::table('educator_recaps', fn (Blueprint $t) => $t->json('legacy_snapshot')->nullable());
        Schema::table('batik_products', function (Blueprint $t): void {
            $t->unsignedBigInteger('legacy_product_id')->nullable()->unique();
            $t->json('legacy_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_leave_requests', fn (Blueprint $t) => $t->dropColumn('legacy_permission_id'));
        Schema::table('attendance_records', fn (Blueprint $t) => $t->dropColumn(['legacy_snapshot', 'legacy_attendance_ids']));
        foreach (['correspondence_requests' => 'legacy_correspondence_id', 'proposal_requests' => 'legacy_proposal_id', 'batik_orders' => 'legacy_order_id', 'secretariat_agendas' => 'legacy_agenda_id', 'learning_modules' => 'legacy_module_id', 'employee_activity_requests' => 'legacy_activity_id'] as $table => $column) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn([$column, 'legacy_snapshot']));
        }
        Schema::table('sipinter_updates', fn (Blueprint $t) => $t->dropColumn(['legacy_update_id', 'legacy_snapshot']));
        Schema::table('student_enrollments', fn (Blueprint $t) => $t->dropColumn('legacy_snapshot'));
        Schema::table('educator_recaps', fn (Blueprint $t) => $t->dropColumn('legacy_snapshot'));
        Schema::table('batik_products', fn (Blueprint $t) => $t->dropColumn(['legacy_product_id', 'legacy_snapshot']));
    }
};
