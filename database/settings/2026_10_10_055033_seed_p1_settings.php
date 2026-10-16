<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Seed every P1 settings group with its documented defaults (PRD §15.6).
     *
     * Required: the package only saves properties backed by database rows —
     * class defaults alone are treated as missing on save. Fresh installs and
     * every test database get these rows from this migration.
     */
    public function up(): void
    {
        $this->migrator->add('general.site_name', []);
        $this->migrator->add('general.tagline', []);
        $this->migrator->add('general.logo_light', null);
        $this->migrator->add('general.logo_dark', null);
        $this->migrator->add('general.favicon', null);
        $this->migrator->add('general.default_language', 'en');
        $this->migrator->add('general.display_timezone', 'Asia/Kolkata');

        $this->migrator->add('contact.email', null);
        $this->migrator->add('contact.phone', null);
        $this->migrator->add('contact.address', []);

        $this->migrator->add('social.profiles', []);

        $this->migrator->add('seo.title_template', '{title} — {site}');
        $this->migrator->add('seo.meta_description_fallback', null);
        $this->migrator->add('seo.default_og_image', null);
        $this->migrator->add('seo.robots_default', 'index,follow');
        $this->migrator->add('seo.tag_index_min_articles', 5);

        $this->migrator->add('content.require_featured_image', true);
        $this->migrator->add('content.require_alt_text', true);
        $this->migrator->add('content.articles_per_page', 20);
        $this->migrator->add('content.trash_retention_days', 30);
        $this->migrator->add('content.revision_count', 50);
        $this->migrator->add('content.breaking_default_hours', 2);

        $this->migrator->add('appearance.active_theme', 'default');

        $this->migrator->add('analytics.analytics_enabled', false);
        $this->migrator->add('analytics.trending_half_life_hours', 24.0);

        $this->migrator->add('locale.enabled_languages', ['en']);

        $this->migrator->add('cache.ttl_article_seconds', 600);
        $this->migrator->add('cache.ttl_listing_seconds', 120);
        $this->migrator->add('cache.ttl_homepage_seconds', 60);
    }
};
