<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key'         => 'currency_code',
                'value'       => 'USD',
                'label'       => 'Currency Code',
                'description' => 'ISO 4217 currency code (e.g. USD, BDT, EUR).',
            ],
            [
                'key'         => 'currency_symbol',
                'value'       => '$',
                'label'       => 'Currency Symbol',
                'description' => 'Symbol displayed next to monetary values.',
            ],
            [
                'key'         => 'currency_position',
                'value'       => 'before',
                'label'       => 'Symbol Position',
                'description' => '"before" = $100 | "after" = 100 $',
            ],
            [
                'key'         => 'decimal_separator',
                'value'       => '.',
                'label'       => 'Decimal Separator',
                'description' => 'Character used to separate decimals (e.g. . or ,).',
            ],
            [
                'key'         => 'thousand_separator',
                'value'       => ',',
                'label'       => 'Thousand Separator',
                'description' => 'Character used to separate thousands (e.g. , or .).',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $row['key']],
                array_merge($row, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'currency_code',
            'currency_symbol',
            'currency_position',
            'decimal_separator',
            'thousand_separator',
        ])->delete();
    }
};
