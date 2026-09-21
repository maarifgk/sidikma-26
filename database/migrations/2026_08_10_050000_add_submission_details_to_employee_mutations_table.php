<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->foreignId('employee_id')->nullable()->change();
            $table->string('employee_code', 50)->nullable()->after('employee_id');
            $table->string('employee_name')->nullable()->after('employee_code');
            $table->string('phone', 30)->nullable()->after('employee_name');
            $table->string('birth_place', 100)->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->date('effective_date')->nullable()->after('mutation_type');
            $table->foreignId('origin_school_id')->nullable()->change();
            $table->string('origin_school_name')->nullable()->after('origin_school_id');
            $table->foreignId('destination_school_id')->nullable()->change();
            $table->string('destination_school_name')->nullable()->after('destination_school_id');
        });

        DB::table('employee_mutations')
            ->orderBy('id')
            ->each(function (object $mutation): void {
                $employee = DB::table('employees')->where('id', $mutation->employee_id)->first();
                $originSchool = DB::table('schools')->where('id', $mutation->origin_school_id)->first();
                $destinationSchool = DB::table('schools')->where('id', $mutation->destination_school_id)->first();

                DB::table('employee_mutations')
                    ->where('id', $mutation->id)
                    ->update([
                        'employee_code' => $employee?->employee_code,
                        'employee_name' => $employee?->name,
                        'phone' => $employee?->phone,
                        'birth_place' => $employee?->birth_place,
                        'birth_date' => $employee?->birth_date,
                        'origin_school_name' => $originSchool?->name,
                        'destination_school_name' => $destinationSchool?->name,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('employee_mutations', function (Blueprint $table): void {
            $table->dropColumn([
                'employee_code',
                'employee_name',
                'phone',
                'birth_place',
                'birth_date',
                'effective_date',
                'origin_school_name',
                'destination_school_name',
            ]);
            $table->foreignId('employee_id')->nullable(false)->change();
            $table->foreignId('origin_school_id')->nullable(false)->change();
            $table->foreignId('destination_school_id')->nullable(false)->change();
        });
    }
};
