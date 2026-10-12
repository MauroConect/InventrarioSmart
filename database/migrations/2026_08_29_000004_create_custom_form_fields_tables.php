<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('form_key', 40);
            $table->string('name', 80);
            $table->string('label', 120);
            $table->string('type', 20);
            $table->boolean('required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'form_key', 'name']);
            $table->index(['tenant_id', 'form_key']);
        });

        Schema::create('custom_form_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_form_field_id')->constrained('custom_form_fields')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['custom_form_field_id', 'entity_type', 'entity_id'], 'custom_form_values_unique');
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_form_values');
        Schema::dropIfExists('custom_form_fields');
    }
};
