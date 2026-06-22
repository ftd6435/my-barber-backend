<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', length: 160)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        // Auto insert these categories
        // Coiffure homme, Coiffure femme, Barbier, Ongles, Soins de visage, Soins du corps, Maquillage & Beauté du regard, Bien-être & Relaxation, etc.
        $categories = [
            [
                'name' => 'Coiffure homme',
                'description' => 'Coiffure d\'un homme',
                'is_active' => true,
            ],
            [
                'name' => 'Coiffure femme',
                'description' => 'Coiffure d\'une femme',
                'is_active' => true,
            ],
            [
                'name' => 'Barbier',
                'description' => 'Taille, entretien et stylisation de barbe et moustache.',
                'is_active' => true,
            ],
            [
                'name' => 'Ongles',
                'description' => 'Manucure, pédicure, pose et entretien des ongles.',
                'is_active' => true,
            ],
            [
                'name' => 'Soins du visage',
                'description' => 'Nettoyage, hydratation et soins esthétiques du visage.',
                'is_active' => true,
            ],
            [
                'name' => 'Soins du corps',
                'description' => 'Soins esthétiques et de bien-être dédiés au corps.',
                'is_active' => true,
            ],
            [
                'name' => 'Maquillage',
                'description' => 'Maquillage professionnel pour toutes occasions.',
                'is_active' => true,
            ],
            [
                'name' => 'Beauté du regard',
                'description' => 'Extensions, rehaussement et entretien des cils et sourcils.',
                'is_active' => true,
            ],
            [
                'name' => 'Bien-être & Relaxation',
                'description' => 'Prestations de relaxation et de détente pour le bien-être.',
                'is_active' => true,
            ],
            [
                'name' => 'Massage',
                'description' => 'Massages relaxants, thérapeutiques et de récupération.',
                'is_active' => true,
            ],
            [
                'name' => 'Épilation',
                'description' => 'Prestations d\'épilation du visage et du corps.',
                'is_active' => true,
            ],
            [
                'name' => 'Coloration',
                'description' => 'Coloration, décoloration et techniques de transformation capillaire.',
                'is_active' => true,
            ],
            [
                'name' => 'Coiffure événementielle',
                'description' => 'Coiffures pour mariages, soirées et événements spéciaux.',
                'is_active' => true,
            ],
        ];
        foreach ($categories as $category) {
            DB::table('categories')->insert($category);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
