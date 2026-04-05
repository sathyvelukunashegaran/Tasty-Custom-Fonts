<?php

declare(strict_types=1);

namespace TastyFonts\Fonts;

use TastyFonts\Support\FontUtils;

final class CssBuilder
{
    private const FORMAT_ORDER = ['eot', 'woff2', 'woff', 'ttf', 'otf', 'svg'];

    public function build(array $catalog, array $roles, array $settings): string
    {
        return $this->buildCss($catalog, $roles, $settings, true);
    }

    public function buildFontFaceOnly(array $catalog, array $settings): string
    {
        return $this->buildCss($catalog, [], $settings, false);
    }

    private function buildCss(array $catalog, array $roles, array $settings, bool $includeRoleUsage): string
    {
        $blocks = [];

        foreach ($catalog as $family) {
            foreach ((array) ($family['faces'] ?? []) as $face) {
                $rule = $this->buildFaceRule($face, (string) ($settings['font_display'] ?? 'swap'));

                if ($rule !== '') {
                    $blocks[] = $rule;
                }
            }
        }

        if ($includeRoleUsage && !empty($settings['auto_apply_roles'])) {
            $usageCss = $this->buildRoleUsageSnippet($roles);

            if ($usageCss !== '') {
                $blocks[] = $usageCss;
            }
        }

        $css = implode("\n", $blocks);

        if (!empty($settings['minify_css_output'])) {
            $css = $this->minify($css);
        }

        return trim($css);
    }

    public function buildRoleUsageSnippet(array $roles): string
    {
        $variableLines = $this->buildRoleVariableLines($roles);

        if ($variableLines === []) {
            return '';
        }

        return implode(
            "\n",
            [
                ...$variableLines,
                '',
                'body {',
                '  font-family: var(--font-body);',
                '}',
                '',
                'h1, h2, h3, h4, h5, h6 {',
                '  font-family: var(--font-heading);',
                '}',
            ]
        );
    }

    public function buildRoleVariableSnippet(array $roles): string
    {
        $variableLines = $this->buildRoleVariableLines($roles);

        if ($variableLines === []) {
            return '';
        }

        return implode("\n", $variableLines);
    }

    public function buildRoleStackSnippet(array $roles): string
    {
        return implode(
            "\n",
            [
                FontUtils::buildFontStack((string) ($roles['heading'] ?? ''), (string) ($roles['heading_fallback'] ?? 'sans-serif')),
                FontUtils::buildFontStack((string) ($roles['body'] ?? ''), (string) ($roles['body_fallback'] ?? 'sans-serif')),
            ]
        );
    }

    public function buildRoleNameSnippet(array $roles): string
    {
        return implode("\n", [(string) ($roles['heading'] ?? ''), (string) ($roles['body'] ?? '')]);
    }

    private function buildFaceRule(array $face, string $display): string
    {
        $family = (string) ($face['family'] ?? '');
        $weight = FontUtils::normalizeWeight((string) ($face['weight'] ?? '400'));
        $style = FontUtils::normalizeStyle((string) ($face['style'] ?? 'normal'));
        $unicodeRange = trim((string) ($face['unicode_range'] ?? ''));
        $files = is_array($face['files'] ?? null) ? $face['files'] : [];

        if ($family === '' || $files === []) {
            return '';
        }

        $css = "@font-face{\n";
        $css .= '  font-family:"' . FontUtils::escapeFontFamily($family) . "\";\n";
        $css .= "  font-weight:{$weight};\n";
        $css .= "  font-style:{$style};\n";

        if (isset($files['eot'])) {
            $css .= '  src:url("' . esc_url((string) $files['eot']) . "\");\n";
        }

        $sources = [];

        foreach (self::FORMAT_ORDER as $format) {
            if (!isset($files[$format])) {
                continue;
            }

            $sources[] = $this->buildSourceEntry($format, (string) $files[$format]);
        }

        if ($sources !== []) {
            $css .= "  src:" . implode(",\n      ", $sources) . ";\n";
        }

        if ($unicodeRange !== '') {
            $css .= "  unicode-range:{$unicodeRange};\n";
        }

        $css .= '  font-display:' . $this->sanitizeKeyword(
            $display,
            ['auto', 'block', 'swap', 'fallback', 'optional'],
            'swap'
        ) . ";\n";
        $css .= "}\n";

        return $css;
    }

    private function minify(string $css): string
    {
        $css = preg_replace('/\r?\n *(?![\@\.:}])/', '', $css) ?? $css;
        $css = str_replace("\t", '', $css);

        return trim($css);
    }

    private function sanitizeKeyword(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function buildRoleVariableLines(array $roles): array
    {
        [$headingSlug, $bodySlug, $headingStack, $bodyStack] = $this->roleTokens($roles);

        if ($headingSlug === '' || $bodySlug === '') {
            return [];
        }

        return [
            ':root {',
            "  --font-{$headingSlug}: {$headingStack};",
            "  --font-{$bodySlug}: {$bodyStack};",
            "  --font-heading: var(--font-{$headingSlug});",
            "  --font-body: var(--font-{$bodySlug});",
            '}',
        ];
    }

    private function buildSourceEntry(string $format, string $url): string
    {
        $formatName = match ($format) {
            'eot' => 'embedded-opentype',
            'otf' => 'opentype',
            'ttf' => 'truetype',
            default => $format,
        };
        $escapedUrl = esc_url($url);

        return $format === 'eot'
            ? 'url("' . $escapedUrl . '?#iefix") format("' . $formatName . '")'
            : 'url("' . $escapedUrl . '") format("' . $formatName . '")';
    }

    private function roleTokens(array $roles): array
    {
        $heading = (string) ($roles['heading'] ?? '');
        $body = (string) ($roles['body'] ?? '');

        if ($heading === '' || $body === '') {
            return ['', '', '', ''];
        }

        return [
            FontUtils::slugify($heading),
            FontUtils::slugify($body),
            FontUtils::buildFontStack($heading, (string) ($roles['heading_fallback'] ?? 'sans-serif')),
            FontUtils::buildFontStack($body, (string) ($roles['body_fallback'] ?? 'sans-serif')),
        ];
    }
}
