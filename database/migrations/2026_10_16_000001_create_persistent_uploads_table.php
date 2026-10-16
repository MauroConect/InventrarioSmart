<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persistent_uploads', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 20);
            $table->string('path', 512);
            $table->string('mime_type');
            $table->longText('content_base64');
            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persistent_uploads');
    }
};
