<?php

declare(strict_types=1);

namespace TastyFonts\Integrations;

defined('ABSPATH') || exit;

use TastyFonts\Support\FontUtils;

final class BricksIntegrationService
{
    public function isAvailable(): bool
    {
        $available = class_exists(\Bricks\Database::class) && defined('BRICKS_DB_THEME_STYLES');

        if (function_exists('apply_filters')) {
            $available = (bool) apply_filters('tasty_fonts_bricks_integration_available', $available);
        }

        return $available;
    }

    public function readState(?bool $enabled): array
    {
        $available = $this->isAvailable();
        $effectiveEnabled = $available && $enabled !== false;

        return [
            'available' => $available,
            'enabled' => $effectiveEnabled,
            'configured' => $enabled !== null,
            'status' => !$available ? 'unavailable' : ($effectiveEnabled ? 'active' : 'disabled'),
        ];
    }

    public function filterStandardFonts(array $fonts, array $runtimeFamilies): array
    {
        $merged = [];
        $seen = [];

        foreach ($fonts as $font) {
            $name = is_string($font) ? trim($font) : '';

            if ($name === '' || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $merged[] = $name;
        }

        foreach ($this->runtimeFamilyNames($runtimeFamilies) as $familyName) {
            if (isset($seen[$familyName])) {
                continue;
            }

            $seen[$familyName] = true;
            $merged[] = $familyName;
        }

        return $merged;
    }

    public function getEditorStyles(array $runtimeFamilies): array
    {
        $runtimeLookup = $this->runtimeFamilyLookup($runtimeFamilies);

        if ($runtimeLookup === []) {
            return [];
        }

        $settings = $this->getActiveThemeStyleSettings();

        if ($settings === []) {
            return [];
        }

        $styles = [];
        $bodyFamily = $this->managedFamilyName($settings['typography']['typographyBody']['font-family'] ?? '', $runtimeLookup);

        if ($bodyFamily !== '') {
            $styles[] = $this->buildEditorRule('body', $bodyFamily);
        }

        foreach (['H1', 'H2', 'H3', 'H4', 'H5', 'H6'] as $headingLevel) {
            $familyName = $this->managedFamilyName(
                $settings['typography']['typographyHeading' . $headingLevel]['font-family'] ?? '',
                $runtimeLookup
            );

            if ($familyName === '') {
                continue;
            }

            $selector = $headingLevel === 'H1'
                ? 'body :is(h1, .editor-post-title)'
                : 'body ' . strtolower($headingLevel);

            $styles[] = $this->buildEditorRule($selector, $familyName);
        }

        return array_values(array_unique($styles));
    }

    private function getActiveThemeStyleSettings(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $optionName = defined('BRICKS_DB_THEME_STYLES') ? (string) constant('BRICKS_DB_THEME_STYLES') : '';

        if ($optionName === '') {
            return [];
        }

        $styles = get_option($optionName, []);

        if (!is_array($styles) || !class_exists(\Bricks\Database::class) || !method_exists(\Bricks\Database::class, 'screen_conditions')) {
            return [];
        }

        $postId = function_exists('get_the_ID') ? absint(get_the_ID()) : 0;
        $matchedStyles = [];

        foreach ($styles as $styleId => $style) {
            if (!is_array($style)) {
                continue;
            }

            $conditions = $style['settings']['conditions']['conditions'] ?? null;

            if (!is_array($conditions)) {
                continue;
            }

            $matchedStyles = \Bricks\Database::screen_conditions(
                $matchedStyles,
                (string) $styleId,
                $conditions,
                $postId,
                ''
            );
        }

        if ($matchedStyles === []) {
            return [];
        }

        ksort($matchedStyles, SORT_NUMERIC);
        $activeStyleId = array_pop($matchedStyles);

        return is_array($styles[$activeStyleId]['settings'] ?? null)
            ? $styles[$activeStyleId]['settings']
            : [];
    }

    private function buildEditorRule(string $selector, string $familyName): string
    {
        return $selector . '{font-family:' . FontUtils::buildFontStack($familyName) . ';}';
    }

    private function runtimeFamilyNames(array $runtimeFamilies): array
    {
        $names = array_keys($this->runtimeFamilyLookup($runtimeFamilies));
        natcasesort($names);

        return array_values($names);
    }

    private function runtimeFamilyLookup(array $runtimeFamilies): array
    {
        $lookup = [];

        foreach ($runtimeFamilies as $family) {
            $name = '';

            if (is_string($family)) {
                $name = trim($family);
            } elseif (is_array($family)) {
                $name = trim((string) ($family['family'] ?? ''));
            }

            if ($name === '') {
                continue;
            }

            $lookup[$name] = true;
        }

        return $lookup;
    }

    private function managedFamilyName(mixed $value, array $runtimeLookup): string
    {
        $familyName = is_scalar($value) ? trim((string) $value) : '';

        return $familyName !== '' && isset($runtimeLookup[$familyName]) ? $familyName : '';
    }
}
