<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('foundation_sk_templates', function(Blueprint $t){$t->string('slug')->nullable()->unique();$t->string('document_title')->nullable();$t->text('description')->nullable();$t->text('content')->nullable();$t->text('custom_css')->nullable();}); }
 public function down(): void { Schema::table('foundation_sk_templates', function(Blueprint $t){$t->dropUnique(['slug']);$t->dropColumn(['slug','document_title','description','content','custom_css']);}); }
};
