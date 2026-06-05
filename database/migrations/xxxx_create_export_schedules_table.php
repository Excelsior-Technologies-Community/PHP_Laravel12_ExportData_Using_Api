<?php
// database/migrations/xxxx_create_export_schedules_table.php

Schema::create('export_schedules', function (Blueprint $table) {
    $table->id();
    $table->string('format');
    $table->string('frequency'); // daily, weekly, monthly
    $table->json('filters')->nullable();
    $table->string('email');
    $table->timestamp('last_run')->nullable();
    $table->timestamp('next_run');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});