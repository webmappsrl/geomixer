<?php

namespace Database\Factories;

use App\Models\TaxonomyWhere;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxonomyWhereFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaxonomyWhere::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'geometry' => null,
        ];
    }
}
