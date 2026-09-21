<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('accreditation_status', 30)->nullable()->after('school_level');
            $table->unsignedSmallInteger('accreditation_expiry_year')->nullable()->after('accreditation_status');
            $table->string('land_status', 50)->nullable()->after('address');
            $table->decimal('land_area', 12, 2)->nullable()->after('land_status');
            $table->boolean('has_land_certificate')->nullable()->after('land_area');
            $table->boolean('has_bhpnu_ownership')->nullable()->after('has_land_certificate');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn([
                'accreditation_status',
                'accreditation_expiry_year',
                'land_status',
                'land_area',
                'has_land_certificate',
                'has_bhpnu_ownership',
            ]);
        });
    }
};
