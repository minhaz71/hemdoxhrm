<?php

namespace Database\Seeders;

use App\Models\DegreeName;
use App\Models\DegreeType;
use Illuminate\Database\Seeder;

class DegreeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'School',
            'College',
            'University',
            'Diploma',
            'Bachelor',
            'Master',
            'PhD',
        ];

        foreach ($types as $name) {
            DegreeType::updateOrCreate(['name' => $name], ['status' => 'active']);
        }

        // Seed common degree names linked to their most relevant type
        $school   = DegreeType::where('name', 'School')->first();
        $college  = DegreeType::where('name', 'College')->first();
        $bachelor = DegreeType::where('name', 'Bachelor')->first();
        $master   = DegreeType::where('name', 'Master')->first();
        $diploma  = DegreeType::where('name', 'Diploma')->first();

        $names = [
            // School
            ['name' => 'SSC / O-Level',    'degree_type_id' => $school?->id],
            ['name' => 'JSC / JDC',        'degree_type_id' => $school?->id],
            ['name' => 'PSC / PEC',        'degree_type_id' => $school?->id],
            // College
            ['name' => 'HSC / A-Level',    'degree_type_id' => $college?->id],
            // Bachelor
            ['name' => 'B.Sc',             'degree_type_id' => $bachelor?->id],
            ['name' => 'B.A',              'degree_type_id' => $bachelor?->id],
            ['name' => 'B.Com',            'degree_type_id' => $bachelor?->id],
            ['name' => 'BBA',              'degree_type_id' => $bachelor?->id],
            ['name' => 'B.Tech / B.Engg',  'degree_type_id' => $bachelor?->id],
            ['name' => 'LLB',              'degree_type_id' => $bachelor?->id],
            ['name' => 'MBBS',             'degree_type_id' => $bachelor?->id],
            // Master
            ['name' => 'M.Sc',             'degree_type_id' => $master?->id],
            ['name' => 'M.A',              'degree_type_id' => $master?->id],
            ['name' => 'MBA',              'degree_type_id' => $master?->id],
            ['name' => 'M.Tech / M.Engg',  'degree_type_id' => $master?->id],
            ['name' => 'LLM',              'degree_type_id' => $master?->id],
            // Diploma
            ['name' => 'Diploma in Engineering',  'degree_type_id' => $diploma?->id],
            ['name' => 'Diploma in Commerce',     'degree_type_id' => $diploma?->id],
            ['name' => 'Diploma in Computer Sci', 'degree_type_id' => $diploma?->id],
        ];

        foreach ($names as $item) {
            DegreeName::updateOrCreate(['name' => $item['name']], [
                'degree_type_id' => $item['degree_type_id'],
                'status'         => 'active',
            ]);
        }
    }
}
