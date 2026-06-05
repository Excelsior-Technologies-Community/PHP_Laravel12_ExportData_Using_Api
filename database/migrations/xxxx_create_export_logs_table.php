<?php
// database/migrations/xxxx_create_export_logs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('export_type'); // json, csv, excel, pdf
            $table->string('filename');
            $table->string('format');
            $table->json('filters')->nullable();
            $table->integer('records_count')->default(0);
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('download_url')->nullable();
            $table->string('user_email')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};