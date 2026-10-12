<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_receipt_counters', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id')->constrained()->restrictOnDelete(); $t->string('series', 8); $t->unsignedBigInteger('value')->default(0); $t->unique(['tenant_id', 'series']); });
        Schema::create('tenant_operations', function (Blueprint $t) { $t->id(); $t->foreignId('tenant_id')->constrained()->restrictOnDelete(); $t->foreignId('actor_user_id')->constrained('users')->restrictOnDelete(); $t->string('key', 128); $t->string('fingerprint', 64); $t->unsignedSmallInteger('status'); $t->longText('response'); $t->timestamps(); $t->unique(['tenant_id', 'actor_user_id', 'key']); });
    }
    public function down(): void { Schema::dropIfExists('tenant_operations'); Schema::dropIfExists('tenant_receipt_counters'); }
};
