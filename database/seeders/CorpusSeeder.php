<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Author;
use App\Models\Term;
use App\Models\User;
use App\Support\Slugger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Content corpus for search/slug spikes and performance tests (TRD §23.3).
 *
 * Always seeds a small deterministic labelled set (S1 query fuel: known
 * titles in English and Hindi with expected slugs). The full 10k-article
 * volume runs only with CORPUS_FULL=true so routine seeding stays fast.
 */
class CorpusSeeder extends Seeder
{
    /**
     * Labelled article titles. Slugs are computed with the Slugger so the set
     * stays consistent with the implementation under test (S2 may still judge
     * individual transliterations unreadable — that verdict belongs to T06.2).
     *
     * @var list<array{string, string}>
     */
    public const LABELLED = [
        ['en', 'City Council Approves Metro Rail Extension'],
        ['en', 'Monsoon Rains Flood Low-Lying Districts'],
        ['en', 'National Team Wins Championship Final'],
        ['en', 'New Education Policy Rolls Out Statewide'],
        ['en', 'Stock Markets Rally On Strong Earnings'],
        ['en', 'Hospital Opens Free Health Camp Sunday'],
        ['hi', 'नगर निगम ने मेट्रो विस्तार को मंजूरी दी'],
        ['hi', 'मानसूनी बारिश से निचले इलाकों में बाढ़'],
        ['hi', 'राष्ट्रीय टीम ने चैंपियनशिप फाइनल जीता'],
        ['hi', 'नई शिक्षा नीति पूरे राज्य में लागू'],
        ['hi', 'अस्पताल में रविवार को निःशुल्क स्वास्थ्य शिविर'],
        ['hi', 'शेयर बाजार में तेज उछाल दर्ज'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('articles')) {
            $this->command->info('CorpusSeeder: articles table absent; skipping (P2 schema required).');

            return;
        }

        $user = User::query()->first() ?? User::factory()->create();
        $author = Author::query()->first() ?? Author::factory()->create();
        $category = Term::query()->where('vocabulary', 'category')->first()
            ?? Term::factory()->translated()->create();

        foreach (self::LABELLED as [$language, $title]) {
            $slug = Slugger::slug($title);

            Article::query()->firstOrCreate(
                ['language' => $language, 'slug' => $slug],
                [
                    'title' => $title,
                    'summary' => $title,
                    'body_json' => [
                        'schema' => 1,
                        'type' => 'doc',
                        'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $title]]]],
                    ],
                    'body_html' => '<p class="cms-block cms-block--paragraph">'.e($title).'</p>',
                    'body_text' => $title,
                    'primary_category_id' => $category->getKey(),
                    'author_id' => $author->getKey(),
                    'status' => 'published',
                    'published_at' => now()->subDay(),
                    'created_by' => $user->getKey(),
                    'updated_by' => $user->getKey(),
                ]
            );
        }

        $this->command->info('CorpusSeeder: labelled set present ('.count(self::LABELLED).' articles).');

        if (config('app.corpus_full', false)) {
            Article::factory()->count(10000)->create();
            $this->command->info('CorpusSeeder: full 10k volume seeded.');
        }
    }
}
