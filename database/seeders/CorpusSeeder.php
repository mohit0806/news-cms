<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CorpusSeeder extends Seeder
{
    /**
     * Seed the large-scale content corpus for search/slug spikes and
     * performance tests (TRD §23.3).
     *
     * Skeleton only: the full 10k-article mixed Hindi/English corpus requires
     * the P2 article schema, which is blocked on P0-T04 (source-of-truth ADRs)
     * and Q1 (editor/body format). Safe to run at any time; no-ops with a
     * notice until the schema exists.
     */
    public function run(): void
    {
        if (! Schema::hasTable('articles')) {
            $this->command->info('CorpusSeeder: articles table absent; skipping (P2 schema required).');

            return;
        }

        // Full corpus arrives with P2-T06/T07 (S1/S2 investigations).
        $this->command->info('CorpusSeeder: articles table present; full corpus arrives with P2-T06/T07.');
    }
}
