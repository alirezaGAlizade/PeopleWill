<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IranGeographySeeder extends Seeder
{
    /**
     * Seed Iran country, provinces, and cities from the bundled MySQL dumps
     * (database/data/iran/*.sql). Safe to run once; skips if a country row already exists.
     */
    public function run(): void
    {
        if (DB::table('countries')->exists()) {
            return;
        }

        $base = database_path('data/iran');

        $countryRow = $this->parseInsertRows($base.'/countries.sql', 'countries')[0] ?? null;

        if ($countryRow === null) {
            throw new \RuntimeException('countries.sql: no INSERT row found.');
        }

        $provinceRows = $this->parseInsertRows($base.'/provinces.sql', 'provinces');
        $cityRows = $this->parseInsertRows($base.'/cities.sql', 'cities');

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('countries')->insert([
                'id' => (int) $countryRow[0],
                'capital_city' => null,
                'name' => (string) $countryRow[2],
                'name_en' => (string) ($countryRow[3] ?? $countryRow[2]),
            ]);

            $provincePayload = [];
            foreach ($provinceRows as $row) {
                $provincePayload[] = [
                    'id' => (int) $row[0],
                    'country' => (int) $row[1],
                    'name' => (string) $row[2],
                    'name_en' => (string) ($row[3] ?? $row[2]),
                ];
            }
            foreach (array_chunk($provincePayload, 100) as $chunk) {
                DB::table('provinces')->insert($chunk);
            }

            $cityPayload = [];
            foreach ($cityRows as $row) {
                $cityPayload[] = [
                    'id' => (int) $row[0],
                    'province' => (int) $row[1],
                    'name' => (string) $row[2],
                    'name_en' => $row[3] !== null && $row[3] !== '' ? (string) $row[3] : (string) $row[2],
                    'latitude' => $this->decimalOrZero($row[4] ?? null),
                    'longitude' => $this->decimalOrZero($row[5] ?? null),
                ];
            }
            foreach (array_chunk($cityPayload, 200) as $chunk) {
                DB::table('cities')->insert($chunk);
            }

            $capitalId = isset($countryRow[1]) && $countryRow[1] !== null && $countryRow[1] !== ''
                ? (int) $countryRow[1]
                : null;

            if ($capitalId !== null) {
                DB::table('countries')
                    ->where('id', (int) $countryRow[0])
                    ->update(['capital_city' => $capitalId]);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @return list<list<string|null>>
     */
    private function parseInsertRows(string $path, string $table): array
    {
        if (! is_readable($path)) {
            throw new \RuntimeException("Cannot read geography SQL file: {$path}");
        }

        $rows = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || ! str_starts_with($line, 'INSERT INTO')) {
                continue;
            }

            if (! preg_match('/^INSERT INTO `'.preg_quote($table, '/').'` VALUES \((.*)\);$/', $line, $m)) {
                continue;
            }

            $rows[] = $this->parseMysqlTuple($m[1]);
        }

        return $rows;
    }

    /**
     * Parse a single MySQL VALUES tuple (inside parentheses): 'a', 123, null, 'esc\'d'
     *
     * @return list<string|null>
     */
    private function parseMysqlTuple(string $inner): array
    {
        $values = [];
        $n = strlen($inner);
        $i = 0;

        while ($i < $n) {
            while ($i < $n && (ctype_space($inner[$i]) || $inner[$i] === ',')) {
                $i++;
            }

            if ($i >= $n) {
                break;
            }

            if (strncasecmp(substr($inner, $i, 4), 'null', 4) === 0) {
                $after = $inner[$i + 4] ?? '';
                if ($after === '' || $after === ',' || $after === ')' || ctype_space($after)) {
                    $values[] = null;
                    $i += 4;

                    continue;
                }
            }

            if ($inner[$i] === "'") {
                $i++;
                $buf = '';
                while ($i < $n) {
                    $c = $inner[$i];
                    if ($c === '\\') {
                        $i++;
                        if ($i < $n) {
                            $buf .= $inner[$i];
                            $i++;
                        }

                        continue;
                    }
                    if ($c === "'") {
                        $i++;

                        break;
                    }
                    $buf .= $c;
                    $i++;
                }
                $values[] = $buf;

                continue;
            }

            throw new \RuntimeException('Unexpected token in SQL tuple at offset '.$i.': '.$inner);
        }

        return $values;
    }

    private function decimalOrZero(?string $value): string
    {
        if ($value === null || $value === '') {
            return '0.00000000';
        }

        return $value;
    }
}
