<?php

namespace Database\Seeders;

use App\Models\GradeBand;
use Illuminate\Database\Seeder;

class GradeBandSeeder extends Seeder
{
    public function run(): void
    {
        GradeBand::query()->delete();

        $primary = [
            [80, 100, '4', 'Excellent'],
            [60, 79, '3', 'Good'],
            [40, 59, '2', 'Average'],
            [0, 39, '1', 'Needs Support'],
        ];
        foreach ($primary as $i => [$min, $max, $grade, $label]) {
            GradeBand::create(['phase' => 'PRIMARY', 'min_score' => $min, 'max_score' => $max, 'grade' => $grade, 'label' => $label, 'sort_order' => $i + 1]);
        }

        $secondary = [
            [75, 100, '1', 'Distinction'], [70, 74, '2', 'Distinction'],
            [65, 69, '3', 'Strong Credit'], [60, 64, '4', 'Credit'], [55, 59, '5', 'Credit'], [50, 54, '6', 'Weak Credit'],
            [45, 49, '7', 'Pass'], [40, 44, '8', 'Weak Pass'],
            [0, 39, '9', 'Fail'],
        ];
        foreach ($secondary as $i => [$min, $max, $grade, $label]) {
            GradeBand::create(['phase' => 'SECONDARY', 'min_score' => $min, 'max_score' => $max, 'grade' => $grade, 'label' => $label, 'sort_order' => $i + 1]);
        }
    }
}
