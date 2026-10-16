<?php

declare(strict_types=1);

namespace App\Support\Editor;

use Illuminate\Support\Facades\View;
use InvalidArgumentException;

/**
 * Validate → render → extract pipeline for block documents (TRD §8.3; P2-T04.1).
 *
 * Every node renders through a `blocks.*` Blade partial with escaped output;
 * stable `cms-block` class names let themes style via CSS only. Gallery, poll,
 * and embed nodes render degraded (link/placeholder card) until the P4 modules
 * and adapters exist. `raw_html` renders escaped until T04.2 wires the
 * allowlist sanitizer — never raw.
 */
final class BodyRenderer
{
    /**
     * @param  array<string, mixed>|string  $document  Decoded doc or JSON string.
     */
    public function render(array|string $document): RenderedBody
    {
        if (is_string($document)) {
            if (strlen($document) > BlockSchema::MAX_BODY_BYTES) {
                throw new InvalidArgumentException('Body exceeds the 2 MB limit.');
            }

            try {
                $decoded = json_decode($document, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new InvalidArgumentException('Body is not valid JSON.', 0, $exception);
            }
        } else {
            $decoded = $document;
        }

        BlockSchema::validate($decoded);

        /** @var list<array<string, mixed>> $nodes */
        $nodes = $decoded['content'];

        $html = '';
        $texts = [];
        $mediaIds = [];
        $embeds = [];

        foreach ($nodes as $node) {
            $html .= $this->renderNode($node);
            $texts[] = $this->extractText($node);

            ['mediaIds' => $nodeMedia, 'embeds' => $nodeEmbeds] = $this->collectReferences($node);
            $mediaIds = [...$mediaIds, ...$nodeMedia];
            $embeds = [...$embeds, ...$nodeEmbeds];
        }

        $text = trim(implode("\n\n", array_filter($texts)));

        return new RenderedBody(
            html: $html,
            text: $text,
            readingTimeMin: self::readingTime($text),
            mediaIds: array_values(array_unique($mediaIds)),
            embeds: array_values(array_unique($embeds)),
        );
    }

    /**
     * Words per 200, minimum 1 (PRD §6.1.2).
     */
    public static function readingTime(string $text): int
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($words === false ? [] : $words) / 200));
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function renderNode(array $node): string
    {
        $view = match ($node['type']) {
            'paragraph' => 'blocks.paragraph',
            'heading' => 'blocks.heading',
            'blockquote' => 'blocks.blockquote',
            'bullet_list' => 'blocks.bullet-list',
            'ordered_list' => 'blocks.ordered-list',
            'image' => 'blocks.image',
            'gallery' => 'blocks.gallery',
            'embed' => 'blocks.embed',
            'video' => 'blocks.video',
            'audio' => 'blocks.audio',
            'table' => 'blocks.table',
            'divider' => 'blocks.divider',
            'related_article' => 'blocks.related-article',
            'poll' => 'blocks.poll',
            'raw_html' => 'blocks.raw-html',
            default => throw new InvalidArgumentException("No renderer for block: {$node['type']}."),
        };

        return View::make($view, ['node' => $node, 'renderer' => $this])->render();
    }

    /**
     * Render inline content (text + marks) with escaped output.
     *
     * @param  list<array<string, mixed>>  $content
     */
    public function renderInline(array $content): string
    {
        $html = '';

        foreach ($content as $child) {
            if (($child['type'] ?? null) === 'text') {
                $html .= $this->renderMarks(
                    e((string) ($child['text'] ?? '')),
                    $child['marks'] ?? []
                );
            } else {
                $html .= $this->renderNode($child);
            }
        }

        return $html;
    }

    /**
     * @param  list<array<string, mixed>|string>  $marks
     */
    private function renderMarks(string $html, array $marks): string
    {
        foreach ($marks as $mark) {
            $type = is_array($mark) ? ($mark['type'] ?? null) : $mark;
            $attrs = is_array($mark) ? ($mark['attrs'] ?? []) : [];

            $html = match ($type) {
                'bold' => "<strong>{$html}</strong>",
                'italic' => "<em>{$html}</em>",
                'strike' => "<s>{$html}</s>",
                'code' => "<code>{$html}</code>",
                'link' => $this->renderLink($html, $attrs),
                default => $html,
            };
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function renderLink(string $html, array $attrs): string
    {
        $href = (string) ($attrs['href'] ?? '#');

        if (! preg_match('#^(https?://|mailto:|tel:)#i', $href)) {
            $href = '#';
        }

        $external = (bool) preg_match('#^https?://#i', $href);
        $target = $external ? ' target="_blank"' : '';

        return '<a href="'.e($href).'" rel="noopener noreferrer"'.$target.'>'.$html.'</a>';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function extractText(array $node): string
    {
        $texts = [];

        foreach ($node['content'] ?? [] as $child) {
            if (($child['type'] ?? null) === 'text') {
                $texts[] = (string) ($child['text'] ?? '');
            } else {
                $texts[] = $this->extractText($child);
            }
        }

        return implode(' ', array_filter($texts));
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array{mediaIds: list<int|string>, embeds: list<string>}
     */
    private function collectReferences(array $node): array
    {
        $mediaIds = [];
        $embeds = [];

        if (in_array($node['type'], ['image', 'video', 'audio'], true) && isset($node['attrs']['media_id'])) {
            $mediaIds[] = $node['attrs']['media_id'];
        }

        if ($node['type'] === 'embed' && isset($node['attrs']['url'])) {
            $embeds[] = (string) $node['attrs']['url'];
        }

        foreach ($node['content'] ?? [] as $child) {
            if (is_array($child)) {
                ['mediaIds' => $childMedia, 'embeds' => $childEmbeds] = $this->collectReferences($child);
                $mediaIds = [...$mediaIds, ...$childMedia];
                $embeds = [...$embeds, ...$childEmbeds];
            }
        }

        return ['mediaIds' => $mediaIds, 'embeds' => $embeds];
    }
}
