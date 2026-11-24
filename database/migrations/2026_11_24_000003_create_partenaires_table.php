<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partenaires')) {
            return;
        }

        Schema::create('partenaires', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories');
            $table->string('image_partenaire')->nullable();
            $table->string('nom_partenaire');
            $table->decimal('taux_reduction', 5, 2);
            $table->text('info_reduction')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->foreignId('ville_id')->nullable()->constrained('villes')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->constrained('communes')->nullOnDelete();
            $table->string('localisation')->nullable();
            $table->string('nom_prenom_gerant')->nullable();
            $table->string('mail_gerant')->nullable();
            $table->string('phone_gerant', 30)->nullable();
            $table->string('activite_principale')->nullable();
            $table->boolean('boutique_physique')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Existing business tables must never be dropped by this migration.
    }
};
