<?php

declare(strict_types=1);

namespace EtchFonts\Fonts;

use EtchFonts\Adobe\AdobeProjectClient;
use EtchFonts\Support\FontUtils;
use WP_Theme_JSON_Data;

final class RuntimeService
{
    public function __construct(
        private readonly CatalogService $catalog,
        private readonly AssetService $assets,
        private readonly AdobeProjectClient $adobe
    ) {
    }

    public function enqueueFrontend(): void
    {
        $this->assets->enqueue('etch-fonts-frontend');
        $this->enqueueAdobeStylesheet('etch-fonts-adobe-frontend');

        if ($this->hasEtchCanvasRequest()) {
            $this->enqueueEtchCanvasBridge();
        }
    }

    public function enqueueEtchCanvas(): void
    {
        $this->assets->enqueue('etch-fonts-etch');
        $this->enqueueAdobeStylesheet('etch-fonts-adobe-etch');
    }

    public function enqueueBlockEditor(): void
    {
        $this->assets->enqueue('etch-fonts-editor');
        $this->enqueueAdobeStylesheet('etch-fonts-adobe-editor');
    }

    public function enqueueAdminScreenFonts(string $hookSuffix): void
    {
        if (!\EtchFonts\Admin\AdminController::isPluginAdminHook($hookSuffix)) {
            return;
        }

        $this->assets->enqueue('etch-fonts-admin-fonts');
        $this->enqueueAdobeStylesheet('etch-fonts-adobe-admin');
    }

    public function injectEditorFontPresets(WP_Theme_JSON_Data $themeJson): WP_Theme_JSON_Data
    {
        $fontFamilies = $this->buildEditorFontFamilies();

        if ($fontFamilies === []) {
            return $themeJson;
        }

        $existingData = $themeJson->get_data();
        $schemaVersion = (int) ($existingData['version'] ?? 3);

        return $themeJson->update_with(
            [
                'version' => $schemaVersion,
                'settings' => [
                    'typography' => [
                        'fontFamilies' => $fontFamilies,
                    ],
                ],
            ]
        );
    }

    private function enqueueEtchCanvasBridge(): void
    {
        $stylesheetUrls = $this->getCanvasStylesheetUrls();

        if ($stylesheetUrls === []) {
            return;
        }

        wp_enqueue_script(
            'etch-fonts-canvas',
            ETCH_FONTS_URL . 'assets/js/etch-canvas.js',
            [],
            ETCH_FONTS_VERSION,
            true
        );

        wp_localize_script(
            'etch-fonts-canvas',
            'EtchFontsCanvas',
            [
                'stylesheetUrl' => $stylesheetUrls[0] ?? '',
                'stylesheetUrls' => $stylesheetUrls,
            ]
        );
    }

    private function buildEditorFontFamilies(): array
    {
        $fontFamilies = [];

        foreach ($this->catalog->getCatalog() as $family) {
            $familyName = (string) $family['family'];

            $fontFamilies[$familyName] = [
                'name' => $familyName,
                'slug' => $family['slug'],
                'fontFamily' => FontUtils::buildFontStack($familyName, 'sans-serif'),
            ];
        }

        foreach ($this->adobe->getConfiguredFamilies() as $family) {
            $familyName = (string) ($family['family'] ?? '');

            if ($familyName === '' || isset($fontFamilies[$familyName])) {
                continue;
            }

            $fontFamilies[$familyName] = [
                'name' => $familyName,
                'slug' => (string) ($family['slug'] ?? FontUtils::slugify($familyName)),
                'fontFamily' => FontUtils::buildFontStack($familyName, 'sans-serif'),
            ];
        }

        return array_values($fontFamilies);
    }

    private function enqueueAdobeStylesheet(string $handle): void
    {
        if (!$this->adobe->canEnqueue()) {
            return;
        }

        $url = $this->adobe->getStylesheetUrl($this->adobe->getProjectId());

        if ($url === '') {
            return;
        }

        wp_enqueue_style($handle, $url, [], $this->adobe->getEnqueueVersion());
    }

    private function getCanvasStylesheetUrls(): array
    {
        $urls = [];
        $generatedUrl = $this->assets->getVersionedStylesheetUrl();

        if ($generatedUrl) {
            $urls[] = $generatedUrl;
        }

        if ($this->adobe->canEnqueue()) {
            $adobeUrl = $this->adobe->getStylesheetUrl($this->adobe->getProjectId());

            if ($adobeUrl !== '') {
                $urls[] = add_query_arg('ver', $this->adobe->getEnqueueVersion(), $adobeUrl);
            }
        }

        return array_values(array_unique(array_filter($urls, 'strlen')));
    }

    private function hasEtchCanvasRequest(): bool
    {
        if (is_admin()) {
            return false;
        }

        $etch = isset($_GET['etch']) ? sanitize_text_field(wp_unslash((string) $_GET['etch'])) : '';

        return $etch !== '';
    }
}
