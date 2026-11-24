<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partenaires') || Schema::hasColumn('partenaires', 'is_active')) {
            return;
        }

        Schema::table('partenaires', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('partenaires') || ! Schema::hasColumn('partenaires', 'is_active')) {
            return;
        }

        Schema::table('partenaires', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
