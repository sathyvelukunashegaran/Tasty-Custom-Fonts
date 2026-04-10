<?php

declare(strict_types=1);

namespace TastyFonts\Admin\Renderer;

defined('ABSPATH') || exit;

use TastyFonts\Support\FontUtils;

trait LibraryRenderValueHelpers
{
    protected function roleSetsMatch(
        array $left,
        array $right,
        bool $includeMonospace,
        array $catalog = [],
        bool $includeVariableAxes = true,
        array $familyFallbacks = []
    ): bool
    {
        $roleKeys = ['heading', 'body'];

        if ($includeMonospace) {
            $roleKeys[] = 'monospace';
        }

        foreach ($roleKeys as $roleKey) {
            if (trim((string) ($left[$roleKey] ?? '')) !== trim((string) ($right[$roleKey] ?? ''))) {
                return false;
            }

            if (
                $this->resolveEffectiveRoleFallback($roleKey, $left, $catalog, $familyFallbacks)
                !== $this->resolveEffectiveRoleFallback($roleKey, $right, $catalog, $familyFallbacks)
            ) {
                return false;
            }

            if (
                trim((string) ($left[$roleKey . '_weight'] ?? ''))
                !== trim((string) ($right[$roleKey . '_weight'] ?? ''))
            ) {
                return false;
            }

            if (
                $includeVariableAxes
                && FontUtils::normalizeVariationDefaults($left[$roleKey . '_axes'] ?? [])
                    !== FontUtils::normalizeVariationDefaults($right[$roleKey . '_axes'] ?? [])
            ) {
                return false;
            }
        }

        return true;
    }

    protected function resolveEffectiveRoleFallback(
        string $roleKey,
        array $roles,
        array $catalog = [],
        array $familyFallbacks = []
    ): string
    {
        $default = $roleKey === 'monospace' ? 'monospace' : 'sans-serif';
        $familyName = trim((string) ($roles[$roleKey] ?? ''));

        if ($familyName !== '') {
            if (array_key_exists($familyName, $familyFallbacks)) {
                $configuredFallback = trim((string) $familyFallbacks[$familyName]);

                if ($configuredFallback !== '') {
                    return FontUtils::sanitizeFallback($configuredFallback);
                }
            }

            $family = $this->findCatalogFamilyByName($familyName, $catalog);

            if (is_array($family)) {
                return FontUtils::defaultFallbackForCategory($this->resolveFamilyCategory($family));
            }
        }

        $fallback = trim((string) ($roles[$roleKey . '_fallback'] ?? ''));

        return $fallback !== '' ? FontUtils::sanitizeFallback($fallback) : $default;
    }

    protected function findCatalogFamilyByName(string $familyName, array $catalog): ?array
    {
        if (is_array($catalog[$familyName] ?? null)) {
            return $catalog[$familyName];
        }

        foreach ($catalog as $family) {
            if (!is_array($family)) {
                continue;
            }

            if (trim((string) ($family['family'] ?? '')) === $familyName) {
                return $family;
            }
        }

        return null;
    }

    protected function buildFontVariableReference(string $familyName): string
    {
        return FontUtils::fontVariableReference($familyName);
    }

    protected function buildCategoryAliasOwners(array $families, array $roles, bool $includeMonospace): array
    {
        $owners = [];
        $orderedFamilies = [];
        $usedKeys = [];
        $priorityNames = [
            trim((string) ($roles['heading'] ?? '')),
            trim((string) ($roles['body'] ?? '')),
        ];

        if ($includeMonospace) {
            $priorityNames[] = trim((string) ($roles['monospace'] ?? ''));
        }

        foreach ($priorityNames as $priorityName) {
            if ($priorityName === '') {
                continue;
            }

            foreach ($families as $familyKey => $family) {
                if (!is_array($family) || isset($usedKeys[$familyKey])) {
                    continue;
                }

                if (trim((string) ($family['family'] ?? '')) !== $priorityName) {
                    continue;
                }

                $orderedFamilies[] = $family;
                $usedKeys[$familyKey] = true;
                break;
            }
        }

        foreach ($families as $familyKey => $family) {
            if (!is_array($family) || isset($usedKeys[$familyKey])) {
                continue;
            }

            $orderedFamilies[] = $family;
        }

        foreach ($orderedFamilies as $family) {
            $property = $this->resolveCategoryAliasProperty(
                $this->resolveFamilyCategory((array) $family)
            );

            if (
                $property === ''
                || (!$includeMonospace && $property === '--font-mono')
                || isset($owners[$property])
            ) {
                continue;
            }

            $owners[$property] = trim((string) ($family['family'] ?? ''));
        }

        return $owners;
    }

    protected function resolveFamilyCategory(array $family): string
    {
        $category = trim((string) ($family['font_category'] ?? ''));

        if ($category === '' && is_array($family['active_delivery'] ?? null) && is_array($family['active_delivery']['meta'] ?? null)) {
            $category = trim((string) ($family['active_delivery']['meta']['category'] ?? ''));
        }

        return $category;
    }

    protected function resolveCategoryAliasProperty(string $category): string
    {
        return match (strtolower(trim($category))) {
            'sans-serif', 'sans serif' => '--font-sans',
            'serif', 'slab-serif', 'slab serif' => '--font-serif',
            'monospace' => '--font-mono',
            default => '',
        };
    }
}
