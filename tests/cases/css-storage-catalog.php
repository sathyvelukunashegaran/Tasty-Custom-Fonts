<?php

declare(strict_types=1);

use TastyFonts\Adobe\AdobeCssParser;
use TastyFonts\Adobe\AdobeProjectClient;
use TastyFonts\Admin\AdminController;
use TastyFonts\Admin\AdminPageRenderer;
use TastyFonts\Api\RestController;
use TastyFonts\Bunny\BunnyCssParser;
use TastyFonts\Bunny\BunnyFontsClient;
use TastyFonts\Bunny\BunnyImportService;
use TastyFonts\Fonts\AssetService;
use TastyFonts\Fonts\BlockEditorFontLibraryService;
use TastyFonts\Fonts\CatalogService;
use TastyFonts\Fonts\CssBuilder;
use TastyFonts\Fonts\FontFilenameParser;
use TastyFonts\Fonts\HostedImportSupport;
use TastyFonts\Fonts\LibraryService;
use TastyFonts\Fonts\LocalUploadService;
use TastyFonts\Fonts\RuntimeAssetPlanner;
use TastyFonts\Fonts\RuntimeService;
use TastyFonts\Google\GoogleCssParser;
use TastyFonts\Google\GoogleFontsClient;
use TastyFonts\Google\GoogleImportService;
use TastyFonts\Plugin;
use TastyFonts\Repository\ImportRepository;
use TastyFonts\Repository\LogRepository;
use TastyFonts\Repository\SettingsRepository;
use TastyFonts\Support\FontUtils;
use TastyFonts\Support\Storage;
use TastyFonts\Updates\GitHubUpdater;

$tests['css_builder_generates_font_face_and_role_variables'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];
    $roles = [
        'heading' => 'Inter',
        'body' => 'Inter',
        'heading_fallback' => 'sans-serif',
        'body_fallback' => 'serif',
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => true,
        'minify_css_output' => false,
    ];

    $css = $builder->build($catalog, $roles, $settings);

    assertContainsValue('@font-face', $css, 'CSS builder should emit @font-face rules.');
    assertContainsValue('font-family:"Inter"', $css, 'CSS builder should include the family name.');
    assertContainsValue('--font-heading', $css, 'CSS builder should emit the heading role variable.');
    assertContainsValue('font-family: var(--font-body);', $css, 'CSS builder should emit the body usage rule.');
};

$tests['css_builder_emits_optional_monospace_role_css_when_enabled'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];
    $roles = [
        'heading' => 'Inter',
        'body' => 'Inter',
        'monospace' => '',
        'heading_fallback' => 'sans-serif',
        'body_fallback' => 'serif',
        'monospace_fallback' => 'monospace',
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => true,
        'minify_css_output' => false,
        'monospace_role_enabled' => true,
    ];

    $css = $builder->build($catalog, $roles, $settings);

    assertContainsValue('--font-monospace: monospace;', $css, 'Enabled monospace support should emit a fallback-only monospace variable when no family is selected.');
    assertContainsValue("code, pre {\n  font-family: var(--font-monospace);\n}", $css, 'Enabled monospace support should emit the code/pre usage rule.');
    assertNotContainsValue('--font-monospace: var(--font-', $css, 'Fallback-only monospace output should not point the role variable at a synthetic family variable.');
};

$tests['css_builder_omits_monospace_role_css_when_feature_is_disabled'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];
    $roles = [
        'heading' => 'Inter',
        'body' => 'Inter',
        'monospace' => 'JetBrains Mono',
        'heading_fallback' => 'sans-serif',
        'body_fallback' => 'sans-serif',
        'monospace_fallback' => 'monospace',
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => true,
        'minify_css_output' => false,
        'monospace_role_enabled' => false,
    ];

    $css = $builder->build($catalog, $roles, $settings);

    assertNotContainsValue('--font-monospace', $css, 'Disabled monospace support should not emit a monospace role variable.');
    assertNotContainsValue('code, pre {', $css, 'Disabled monospace support should not emit the code/pre usage rule.');
};

$tests['css_builder_can_generate_font_faces_without_role_usage_rules'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => true,
        'minify_css_output' => false,
    ];

    $css = $builder->buildFontFaceOnly($catalog, $settings);

    assertContainsValue('@font-face', $css, 'Font-face-only CSS should still emit @font-face rules.');
    assertNotContainsValue('--font-heading', $css, 'Font-face-only CSS should not emit role variables.');
    assertNotContainsValue('font-family: var(--font-body);', $css, 'Font-face-only CSS should not emit body usage rules.');
    assertNotContainsValue('--font-monospace', $css, 'Font-face-only CSS should not emit monospace role variables either.');
};

$tests['css_builder_ignores_eot_and_svg_sources'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'eot' => 'https://example.com/fonts/inter.eot',
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                        'svg' => 'https://example.com/fonts/inter.svg',
                    ],
                ],
            ],
        ],
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => false,
        'minify_css_output' => false,
    ];

    $css = $builder->buildFontFaceOnly($catalog, $settings);

    assertContainsValue('format("woff2")', $css, 'CSS builder should continue to emit supported modern formats.');
    assertNotContainsValue('embedded-opentype', $css, 'CSS builder should not emit legacy EOT sources.');
    assertNotContainsValue('inter.svg', $css, 'CSS builder should not emit deprecated SVG font sources.');
};

$tests['css_builder_preserves_raw_query_strings_in_source_urls'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['google'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'google',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2?display=swap&subset=latin',
                    ],
                ],
            ],
        ],
    ];

    $css = $builder->buildFontFaceOnly($catalog, ['minify_css_output' => false]);

    assertContainsValue(
        'url("https://example.com/fonts/inter.woff2?display=swap&subset=latin") format("woff2")',
        $css,
        'CSS builder should preserve raw query-string separators inside font source URLs.'
    );
    assertNotContainsValue('&#038;', $css, 'CSS builder should not HTML-escape ampersands inside CSS source URLs.');
};

$tests['css_builder_minifies_generated_css_without_leaving_layout_whitespace'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];
    $roles = [
        'heading' => 'Inter',
        'body' => 'Inter',
        'heading_fallback' => 'sans-serif',
        'body_fallback' => 'sans-serif',
    ];
    $settings = [
        'font_display' => 'swap',
        'auto_apply_roles' => true,
        'minify_css_output' => true,
    ];

    $css = $builder->build($catalog, $roles, $settings);

    assertSameValue(false, str_contains($css, "\n"), 'Minified CSS should not leave newline characters in the generated output.');
    assertSameValue(false, str_contains($css, "\t"), 'Minified CSS should not leave tab characters in the generated output.');
    assertContainsValue('@font-face{font-family:"Inter";font-weight:400;font-style:normal;', $css, 'Minified CSS should collapse @font-face declarations into a compact form.');
    assertContainsValue('body{font-family:var(--font-body)}', $css, 'Minified CSS should collapse role usage rules into a compact form.');
};

$tests['css_builder_format_output_respects_minify_flag'] = static function (): void {
    $builder = new CssBuilder();
    $snippet = ":root {\n  --font-heading: var(--font-lora);\n}\n";

    assertSameValue($snippet, $builder->formatOutput($snippet, false), 'Formatted output should preserve readable snippets when minification is disabled.');
    assertSameValue(':root{--font-heading:var(--font-lora)}', $builder->formatOutput($snippet, true), 'Formatted output should minify snippets when requested.');
};

$tests['css_builder_defaults_font_display_to_optional'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
    ];

    $css = $builder->buildFontFaceOnly($catalog, ['minify_css_output' => false]);

    assertContainsValue('font-display:optional;', $css, 'Generated font-face CSS should default to font-display optional when no explicit setting is stored.');
};

$tests['css_builder_uses_per_family_font_display_overrides'] = static function (): void {
    $builder = new CssBuilder();
    $catalog = [
        'Inter' => [
            'family' => 'Inter',
            'slug' => 'inter',
            'sources' => ['local'],
            'faces' => [
                [
                    'family' => 'Inter',
                    'slug' => 'inter',
                    'source' => 'local',
                    'weight' => '400',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/inter.woff2',
                    ],
                ],
            ],
        ],
        'Lora' => [
            'family' => 'Lora',
            'slug' => 'lora',
            'sources' => ['google'],
            'faces' => [
                [
                    'family' => 'Lora',
                    'slug' => 'lora',
                    'source' => 'google',
                    'weight' => '700',
                    'style' => 'normal',
                    'unicode_range' => '',
                    'files' => [
                        'woff2' => 'https://example.com/fonts/lora.woff2',
                    ],
                ],
            ],
        ],
    ];

    $css = $builder->buildFontFaceOnly(
        $catalog,
        [
            'font_display' => 'optional',
            'family_font_displays' => ['Inter' => 'swap'],
            'minify_css_output' => false,
        ]
    );

    assertContainsValue("font-family:\"Inter\";\n  font-weight:400;\n  font-style:normal;\n  src:url(\"https://example.com/fonts/inter.woff2\") format(\"woff2\");\n  font-display:swap;", $css, 'Per-family overrides should change the font-display value for the matching family.');
    assertContainsValue("font-family:\"Lora\";\n  font-weight:700;\n  font-style:normal;\n  src:url(\"https://example.com/fonts/lora.woff2\") format(\"woff2\");\n  font-display:optional;", $css, 'Families without an override should continue using the global font-display default.');
};

$tests['storage_returns_absolute_generated_css_url'] = static function (): void {
    resetTestState();

    $storage = new Storage();
    $url = $storage->getGeneratedCssUrl();

    assertSameValue(
        'https://example.test/wp-content/uploads/fonts/tasty-fonts.css',
        $url,
        'Generated CSS URL should stay absolute so Etch can pass it to new URL(...).'
    );
};

$tests['catalog_service_applies_catalog_filter_before_returning_results'] = static function (): void {
    resetTestState();

    $services = makeServiceGraph();
    $services['storage']->writeAbsoluteFile((string) $services['storage']->pathForRelativePath('inter/Inter-400-normal.woff2'), 'font-data');

    add_filter(
        'tasty_fonts_catalog',
        static function (array $catalog): array {
            unset($catalog['Inter']);

            return $catalog;
        }
    );

    $catalog = $services['catalog']->getCatalog();
    $counts = $services['catalog']->getCounts();

    assertSameValue(false, isset($catalog['Inter']), 'Catalog filters should be able to remove families before getCatalog() returns.');
    assertSameValue(0, (int) ($counts['families'] ?? -1), 'Catalog counts should reflect the filtered catalog payload.');
};

$tests['catalog_service_ignores_eot_and_svg_files_during_local_scan'] = static function (): void {
    resetTestState();

    $storage = new Storage();
    $storage->ensureRootDirectory();
    $storage->writeAbsoluteFile((string) $storage->pathForRelativePath('inter/Inter-400-normal.woff2'), 'font-data');
    $storage->writeAbsoluteFile((string) $storage->pathForRelativePath('legacy/Legacy-400-normal.eot'), 'font-data');
    $storage->writeAbsoluteFile((string) $storage->pathForRelativePath('vector/Vector-400-normal.svg'), 'font-data');

    $settings = new SettingsRepository();
    $imports = new ImportRepository();
    $log = new LogRepository();
    $adobe = new AdobeProjectClient($settings, new AdobeCssParser());
    $catalog = new CatalogService($storage, $imports, new FontFilenameParser(), $log, $adobe);
    $families = $catalog->getCatalog();

    assertSameValue(['Inter'], array_values(array_keys($families)), 'Catalog scanning should ignore local EOT and SVG files so the scanned formats match the upload allowlist.');
};

$tests['catalog_service_includes_live_role_families_in_published_filter_and_emits_category_aliases'] = static function (): void {
    resetTestState();

    $services = makeServiceGraph();
    $services['imports']->saveProfile(
        'Caveat',
        'caveat',
        [
            'id' => 'google-cdn',
            'label' => 'Google CDN',
            'provider' => 'google',
            'type' => 'cdn',
            'variants' => ['regular'],
            'faces' => [],
            'meta' => ['category' => 'handwriting'],
        ],
        'role_active',
        true
    );

    $family = $services['catalog']->getCatalog()['Caveat'] ?? [];
    $deliveryTokens = (array) ($family['delivery_filter_tokens'] ?? []);
    $categoryTokens = (array) ($family['font_category_tokens'] ?? []);

    assertSameValue(true, in_array('role_active', $deliveryTokens, true), 'Live role families should keep their dedicated In Use token.');
    assertSameValue(true, in_array('published', $deliveryTokens, true), 'Live role families should also match the Published library filter.');
    assertSameValue('handwriting', (string) ($family['font_category'] ?? ''), 'Catalog families should preserve their normalized font category.');
    assertSameValue(true, in_array('handwriting', $categoryTokens, true), 'Handwriting families should expose their canonical category token.');
    assertSameValue(true, in_array('script', $categoryTokens, true), 'Handwriting families should match the Script type filter.');
    assertSameValue(true, in_array('cursive', $categoryTokens, true), 'Handwriting families should match the Cursive type filter.');
};

$tests['catalog_service_inferrs_monospace_category_from_family_name_when_metadata_is_missing'] = static function (): void {
    resetTestState();

    $services = makeServiceGraph();
    $services['imports']->saveProfile(
        'JetBrains Mono',
        'jetbrains-mono',
        [
            'id' => 'local-self-hosted',
            'label' => 'Self-hosted',
            'provider' => 'local',
            'type' => 'self_hosted',
            'variants' => ['regular'],
            'faces' => [],
            'meta' => [],
        ],
        'published',
        true
    );

    $family = $services['catalog']->getCatalog()['JetBrains Mono'] ?? [];

    assertSameValue('monospace', (string) ($family['font_category'] ?? ''), 'Families with Mono in the name should infer the monospace category when provider metadata is missing.');
    assertSameValue(true, in_array('monospace', (array) ($family['font_category_tokens'] ?? []), true), 'Inferred monospace families should still emit the monospace filter token.');
};

$tests['storage_writes_absolute_files_via_wp_filesystem'] = static function (): void {
    resetTestState();

    global $wpFilesystemInitCalls;
    global $wp_filesystem;

    $storage = new Storage();
    $targetPath = uniqueTestDirectory('storage-write') . '/families/inter/inter-400.woff2';
    $written = $storage->writeAbsoluteFile($targetPath, 'font-data');

    assertSameValue(true, $written, 'Storage should write absolute files through the shared filesystem bridge.');
    assertSameValue('font-data', (string) file_get_contents($targetPath), 'Storage writes should persist the provided file contents.');
    assertSameValue(true, in_array(dirname($targetPath), $wp_filesystem->mkdirCalls, true), 'Storage writes should create missing parent directories before writing.');
    assertSameValue(1, count($wpFilesystemInitCalls), 'Storage writes should initialize the shared filesystem bridge once per write.');
};

$tests['storage_skips_wp_filesystem_when_direct_method_is_unavailable'] = static function (): void {
    resetTestState();

    global $filesystemMethod;
    global $wpFilesystemInitCalls;

    $filesystemMethod = 'ftpext';

    $storage = new Storage();
    $targetPath = uniqueTestDirectory('storage-no-direct') . '/families/inter/inter-400.woff2';
    $written = $storage->writeAbsoluteFile($targetPath, 'font-data');

    assertSameValue(false, $written, 'Storage writes should fail fast when WordPress cannot use the direct filesystem method.');
    assertSameValue(0, count($wpFilesystemInitCalls), 'Storage should not bootstrap WP_Filesystem when the direct method is unavailable.');
    assertContainsValue(
        'Direct filesystem access is unavailable',
        $storage->getLastFilesystemErrorMessage(),
        'Storage should expose a clear error message when direct filesystem access is unavailable.'
    );
};

$tests['storage_can_copy_absolute_files_without_buffering_contents'] = static function (): void {
    resetTestState();

    $storage = new Storage();
    $sourcePath = uniqueTestDirectory('storage-copy-source') . '/inter-400.woff2';
    $targetPath = uniqueTestDirectory('storage-copy-target') . '/families/inter/inter-400.woff2';

    mkdir(dirname($sourcePath), FS_CHMOD_DIR, true);
    file_put_contents($sourcePath, 'font-data');

    $copied = $storage->copyAbsoluteFile($sourcePath, $targetPath);

    assertSameValue(true, $copied, 'Storage should copy uploaded files into the target directory without reading the whole file into PHP memory first.');
    assertSameValue('font-data', (string) file_get_contents($targetPath), 'Copied files should preserve the original contents.');
};
