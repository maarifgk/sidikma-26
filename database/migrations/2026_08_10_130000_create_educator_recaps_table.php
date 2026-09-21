<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('educator_recaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9)->index();
            $table->unsignedInteger('asn_certified')->default(0);
            $table->unsignedInteger('asn_uncertified')->default(0);
            $table->unsignedInteger('foundation_certified_inpassing')->default(0);
            $table->unsignedInteger('foundation_uncertified')->default(0);
            $table->unsignedInteger('total')->default(0)->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('educator_recaps');
    }
};
