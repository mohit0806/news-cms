<?php

declare(strict_types=1);

namespace App\Support\Editor;

use InvalidArgumentException;

/**
 * Single source of truth for editor blocks (TRD §8.2; P2-T04.1).
 *
 * The PHP schema is authoritative; the TipTap extensions must match it
 * (parity test in T04.4 fails CI on drift). Unknown node types, attributes,
 * or marks are rejected — never silently ignored.
 */
final class BlockSchema
{
    /**
     * Stored document schema version (TRD §8.1).
     */
    public const VERSION = 1;

    /**
     * Maximum serialized body size in bytes (TRD §8.2).
     */
    public const MAX_BODY_BYTES = 2 * 1024 * 1024;

    /**
     * Allowed inline marks.
     *
     * @var list<string>
     */
    public const MARKS = ['bold', 'italic', 'strike', 'code', 'link'];

    /**
     * Node type → allowed attributes (name → rule).
     *
     * Rules: `required` marks a mandatory attribute; `values` bounds an enum;
     * `max` bounds a string length. Attributes absent from the map are rejected.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    public const NODES = [
        'paragraph' => [],
        'heading' => ['level' => ['required' => true, 'values' => ['2', '3', '4']]],
        'blockquote' => ['attribution' => ['max' => 200]],
        'bullet_list' => [],
        'ordered_list' => [],
        'list_item' => [],
        'image' => [
            'media_id' => ['required' => true],
            'alt' => ['max' => 250],
            'caption' => ['max' => 500],
            'credit' => ['max' => 200],
            'alignment' => ['values' => ['left', 'center', 'right']],
        ],
        'gallery' => ['gallery_id' => ['required' => true]],
        'embed' => ['url' => ['required' => true, 'max' => 2000]],
        'video' => ['media_id' => ['required' => true]],
        'audio' => ['media_id' => ['required' => true]],
        'table' => [],
        'table_row' => [],
        'table_cell' => [],
        'divider' => [],
        'related_article' => ['article_ids' => ['required' => true]],
        'poll' => ['poll_id' => ['required' => true]],
        'raw_html' => ['html' => ['required' => true, 'max' => 65535]],
    ];

    /**
     * Validate a decoded body document. Throws on the first violation.
     */
    public static function validate(mixed $document): void
    {
        if (! is_array($document)) {
            throw new InvalidArgumentException('Body must be a JSON document object.');
        }

        if (($document['schema'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('Unsupported body schema version.');
        }

        if (($document['type'] ?? null) !== 'doc' || ! isset($document['content']) || ! is_array($document['content'])) {
            throw new InvalidArgumentException('Body must be a doc with a content array.');
        }

        foreach ($document['content'] as $node) {
            self::validateNode($node);
        }
    }

    private static function validateNode(mixed $node): void
    {
        if (! is_array($node) || ! isset($node['type']) || ! is_string($node['type'])) {
            throw new InvalidArgumentException('Every block must declare a type.');
        }

        $rules = self::NODES[$node['type']] ?? null;

        if ($rules === null) {
            if ($node['type'] === 'text') {
                self::validateTextLeaf($node);

                return;
            }

            throw new InvalidArgumentException("Unknown block type: {$node['type']}.");
        }

        $attrs = $node['attrs'] ?? [];

        if (! is_array($attrs)) {
            throw new InvalidArgumentException("Block {$node['type']} attrs must be an object.");
        }

        foreach ($rules as $name => $rule) {
            if (($rule['required'] ?? false) && ! array_key_exists($name, $attrs)) {
                throw new InvalidArgumentException("Block {$node['type']} requires attribute: {$name}.");
            }
        }

        foreach ($attrs as $name => $value) {
            $rule = $rules[$name] ?? null;

            if ($rule === null) {
                throw new InvalidArgumentException("Block {$node['type']} rejects attribute: {$name}.");
            }

            if (isset($rule['values']) && ! in_array((string) $value, $rule['values'], true)) {
                throw new InvalidArgumentException("Block {$node['type']} rejects value for: {$name}.");
            }

            if (isset($rule['max']) && is_string($value) && mb_strlen($value) > $rule['max']) {
                throw new InvalidArgumentException("Block {$node['type']} attribute too long: {$name}.");
            }
        }

        foreach ($node['content'] ?? [] as $child) {
            self::validateNode($child);
        }

        self::validateMarks($node['marks'] ?? []);
    }

    private static function validateMarks(mixed $marks): void
    {
        foreach ((array) $marks as $mark) {
            $markType = is_array($mark) ? ($mark['type'] ?? null) : $mark;

            if (! in_array($markType, self::MARKS, true)) {
                throw new InvalidArgumentException("Unknown mark: {$markType}.");
            }
        }
    }

    /**
     * Text leaves carry inline content, not block attributes.
     *
     * @param  array<string, mixed>  $node
     */
    private static function validateTextLeaf(array $node): void
    {
        if (! isset($node['text']) || ! is_string($node['text'])) {
            throw new InvalidArgumentException('Text leaves must carry a string.');
        }

        self::validateMarks($node['marks'] ?? []);
    }
}
