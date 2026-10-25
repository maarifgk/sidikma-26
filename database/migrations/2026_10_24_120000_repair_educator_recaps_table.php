<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('educator_recaps')) {
            Schema::create('educator_recaps', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('academic_year', 9)->index();
                $table->unsignedInteger('asn_certified')->default(0);
                $table->unsignedInteger('asn_uncertified')->default(0);
                $table->unsignedInteger('foundation_certified_inpassing')->default(0);
                $table->unsignedInteger('foundation_uncertified')->default(0);
                $table->unsignedInteger('total')->default(0)->index();
                $table->unsignedBigInteger('submitted_by')->nullable()->index();
                $table->json('legacy_snapshot')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'academic_year']);
            });
        }
    }

    public function down(): void {}
};
