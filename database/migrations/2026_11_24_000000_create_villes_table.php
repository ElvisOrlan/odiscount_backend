<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('villes')) {
            return;
        }

        Schema::create('villes', function (Blueprint $table): void {
            $table->id();
            $table->string('ville');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Existing business tables must never be dropped by this migration.
    }
};
