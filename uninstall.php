<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/plugin.php';

$settings = get_option(\TastyFonts\Repository\SettingsRepository::OPTION_SETTINGS, []);
$storage = new \TastyFonts\Support\Storage();
$settingsRepository = new \TastyFonts\Repository\SettingsRepository();
$importRepository = new \TastyFonts\Repository\ImportRepository();
$logRepository = new \TastyFonts\Repository\LogRepository();
$adobeClient = new \TastyFonts\Adobe\AdobeProjectClient(
    $settingsRepository,
    new \TastyFonts\Adobe\AdobeCssParser()
);
$googleClient = new \TastyFonts\Google\GoogleFontsClient($settingsRepository);
$catalog = new \TastyFonts\Fonts\CatalogService(
    $storage,
    $importRepository,
    new \TastyFonts\Fonts\FontFilenameParser(),
    $logRepository,
    $adobeClient
);
$planner = new \TastyFonts\Fonts\RuntimeAssetPlanner(
    $catalog,
    $settingsRepository,
    $googleClient,
    new \TastyFonts\Bunny\BunnyFontsClient(),
    $adobeClient
);
$assetService = new \TastyFonts\Fonts\AssetService(
    $storage,
    $catalog,
    $settingsRepository,
    new \TastyFonts\Fonts\CssBuilder(),
    $planner,
    $logRepository
);
$blockEditorFontLibrary = new \TastyFonts\Fonts\BlockEditorFontLibraryService(
    $storage,
    $importRepository,
    $settingsRepository,
    $logRepository
);
$developerTools = new \TastyFonts\Maintenance\DeveloperToolsService(
    $storage,
    $settingsRepository,
    $importRepository,
    $catalog,
    $assetService,
    $blockEditorFontLibrary,
    $googleClient
);

$handler = new \TastyFonts\Uninstall\UninstallHandler(
    is_array($settings) ? $settings : [],
    $storage,
    $blockEditorFontLibrary,
    $developerTools
);

$handler->run();
