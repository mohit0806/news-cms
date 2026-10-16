<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Settings\AnalyticsSettings;
use App\Settings\AppearanceSettings;
use App\Settings\CacheSettings;
use App\Settings\ContactSettings;
use App\Settings\ContentSettings;
use App\Settings\GeneralSettings;
use App\Settings\LocaleSettings;
use App\Settings\NewsSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Group slug => [settings class, label].
     *
     * @return array<string, array{class-string<NewsSettings>, string}>
     */
    public static function groups(): array
    {
        return [
            'general' => [GeneralSettings::class, 'General'],
            'contact' => [ContactSettings::class, 'Contact'],
            'social' => [SocialSettings::class, 'Social'],
            'seo' => [SeoSettings::class, 'SEO'],
            'content' => [ContentSettings::class, 'Content'],
            'appearance' => [AppearanceSettings::class, 'Appearance'],
            'analytics' => [AnalyticsSettings::class, 'Analytics'],
            'locale' => [LocaleSettings::class, 'Locale'],
            'cache' => [CacheSettings::class, 'Cache'],
        ];
    }

    /**
     * Field definitions per settings class: property, widget type, label.
     *
     * @return array<string, list<array{property: string, type: string, label: string}>>
     */
    public static function fields(): array
    {
        return [
            GeneralSettings::class => [
                ['property' => 'site_name', 'type' => 'language', 'label' => 'Site name'],
                ['property' => 'tagline', 'type' => 'language', 'label' => 'Tagline'],
                ['property' => 'logo_light', 'type' => 'text', 'label' => 'Light logo URL'],
                ['property' => 'logo_dark', 'type' => 'text', 'label' => 'Dark logo URL'],
                ['property' => 'favicon', 'type' => 'text', 'label' => 'Favicon URL'],
                ['property' => 'default_language', 'type' => 'text', 'label' => 'Default language'],
                ['property' => 'display_timezone', 'type' => 'text', 'label' => 'Display timezone'],
            ],
            ContactSettings::class => [
                ['property' => 'email', 'type' => 'text', 'label' => 'Email'],
                ['property' => 'phone', 'type' => 'text', 'label' => 'Phone'],
                ['property' => 'address', 'type' => 'language', 'label' => 'Address'],
            ],
            SocialSettings::class => [
                ['property' => 'profiles', 'type' => 'profiles-json', 'label' => 'Profiles (JSON object of label to URL)'],
            ],
            SeoSettings::class => [
                ['property' => 'title_template', 'type' => 'text', 'label' => 'Title template'],
                ['property' => 'meta_description_fallback', 'type' => 'text', 'label' => 'Meta description fallback'],
                ['property' => 'default_og_image', 'type' => 'text', 'label' => 'Default OG image'],
                ['property' => 'robots_default', 'type' => 'text', 'label' => 'Default robots'],
                ['property' => 'tag_index_min_articles', 'type' => 'number', 'label' => 'Tag index minimum articles'],
            ],
            ContentSettings::class => [
                ['property' => 'require_featured_image', 'type' => 'toggle', 'label' => 'Require featured image'],
                ['property' => 'require_alt_text', 'type' => 'toggle', 'label' => 'Require alt text'],
                ['property' => 'articles_per_page', 'type' => 'number', 'label' => 'Articles per page'],
                ['property' => 'trash_retention_days', 'type' => 'number', 'label' => 'Trash retention (days)'],
                ['property' => 'revision_count', 'type' => 'number', 'label' => 'Revision count'],
                ['property' => 'breaking_default_hours', 'type' => 'number', 'label' => 'Breaking default (hours)'],
            ],
            AppearanceSettings::class => [
                ['property' => 'active_theme', 'type' => 'text', 'label' => 'Active theme'],
            ],
            AnalyticsSettings::class => [
                ['property' => 'analytics_enabled', 'type' => 'toggle', 'label' => 'Analytics enabled'],
                ['property' => 'trending_half_life_hours', 'type' => 'number', 'label' => 'Trending half-life (hours)'],
            ],
            LocaleSettings::class => [
                ['property' => 'enabled_languages', 'type' => 'languages-csv', 'label' => 'Enabled languages (comma-separated)'],
            ],
            CacheSettings::class => [
                ['property' => 'ttl_article_seconds', 'type' => 'number', 'label' => 'Article TTL (seconds)'],
                ['property' => 'ttl_listing_seconds', 'type' => 'number', 'label' => 'Listing TTL (seconds)'],
                ['property' => 'ttl_homepage_seconds', 'type' => 'number', 'label' => 'Homepage TTL (seconds)'],
            ],
        ];
    }

    public function index(): View
    {
        return view('settings.index', ['groups' => self::groups()]);
    }

    public function edit(string $group): View
    {
        [$class, $label] = $this->resolveGroup($group);

        $settings = app($class);

        $languages = config('app.site_languages', ['en']);
        $languages = is_array($languages) && $languages !== [] ? array_values($languages) : ['en'];

        return view('settings.edit', [
            'group' => $group,
            'label' => $label,
            'values' => $settings->toArray(),
            'fields' => self::fields()[$class],
            'languages' => $languages,
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        [$class] = $this->resolveGroup($group);

        /** @var NewsSettings $settings */
        $settings = app($class);

        $input = $request->all();

        foreach (self::fields()[$class] as $field) {
            if ($field['type'] === 'toggle') {
                $input[$field['property']] = $request->boolean($field['property']);
            }

            if ($field['type'] === 'profiles-json') {
                $input[$field['property']] = $this->decodeProfiles($request->input($field['property']));
            }

            if ($field['type'] === 'languages-csv') {
                $input[$field['property']] = $this->decodeLanguages($request->input($field['property']));
            }
        }

        $validated = Validator::make($input, $class::rules())->validate();

        $settings->fill(array_merge($settings->toArray(), $validated));
        $settings->save();

        return back()->with('toast', __('Settings saved.'));
    }

    /**
     * @return array{0: class-string<NewsSettings>, 1: string}
     */
    private function resolveGroup(string $group): array
    {
        abort_unless(isset(self::groups()[$group]), 404);

        return self::groups()[$group];
    }

    /**
     * @return array<string, string>
     */
    private function decodeProfiles(mixed $value): array
    {
        $decoded = is_string($value) ? json_decode($value, true) : null;

        if (! is_array($decoded)) {
            abort(422, 'Profiles must be a JSON object.');
        }

        $validator = Validator::make(
            ['urls' => array_values($decoded)],
            ['urls.*' => ['required', 'url', 'max:500']]
        );

        if ($validator->fails()) {
            abort(422, 'Every profile value must be a valid URL.');
        }

        $profiles = [];

        foreach ($decoded as $label => $url) {
            $profiles[(string) $label] = (string) $url;
        }

        return $profiles;
    }

    /**
     * @return list<string>
     */
    private function decodeLanguages(mixed $value): array
    {
        $codes = array_values(array_filter(array_map(
            fn ($code): string => trim((string) $code),
            explode(',', (string) ($value ?? '')),
        )));

        foreach ($codes as $code) {
            if (preg_match('/^[a-z]{2,3}(-[a-zA-Z]{2,8})?$/', $code) !== 1) {
                abort(422, "Invalid language code [{$code}].");
            }
        }

        abort_if($codes === [], 422, 'At least one language is required.');

        return $codes;
    }
}
