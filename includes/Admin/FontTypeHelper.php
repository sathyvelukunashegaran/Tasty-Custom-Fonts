<?php

declare(strict_types=1);

namespace TastyFonts\Admin;

defined('ABSPATH') || exit;

use TastyFonts\Support\FontUtils;

final class FontTypeHelper
{
    public static function describeEntry(array $entry, string $context = 'library'): array
    {
        return self::describe(self::entryHasVariableMetadata($entry), $context);
    }

    public static function describe(bool $hasVariableMetadata, string $context = 'library'): array
    {
        $normalizedContext = strtolower(trim($context));

        if (!$hasVariableMetadata) {
            return [
                'type' => 'static',
                'label' => __('Static', 'tasty-fonts'),
                'badge_class' => '',
                'has_variable' => false,
                'is_source_only' => false,
            ];
        }

        if ($normalizedContext === 'bunny') {
            return [
                'type' => 'variable',
                'label' => __('Variable Source', 'tasty-fonts'),
                'badge_class' => 'is-warning',
                'has_variable' => true,
                'is_source_only' => true,
            ];
        }

        return [
            'type' => 'variable',
            'label' => __('Variable', 'tasty-fonts'),
            'badge_class' => 'is-role',
            'has_variable' => true,
            'is_source_only' => false,
        ];
    }

    public static function entryHasVariableMetadata(array $entry): bool
    {
        if (!empty($entry['has_variable_faces'])) {
            return true;
        }

        if (FontUtils::normalizeAxesMap($entry['variation_axes'] ?? []) !== []) {
            return true;
        }

        if (!empty($entry['is_variable'])) {
            return true;
        }

        if (FontUtils::normalizeAxesMap($entry['axes'] ?? []) !== []) {
            return true;
        }

        foreach ((array) ($entry['faces'] ?? []) as $face) {
            if (!is_array($face)) {
                continue;
            }

            if (!empty($face['is_variable']) || FontUtils::normalizeAxesMap($face['axes'] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    public static function buildSelectorOptionLabel(string $familyName, ?array $entry = null, string $context = 'library'): string
    {
        $trimmedFamilyName = trim($familyName);

        if ($trimmedFamilyName === '' || !is_array($entry) || $entry === []) {
            return $trimmedFamilyName;
        }

        $descriptor = self::describeEntry($entry, $context);

        return $trimmedFamilyName . ' · ' . (string) ($descriptor['label'] ?? '');
    }
}
