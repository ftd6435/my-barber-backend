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
        Schema::create('age_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name', length: 160)->unique(); // Enfant, Adolescent, Adulte
            $table->text('description')->nullable();
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        $ageRanges = [
            [
                'name' => 'Enfant',
                'min_age' => 0,
                'max_age' => 12,
                'description' => 'Prestations destinées aux enfants de 0 à 12 ans.',
                'is_active' => true,
            ],
            [
                'name' => 'Adolescent',
                'min_age' => 13,
                'max_age' => 18,
                'description' => 'Prestations destinées aux adolescents de 13 à 18 ans.',
                'is_active' => true,
            ],
            [
                'name' => 'Adulte',
                'min_age' => 19,
                'max_age' => 199,
                'description' => 'Prestations destinées aux adultes de 19 ans et plus.',
                'is_active' => true,
            ],
        ];

        foreach ($ageRanges as $ageRange) {
            DB::table('age_ranges')->insert($ageRange);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('age_ranges');
    }
};
