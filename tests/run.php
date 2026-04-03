<?php

declare(strict_types=1);

if (!defined('ETCH_FONTS_VERSION')) {
    define('ETCH_FONTS_VERSION', '6.0.1');
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('FS_CHMOD_DIR')) {
    define('FS_CHMOD_DIR', 0755);
}

if (!defined('FS_CHMOD_FILE')) {
    define('FS_CHMOD_FILE', 0644);
}

require_once __DIR__ . '/bootstrap.php';

use EtchFonts\Admin\AdminController;
use EtchFonts\Fonts\AssetService;
use EtchFonts\Fonts\CatalogService;
use EtchFonts\Fonts\CssBuilder;
use EtchFonts\Fonts\FontFilenameParser;
use EtchFonts\Google\GoogleCssParser;
use EtchFonts\Google\GoogleFontsClient;
use EtchFonts\Repository\ImportRepository;
use EtchFonts\Repository\LogRepository;
use EtchFonts\Repository\SettingsRepository;
use EtchFonts\Support\FontUtils;
use EtchFonts\Support\Storage;

if (!class_exists('WP_Error')) {
    class WP_Error extends RuntimeException
    {
        public function __construct(private readonly string $errorCode = '', string $message = '')
        {
            parent::__construct($message);
        }

        public function get_error_message(): string
        {
            return $this->getMessage();
        }

        public function get_error_code(): string
        {
            return $this->errorCode;
        }
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string
    {
        return $text;
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $value): string
    {
        return rtrim($value, "/\\") . '/';
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit(string $value): string
    {
        return rtrim($value, "/\\");
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('wp_make_link_relative')) {
    function wp_make_link_relative(string $url): string
    {
        $parts = parse_url($url);

        return (string) ($parts['path'] ?? '');
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $path): bool
    {
        return is_dir($path) || mkdir($path, 0777, true);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\r\n\t ]+/', ' ', $value) ?? $value;

        return trim($value);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        return stripslashes($value);
    }
}

if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args(array $args, array $defaults = []): array
    {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('absint')) {
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

if (!function_exists('current_time')) {
    function current_time(string $type, bool $gmt = false): string
    {
        return '2026-04-03 10:00:00';
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in(): bool
    {
        return false;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error(mixed $value): bool
    {
        return $value instanceof WP_Error;
    }
}

$tests = [];
$optionStore = [];
$transientStore = [];
$transientDeleted = [];
$transientSet = [];
$uploadBaseDir = sys_get_temp_dir() . '/etch-fonts-tests/uploads';
$wp_filesystem = null;

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        global $optionStore;

        return $optionStore[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, bool $autoload = false): bool
    {
        global $optionStore;

        $optionStore[$option] = $value;

        return true;
    }
}

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        global $transientStore;

        return $transientStore[$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration): bool
    {
        global $transientStore;
        global $transientSet;

        $transientStore[$key] = $value;
        $transientSet[] = $key;

        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        global $transientDeleted;
        global $transientStore;

        $transientDeleted[] = $key;
        unset($transientStore[$key]);

        return true;
    }
}

if (!function_exists('WP_Filesystem')) {
    function WP_Filesystem(): bool
    {
        global $wp_filesystem;

        if (!$wp_filesystem instanceof TestWpFilesystem) {
            $wp_filesystem = new TestWpFilesystem();
        }

        return true;
    }
}

if (!function_exists('wp_get_upload_dir')) {
    function wp_get_upload_dir(): array
    {
        global $uploadBaseDir;

        return [
            'basedir' => $uploadBaseDir,
            'baseurl' => 'https://example.test/wp-content/uploads',
            'error' => false,
        ];
    }
}

final class TestWpFilesystem
{
    public array $mkdirCalls = [];
    public array $writeCalls = [];

    public function is_dir(string $path): bool
    {
        return is_dir($path);
    }

    public function mkdir(string $path, int $chmod): bool
    {
        $this->mkdirCalls[] = $path;

        return is_dir($path) || mkdir($path, $chmod, true);
    }

    public function put_contents(string $path, string $contents, int $chmod): bool
    {
        $this->writeCalls[] = $path;

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, FS_CHMOD_DIR, true) && !is_dir($directory)) {
            return false;
        }

        return file_put_contents($path, $contents) !== false;
    }
}

function uniqueTestDirectory(string $name): string
{
    return sys_get_temp_dir() . '/etch-fonts-tests/' . $name . '-' . uniqid('', true);
}

function resetTestState(): void
{
    global $optionStore;
    global $transientDeleted;
    global $transientSet;
    global $transientStore;
    global $wp_filesystem;
    global $uploadBaseDir;

    $optionStore = [];
    $transientStore = [];
    $transientDeleted = [];
    $transientSet = [];
    $uploadBaseDir = uniqueTestDirectory('uploads');
    $wp_filesystem = new TestWpFilesystem();
    $_POST = [];
    $_FILES = [];
}

function invokePrivateMethod(object $object, string $methodName, array $arguments = []): mixed
{
    $reflection = new ReflectionMethod($object, $methodName);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($object, $arguments);
}

function makeAdminControllerTestInstance(): AdminController
{
    $reflection = new ReflectionClass(AdminController::class);

    return $reflection->newInstanceWithoutConstructor();
}

$tests['font_filename_parser_detects_weight_and_style'] = static function (): void {
    $parser = new FontFilenameParser();
    $parsed = $parser->parse('Inter-ExtraBoldItalic');

    assertSameValue('Inter', $parsed['family'], 'Parser should remove suffixes from the family name.');
    assertSameValue('800', $parsed['weight'], 'Parser should detect extra-bold weight.');
    assertSameValue('italic', $parsed['style'], 'Parser should detect italic style.');
    assertSameValue(false, $parsed['is_variable'], 'Static fonts should not be marked variable.');
};

$tests['font_filename_parser_preserves_oblique_style'] = static function (): void {
    $parser = new FontFilenameParser();
    $parsed = $parser->parse('Satoshi-500Oblique');

    assertSameValue('Satoshi', $parsed['family'], 'Parser should keep the family name when reading oblique files.');
    assertSameValue('500', $parsed['weight'], 'Parser should detect the numeric weight for oblique files.');
    assertSameValue('oblique', $parsed['style'], 'Parser should preserve oblique instead of collapsing it to italic.');
};

$tests['font_utils_builds_static_upload_filename'] = static function (): void {
    $filename = FontUtils::buildStaticFontFilename('Satoshi Display', '700', 'italic', 'woff2');

    assertSameValue('Satoshi Display-700-italic.woff2', $filename, 'Uploaded static files should use deterministic scanner-friendly filenames.');
};

$tests['font_utils_normalizes_google_variants'] = static function (): void {
    $variants = FontUtils::normalizeVariantTokens(['700italic', 'regular', '700italic', 'bogus']);

    assertSameValue(['regular', '700italic'], $variants, 'Variant normalization should dedupe and discard unsupported tokens.');
};

$tests['font_utils_preserves_custom_fallback_stacks'] = static function (): void {
    $fallback = FontUtils::sanitizeFallback('-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif');
    $stack = FontUtils::buildFontStack('Inter', $fallback);

    assertSameValue('-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', $fallback, 'Fallback sanitizer should allow custom browser/system font stacks.');
    assertSameValue('"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', $stack, 'Font stack builder should preserve sanitized custom fallback stacks.');
};

$tests['font_utils_builds_face_axis_keys'] = static function (): void {
    $axisKey = FontUtils::faceAxisKey(400, 'ITALIC');

    assertSameValue('400|italic', $axisKey, 'Face axis keys should normalize weight and style before composing the dedupe key.');
};

$tests['font_utils_compares_faces_by_weight_and_style'] = static function (): void {
    $faces = [
        ['weight' => '700', 'style' => 'normal'],
        ['weight' => '400', 'style' => 'normal'],
        ['weight' => '400', 'style' => 'italic'],
    ];

    usort($faces, [FontUtils::class, 'compareFacesByWeightAndStyle']);

    assertSameValue(
        [
            ['weight' => '400', 'style' => 'italic'],
            ['weight' => '400', 'style' => 'normal'],
            ['weight' => '700', 'style' => 'normal'],
        ],
        $faces,
        'Face sorting should remain stable across shared catalog/import comparators.'
    );
};

$tests['google_css_parser_extracts_woff2_faces_and_unicode_ranges'] = static function (): void {
    $css = <<<'CSS'
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 400;
  src: url(https://fonts.gstatic.com/s/inter/v18/u-4k0qWljRw-PfU81xCK.woff2) format('woff2');
  unicode-range: U+0000-00FF;
}
@font-face {
  font-family: 'Inter';
  font-style: italic;
  font-weight: 700;
  src: url(https://fonts.gstatic.com/s/inter/v18/u-4i0qWljRw.woff2) format('woff2');
  unicode-range: U+0100-024F;
}
CSS;

    $parser = new GoogleCssParser();
    $faces = $parser->parse($css, 'Inter');

    assertSameValue(2, count($faces), 'Google CSS parser should return one face per @font-face block.');
    assertSameValue('400', $faces[0]['weight'], 'Google CSS parser should capture the weight.');
    assertSameValue('italic', $faces[1]['style'], 'Google CSS parser should capture style.');
    assertSameValue('U+0100-024F', $faces[1]['unicode_range'], 'Google CSS parser should preserve unicode-range.');
    assertSameValue('https://fonts.gstatic.com/s/inter/v18/u-4k0qWljRw-PfU81xCK.woff2', $faces[0]['files']['woff2'], 'Google CSS parser should keep the remote WOFF2 URL.');
};

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

$tests['storage_returns_absolute_generated_css_url'] = static function (): void {
    resetTestState();

    $storage = new Storage();
    $url = $storage->getGeneratedCssUrl();

    assertSameValue(
        'https://example.test/wp-content/uploads/fonts/etch-fonts.css',
        $url,
        'Generated CSS URL should stay absolute so Etch can pass it to new URL(...).'
    );
};

$tests['storage_writes_absolute_files_via_wp_filesystem'] = static function (): void {
    resetTestState();

    global $wp_filesystem;

    $storage = new Storage();
    $targetPath = uniqueTestDirectory('storage-write') . '/families/inter/inter-400.woff2';
    $written = $storage->writeAbsoluteFile($targetPath, 'font-data');

    assertSameValue(true, $written, 'Storage should write absolute files through the shared filesystem bridge.');
    assertSameValue('font-data', (string) file_get_contents($targetPath), 'Storage writes should persist the provided file contents.');
    assertSameValue(true, in_array(dirname($targetPath), $wp_filesystem->mkdirCalls, true), 'Storage writes should create missing parent directories before writing.');
};

$tests['google_fonts_client_clears_catalog_cache'] = static function (): void {
    resetTestState();

    global $transientDeleted;
    global $transientStore;

    $transientStore['etch_fonts_google_catalog_v1'] = ['family' => 'Inter'];

    $client = new GoogleFontsClient(new SettingsRepository());
    $client->clearCatalogCache();

    assertSameValue(false, array_key_exists('etch_fonts_google_catalog_v1', $transientStore), 'Google catalog cache clearing should remove the cached catalog transient.');
    assertSameValue(true, in_array('etch_fonts_google_catalog_v1', $transientDeleted, true), 'Google catalog cache clearing should delete the expected transient key.');
};

$tests['asset_service_refresh_generated_assets_invalidates_caches_and_rewrites_css'] = static function (): void {
    resetTestState();

    global $optionStore;
    global $transientDeleted;
    global $transientStore;

    $optionStore[SettingsRepository::OPTION_SETTINGS] = [
        'auto_apply_roles' => false,
        'css_delivery_mode' => 'file',
        'font_display' => 'swap',
        'minify_css_output' => false,
        'preview_sentence' => '',
        'google_api_key' => '',
        'google_api_key_status' => 'empty',
        'google_api_key_status_message' => '',
        'google_api_key_checked_at' => 0,
        'family_fallbacks' => [],
    ];
    $optionStore[SettingsRepository::OPTION_ROLES] = [];
    $transientStore['etch_fonts_catalog_v2'] = ['stale' => true];
    $transientStore['etch_fonts_css_v2'] = 'stale-css';
    $transientStore['etch_fonts_css_hash_v2'] = 'stale-hash';

    $storage = new Storage();
    $catalog = new CatalogService($storage, new ImportRepository(), new FontFilenameParser(), new LogRepository());
    $assets = new AssetService($storage, $catalog, new SettingsRepository(), new CssBuilder(), new LogRepository());

    $assets->refreshGeneratedAssets();

    $generatedPath = $storage->getGeneratedCssPath();
    $generatedCss = is_string($generatedPath) && file_exists($generatedPath)
        ? (string) file_get_contents($generatedPath)
        : '';

    assertSameValue(true, is_string($generatedPath) && file_exists($generatedPath), 'Refreshing generated assets should rewrite the generated CSS file.');
    assertContainsValue('/* Version: ' . ETCH_FONTS_VERSION . ' */', $generatedCss, 'Refreshing generated assets should write the versioned CSS header.');
    assertSameValue(true, in_array('etch_fonts_catalog_v2', $transientDeleted, true), 'Refreshing generated assets should invalidate the catalog cache first.');
    assertSameValue(true, in_array('etch_fonts_css_v2', $transientDeleted, true), 'Refreshing generated assets should invalidate the cached CSS payload.');
    assertSameValue(true, in_array('etch_fonts_css_hash_v2', $transientDeleted, true), 'Refreshing generated assets should invalidate the cached CSS hash.');
    assertSameValue(true, array_key_exists('etch_fonts_css_v2', $transientStore), 'Refreshing generated assets should rebuild the CSS transient after invalidation.');
    assertSameValue(true, array_key_exists('etch_fonts_css_hash_v2', $transientStore), 'Refreshing generated assets should rebuild the CSS hash transient after invalidation.');
};

$tests['admin_controller_falls_back_to_variant_tokens_when_variants_are_missing'] = static function (): void {
    resetTestState();

    $_POST['variant_tokens'] = 'regular, 700italic';

    $controller = makeAdminControllerTestInstance();
    $variants = invokePrivateMethod($controller, 'getPostedGoogleVariants');

    assertSameValue(['regular', '700italic'], $variants, 'Google import requests should fall back to the comma-separated variant token field when checkbox values are absent.');
};

$tests['admin_controller_normalizes_uploaded_files_by_sparse_row_index'] = static function (): void {
    resetTestState();

    $_POST['rows'] = [
        7 => [
            'family' => 'Inter',
            'weight' => '400',
            'style' => 'italic',
            'fallback' => 'Arial, sans-serif',
        ],
    ];
    $_FILES['files'] = [
        'name' => [7 => 'inter-400-italic.woff2'],
        'type' => [7 => 'font/woff2'],
        'tmp_name' => [7 => '/tmp/php-font'],
        'error' => [7 => UPLOAD_ERR_OK],
        'size' => [7 => 2048],
    ];

    $controller = makeAdminControllerTestInstance();
    $rows = invokePrivateMethod($controller, 'getPostedUploadRows');

    assertSameValue('Inter', $rows[0]['family'], 'Uploaded row normalization should preserve the family name.');
    assertSameValue('italic', $rows[0]['style'], 'Uploaded row normalization should preserve the submitted style.');
    assertSameValue('inter-400-italic.woff2', $rows[0]['file']['name'], 'Uploaded row normalization should attach the correct file payload to a sparse row index.');
    assertSameValue(2048, $rows[0]['file']['size'], 'Uploaded row normalization should preserve the uploaded file size.');
};

$tests['admin_controller_sanitizes_posted_fallback_values'] = static function (): void {
    resetTestState();

    $_POST['fallback'] = '  -apple-system, BlinkMacSystemFont, "Segoe UI", serif !@#$  ';

    $controller = makeAdminControllerTestInstance();
    $fallback = invokePrivateMethod($controller, 'getPostedFallback', ['fallback']);

    assertSameValue(
        '-apple-system, BlinkMacSystemFont, "Segoe UI", serif',
        $fallback,
        'Posted fallback values should be normalized through the controller before they reach settings storage.'
    );
};

$failures = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] {$name}\n";
    } catch (Throwable $throwable) {
        $failures++;
        echo "[FAIL] {$name}\n";
        echo $throwable->getMessage() . "\n";
    }
}

exit($failures > 0 ? 1 : 0);
