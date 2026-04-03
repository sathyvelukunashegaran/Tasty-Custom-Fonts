<?php

declare(strict_types=1);

namespace EtchFonts\Fonts;

use EtchFonts\Repository\ImportRepository;
use EtchFonts\Repository\LogRepository;
use EtchFonts\Repository\SettingsRepository;
use EtchFonts\Support\FontUtils;
use EtchFonts\Support\Storage;
use WP_Error;

final class LibraryService
{
    public function __construct(
        private readonly Storage $storage,
        private readonly CatalogService $catalog,
        private readonly ImportRepository $imports,
        private readonly AssetService $assets,
        private readonly LogRepository $log,
        private readonly SettingsRepository $settings
    ) {
    }

    public function deleteFamily(string $familySlug): bool|WP_Error
    {
        $familySlug = FontUtils::slugify($familySlug);
        $family = $this->findFamilyBySlug($familySlug);

        if ($family === null) {
            return $this->error(
                'etch_fonts_family_not_found',
                __('That font family could not be found in the library.', 'etch-fonts')
            );
        }

        $roles = $this->settings->getRoles($this->catalog->getCatalog());
        $familyName = (string) ($family['family'] ?? $familySlug);
        $roleLabels = [];

        if (($roles['heading'] ?? '') === $familyName) {
            $roleLabels[] = __('heading', 'etch-fonts');
        }

        if (($roles['body'] ?? '') === $familyName) {
            $roleLabels[] = __('body', 'etch-fonts');
        }

        if ($roleLabels !== []) {
            return $this->error(
                'etch_fonts_family_in_use',
                sprintf(
                    __('%1$s is currently assigned as the %2$s font. Choose a different heading/body font before deleting it.', 'etch-fonts'),
                    $familyName,
                    implode(__(' and ', 'etch-fonts'), $roleLabels)
                )
            );
        }

        $relativePaths = $this->collectRelativePaths($family);

        if (!$this->storage->deleteRelativeFiles($relativePaths)) {
            return $this->error(
                'etch_fonts_delete_failed',
                __('The font files could not be deleted from uploads/fonts.', 'etch-fonts')
            );
        }

        if (
            in_array('google', (array) ($family['sources'] ?? []), true)
            && !$this->storage->deleteRelativeDirectory('google/' . $familySlug)
        ) {
            return $this->error(
                'etch_fonts_delete_failed',
                __('The Google Fonts folder could not be removed cleanly.', 'etch-fonts')
            );
        }

        $this->imports->delete($familySlug);
        $this->assets->refreshGeneratedAssets();

        $fileCount = count($relativePaths);
        $this->log->add(
            sprintf(
                __('Font family deleted: %1$s (%2$d file%3$s removed).', 'etch-fonts'),
                (string) ($family['family'] ?? $familySlug),
                $fileCount,
                $fileCount === 1 ? '' : 's'
            )
        );

        return true;
    }

    private function findFamilyBySlug(string $familySlug): ?array
    {
        foreach ($this->catalog->getCatalog() as $family) {
            $slug = is_string($family['slug'] ?? null) ? $family['slug'] : '';

            if ($slug === $familySlug) {
                return $family;
            }
        }

        return null;
    }

    private function collectRelativePaths(array $family): array
    {
        $paths = [];

        foreach ((array) ($family['faces'] ?? []) as $face) {
            foreach ((array) ($face['paths'] ?? []) as $path) {
                if (!is_string($path) || trim($path) === '') {
                    continue;
                }

                $paths[] = trim($path);
            }
        }

        return array_values(array_unique($paths));
    }

    private function error(string $code, string $message): WP_Error
    {
        $this->log->add($message);

        return new WP_Error($code, $message);
    }
}
