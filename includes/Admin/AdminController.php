<?php

declare(strict_types=1);

namespace EtchFonts\Admin;

use EtchFonts\Adobe\AdobeProjectClient;
use EtchFonts\Fonts\AssetService;
use EtchFonts\Fonts\CatalogService;
use EtchFonts\Fonts\CssBuilder;
use EtchFonts\Fonts\LibraryService;
use EtchFonts\Fonts\LocalUploadService;
use EtchFonts\Google\GoogleFontsClient;
use EtchFonts\Google\GoogleImportService;
use EtchFonts\Repository\LogRepository;
use EtchFonts\Repository\SettingsRepository;
use EtchFonts\Support\FontUtils;
use EtchFonts\Support\Storage;

final class AdminController
{
    public const MENU_SLUG = 'etch-custom-fonts';

    private readonly AdminPageRenderer $renderer;

    public function __construct(
        private readonly Storage $storage,
        private readonly SettingsRepository $settings,
        private readonly LogRepository $log,
        private readonly CatalogService $catalog,
        private readonly AssetService $assets,
        private readonly LibraryService $library,
        private readonly LocalUploadService $localUpload,
        private readonly CssBuilder $cssBuilder,
        private readonly AdobeProjectClient $adobe,
        private readonly GoogleFontsClient $googleClient,
        private readonly GoogleImportService $googleImport
    ) {
        $this->renderer = new AdminPageRenderer($this->storage);
    }

    public function registerMenu(): void
    {
        add_menu_page(
            __('Etch Custom Fonts', 'etch-fonts'),
            __('Etch Fonts', 'etch-fonts'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-editor-textcolor',
            999
        );
    }

    public function enqueueAssets(string $hookSuffix): void
    {
        if (!self::isPluginAdminHook($hookSuffix)) {
            return;
        }

        $googleApiStatus = $this->googleClient->getApiKeyStatus();
        $googleSearchEnabled = $this->googleClient->canSearch();

        wp_enqueue_style(
            'etch-fonts-admin',
            ETCH_FONTS_URL . 'assets/css/admin.css',
            [],
            $this->assetVersionFor('assets/css/admin.css')
        );

        wp_enqueue_script(
            'etch-fonts-admin',
            ETCH_FONTS_URL . 'assets/js/admin.js',
            [],
            $this->assetVersionFor('assets/js/admin.js'),
            true
        );

        wp_localize_script(
            'etch-fonts-admin',
            'EtchFontsAdmin',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'searchNonce' => wp_create_nonce('etch_fonts_search_google'),
                'importNonce' => wp_create_nonce('etch_fonts_import_google'),
                'uploadNonce' => wp_create_nonce('etch_fonts_upload_local'),
                'saveFallbackNonce' => wp_create_nonce('etch_fonts_save_family_fallback'),
                'googleApiEnabled' => $googleSearchEnabled,
                'strings' => $this->buildAdminStrings($this->buildSearchDisabledMessage($googleApiStatus)),
            ]
        );
    }

    public function handleAdminActions(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if ($this->handleClearLogAction()) {
            return;
        }

        if ($this->handleRescanFontsAction()) {
            return;
        }

        if ($this->handleSaveSettingsAction()) {
            return;
        }

        if ($this->handleSaveAdobeProjectAction()) {
            return;
        }

        if ($this->handleSaveFamilyFallbackAction()) {
            return;
        }

        if ($this->handleDeleteFamilyAction()) {
            return;
        }

        $this->handleSaveRolesAction();
    }

    public function ajaxSearchGoogle(): void
    {
        $this->assertManageOptionsAjax(__('You are not allowed to search Google Fonts.', 'etch-fonts'));
        check_ajax_referer('etch_fonts_search_google', 'nonce');

        if (!$this->googleClient->canSearch()) {
            $this->sendAjaxError(__('Search is unavailable until a Google Fonts API key is saved.', 'etch-fonts'), 400);
        }

        wp_send_json_success(['items' => $this->googleClient->searchFamilies($this->getPostedText('query'), 20)]);
    }

    public function ajaxImportGoogle(): void
    {
        $this->assertManageOptionsAjax(__('You are not allowed to import Google Fonts.', 'etch-fonts'));
        check_ajax_referer('etch_fonts_import_google', 'nonce');

        $result = $this->googleImport->importFamily(
            $this->getPostedText('family'),
            $this->getPostedGoogleVariants()
        );

        if (is_wp_error($result)) {
            $this->sendAjaxError($result->get_error_message(), 400);
        }

        wp_send_json_success($result);
    }

    public function ajaxUploadLocal(): void
    {
        $this->assertManageOptionsAjax(__('You are not allowed to upload local fonts.', 'etch-fonts'));
        check_ajax_referer('etch_fonts_upload_local', 'nonce');

        $rows = $this->getPostedUploadRows();

        if ($rows === []) {
            $this->sendAjaxError(__('Add at least one upload row before submitting.', 'etch-fonts'), 400);
        }

        $result = $this->localUpload->uploadRows($rows);

        if (is_wp_error($result)) {
            $this->sendAjaxError($result->get_error_message(), 400);
        }

        wp_send_json_success($result);
    }

    public function ajaxSaveFamilyFallback(): void
    {
        $this->assertManageOptionsAjax(__('You are not allowed to update font fallback settings.', 'etch-fonts'));
        check_ajax_referer('etch_fonts_save_family_fallback', 'nonce');

        $family = $this->getPostedText('family');
        $fallback = $this->getPostedFallback('fallback');

        if ($family === '') {
            $this->sendAjaxError(__('A font family is required before saving its fallback.', 'etch-fonts'), 400);
        }

        $this->settings->saveFamilyFallback($family, $fallback);

        wp_send_json_success(
            [
                'family' => $family,
                'fallback' => $fallback,
                'stack' => FontUtils::buildFontStack($family, $fallback),
                'message' => sprintf(
                    __('Saved fallback for %s.', 'etch-fonts'),
                    $family
                ),
            ]
        );
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->renderer->renderPage($this->buildPageContext());
    }

    private function handleClearLogAction(): bool
    {
        if (!isset($_POST['etch_fonts_clear_log'])) {
            return false;
        }

        check_admin_referer('etch_fonts_clear_log');
        $this->log->clear();
        $this->redirect(['log_cleared' => '1']);
    }

    private function handleRescanFontsAction(): bool
    {
        if (!isset($_POST['etch_fonts_rescan_fonts'])) {
            return false;
        }

        check_admin_referer('etch_fonts_rescan_fonts');
        $this->assets->refreshGeneratedAssets();
        $this->log->add(__('Fonts rescanned.', 'etch-fonts'));
        $this->redirect(['rescan' => '1']);
    }

    private function handleSaveSettingsAction(): bool
    {
        if (!isset($_POST['etch_fonts_save_settings'])) {
            return false;
        }

        check_admin_referer('etch_fonts_save_settings');

        $submittedGoogleKey = $this->getPostedText('google_api_key');
        $clearGoogleKey = !empty($_POST['etch_fonts_clear_google_api_key']);

        $this->settings->saveSettings(
            [
                'google_api_key' => wp_unslash($_POST['google_api_key'] ?? ''),
                'etch_fonts_clear_google_api_key' => wp_unslash($_POST['etch_fonts_clear_google_api_key'] ?? ''),
                'css_delivery_mode' => wp_unslash($_POST['css_delivery_mode'] ?? ''),
                'font_display' => wp_unslash($_POST['font_display'] ?? ''),
                'minify_css_output' => $_POST['minify_css_output'] ?? '',
                'preview_sentence' => wp_unslash($_POST['preview_sentence'] ?? ''),
            ]
        );
        $this->googleClient->clearCatalogCache();

        if ($clearGoogleKey) {
            $this->settings->saveGoogleApiKeyStatus('empty');
            $this->log->add(__('Google Fonts API key removed.', 'etch-fonts'));
            $this->redirect(['google_key_cleared' => '1']);
        }

        if ($submittedGoogleKey !== '') {
            $validation = $this->googleClient->validateApiKey($submittedGoogleKey);

            $this->settings->saveGoogleApiKeyStatus(
                (string) ($validation['state'] ?? 'unknown'),
                (string) ($validation['message'] ?? '')
            );

            if (($validation['state'] ?? 'unknown') === 'valid') {
                $this->log->add(__('Google Fonts API key validated.', 'etch-fonts'));
                $this->redirect(['google_key_saved' => '1']);
            }

            $this->log->add(__('Google Fonts API key validation failed.', 'etch-fonts'));
            $this->redirect(
                [
                    'etch_fonts_error' => (string) (
                        $validation['message']
                        ?? __('Google Fonts API key could not be validated.', 'etch-fonts')
                    ),
                ]
            );
        }

        $this->log->add(__('Plugin settings updated.', 'etch-fonts'));
        $this->redirect(['settings_saved' => '1']);
    }

    private function handleSaveFamilyFallbackAction(): bool
    {
        if (!isset($_POST['etch_fonts_save_family_fallback'])) {
            return false;
        }

        check_admin_referer('etch_fonts_save_family_fallback');

        $family = $this->getPostedText('etch_fonts_family_name');
        $fallback = $this->getPostedFallback('etch_fonts_family_fallback');

        if ($family !== '') {
            $this->settings->saveFamilyFallback($family, $fallback);
            $this->log->add(
                sprintf(
                    __('Saved fallback for %1$s: %2$s.', 'etch-fonts'),
                    $family,
                    $fallback
                )
            );
        }

        $this->redirect(['fallback_saved' => '1']);
    }

    private function handleDeleteFamilyAction(): bool
    {
        if (!isset($_POST['etch_fonts_delete_family'])) {
            return false;
        }

        check_admin_referer('etch_fonts_delete_family');

        $result = $this->library->deleteFamily($this->getPostedText('etch_fonts_family_slug'));

        if (is_wp_error($result)) {
            $this->redirect(['etch_fonts_error' => $result->get_error_message()]);
        }

        $this->redirect(['family_deleted' => '1']);
    }

    private function handleSaveRolesAction(): void
    {
        if (!isset($_POST['etch_fonts_save_roles'])) {
            return;
        }

        check_admin_referer('etch_fonts_save_roles');

        $catalog = $this->catalog->getCatalog();
        $availableFamilies = $this->buildSelectableFamilyNames($catalog);
        $roles = $this->settings->saveRoles(
            [
                'heading' => $this->getPostedText('etch_fonts_heading_font'),
                'body' => $this->getPostedText('etch_fonts_body_font'),
                'heading_fallback' => $this->getPostedFallback('etch_fonts_heading_fallback'),
                'body_fallback' => $this->getPostedFallback('etch_fonts_body_fallback'),
            ],
            $availableFamilies
        );

        $actionType = $this->getPostedText('etch_fonts_action_type', 'save');
        $applyEverywhere = $actionType === 'apply';

        $this->settings->setAutoApplyRoles($applyEverywhere);
        $this->assets->refreshGeneratedAssets(false);

        $this->log->add(
            sprintf(
                __('Roles saved. Heading: %1$s; Body: %2$s. %3$s', 'etch-fonts'),
                $roles['heading'],
                $roles['body'],
                $applyEverywhere
                    ? __('Applied everywhere.', 'etch-fonts')
                    : __('Saved without global auto-apply.', 'etch-fonts')
            )
        );

        $this->redirect(['roles_saved' => '1']);
    }

    private function buildPageContext(): array
    {
        $storage = $this->storage->get();
        $settings = $this->settings->getSettings();
        $catalog = $this->catalog->getCatalog();
        $logs = $this->log->all();
        $counts = $this->catalog->getCounts();
        $assetStatus = $this->assets->getStatus();
        $familyFallbacks = is_array($settings['family_fallbacks'] ?? null) ? $settings['family_fallbacks'] : [];
        $visibleLogs = array_slice($logs, 0, 5);
        $olderLogs = array_slice($logs, 5);
        $applyEverywhere = !empty($settings['auto_apply_roles']);
        $previewContext = $this->buildPreviewContext($settings);
        $adobeAccessContext = $this->buildAdobeAccessContext();
        $availableFamilies = $this->buildSelectableFamilyNames($catalog);
        $roles = $this->settings->getRoles($availableFamilies);
        $googleAccessContext = $this->buildGoogleAccessContext();

        return [
            'storage' => $storage,
            'catalog' => $catalog,
            'available_families' => $availableFamilies,
            'roles' => $roles,
            'logs' => $logs,
            'visible_logs' => $visibleLogs,
            'older_logs' => $olderLogs,
            'family_fallbacks' => $familyFallbacks,
            'preview_text' => $previewContext['preview_text'],
            'preview_size' => $previewContext['preview_size'],
            'google_api_enabled' => $googleAccessContext['google_api_enabled'],
            'google_api_saved' => $googleAccessContext['google_api_saved'],
            'google_access_expanded' => $googleAccessContext['google_access_expanded'],
            'google_status_label' => $googleAccessContext['google_status_label'],
            'google_status_class' => $googleAccessContext['google_status_class'],
            'google_access_copy' => $googleAccessContext['google_access_copy'],
            'google_search_disabled_copy' => $googleAccessContext['google_search_disabled_copy'],
            'adobe_project_enabled' => $adobeAccessContext['adobe_project_enabled'],
            'adobe_project_saved' => $adobeAccessContext['adobe_project_saved'],
            'adobe_project_id' => $adobeAccessContext['adobe_project_id'],
            'adobe_status_label' => $adobeAccessContext['adobe_status_label'],
            'adobe_status_class' => $adobeAccessContext['adobe_status_class'],
            'adobe_access_copy' => $adobeAccessContext['adobe_access_copy'],
            'adobe_project_link' => $adobeAccessContext['adobe_project_link'],
            'adobe_detected_families' => $adobeAccessContext['adobe_detected_families'],
            'diagnostic_items' => $this->buildDiagnosticItems($assetStatus, $storage, $settings, $counts),
            'overview_metrics' => $this->buildOverviewMetrics($counts, $applyEverywhere),
            'output_panels' => $this->buildOutputPanels($roles),
            'preview_panels' => $this->buildPreviewPanels(),
            'toasts' => $this->buildNoticeToasts(),
        ];
    }

    private function buildDiagnosticItems(array $assetStatus, ?array $storage, array $settings, array $counts): array
    {
        $cssPath = (string) ($assetStatus['path'] ?? '');
        $cssUrl = (string) ($assetStatus['url'] ?? '');
        $cssExists = !empty($assetStatus['exists']) && $cssPath !== '' && file_exists($cssPath);

        return [
            [
                'label' => __('Generated CSS file', 'etch-fonts'),
                'value' => $cssPath !== '' ? $cssPath : __('Not available', 'etch-fonts'),
                'code' => true,
            ],
            [
                'label' => __('CSS request URL', 'etch-fonts'),
                'value' => $cssUrl !== '' ? $cssUrl : __('Not available', 'etch-fonts'),
                'code' => true,
            ],
            [
                'label' => __('Stylesheet size', 'etch-fonts'),
                'value' => $cssExists ? size_format((int) ($assetStatus['size'] ?? 0)) : __('Not generated', 'etch-fonts'),
                'code' => false,
            ],
            [
                'label' => __('Last generated', 'etch-fonts'),
                'value' => $cssExists
                    ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) filemtime($cssPath))
                    : __('Not available', 'etch-fonts'),
                'code' => false,
            ],
            [
                'label' => __('Fonts directory', 'etch-fonts'),
                'value' => is_array($storage) ? (string) ($storage['dir'] ?? __('Not available', 'etch-fonts')) : __('Not available', 'etch-fonts'),
                'code' => true,
            ],
            [
                'label' => __('Fonts public URL', 'etch-fonts'),
                'value' => is_array($storage)
                    ? (string) ($storage['url_full'] ?? $storage['url'] ?? __('Not available', 'etch-fonts'))
                    : __('Not available', 'etch-fonts'),
                'code' => true,
            ],
            [
                'label' => __('Google import folder', 'etch-fonts'),
                'value' => is_array($storage) ? (string) ($storage['google_dir'] ?? __('Not available', 'etch-fonts')) : __('Not available', 'etch-fonts'),
                'code' => true,
            ],
            [
                'label' => __('Library inventory', 'etch-fonts'),
                'value' => sprintf(
                    __('%1$d families / %2$d files', 'etch-fonts'),
                    (int) ($counts['families'] ?? 0),
                    (int) ($counts['files'] ?? 0)
                ),
                'code' => false,
            ],
            [
                'label' => __('Delivery mode', 'etch-fonts'),
                'value' => (string) ($settings['css_delivery_mode'] ?? 'file'),
                'code' => false,
            ],
            [
                'label' => __('Font display', 'etch-fonts'),
                'value' => (string) ($settings['font_display'] ?? 'swap'),
                'code' => false,
            ],
        ];
    }

    private function buildOverviewMetrics(array $counts, bool $applyEverywhere): array
    {
        return [
            [
                'label' => __('Font families', 'etch-fonts'),
                'value' => (string) ($counts['families'] ?? 0),
            ],
            [
                'label' => __('Font files', 'etch-fonts'),
                'value' => (string) ($counts['files'] ?? 0),
            ],
            [
                'label' => __('Google families', 'etch-fonts'),
                'value' => (string) ($counts['google_families'] ?? 0),
            ],
            [
                'label' => __('Apply everywhere', 'etch-fonts'),
                'value' => $applyEverywhere ? __('On', 'etch-fonts') : __('Off', 'etch-fonts'),
            ],
        ];
    }

    private function buildOutputPanels(array $roles): array
    {
        return [
            [
                'key' => 'usage',
                'label' => __('Site snippet', 'etch-fonts'),
                'target' => 'etch-fonts-output-usage',
                'value' => $this->cssBuilder->buildRoleUsageSnippet($roles),
                'active' => true,
            ],
            [
                'key' => 'variables',
                'label' => __('CSS variables', 'etch-fonts'),
                'target' => 'etch-fonts-output-vars',
                'value' => $this->cssBuilder->buildRoleVariableSnippet($roles),
                'active' => false,
            ],
            [
                'key' => 'stacks',
                'label' => __('Font stacks', 'etch-fonts'),
                'target' => 'etch-fonts-output-stacks',
                'value' => $this->cssBuilder->buildRoleStackSnippet($roles),
                'active' => false,
            ],
            [
                'key' => 'names',
                'label' => __('Font names', 'etch-fonts'),
                'target' => 'etch-fonts-output-names',
                'value' => $this->cssBuilder->buildRoleNameSnippet($roles),
                'active' => false,
            ],
        ];
    }

    private function buildPreviewPanels(): array
    {
        return [
            [
                'key' => 'editorial',
                'label' => __('Specimen', 'etch-fonts'),
                'active' => true,
            ],
            [
                'key' => 'card',
                'label' => __('Card', 'etch-fonts'),
                'active' => false,
            ],
            [
                'key' => 'reading',
                'label' => __('Reading', 'etch-fonts'),
                'active' => false,
            ],
            [
                'key' => 'interface',
                'label' => __('Interface', 'etch-fonts'),
                'active' => false,
            ],
        ];
    }

    private function buildNoticeToasts(): array
    {
        $noticeMap = [
            'settings_saved' => __('Plugin settings saved.', 'etch-fonts'),
            'adobe_project_saved' => __('Adobe Fonts project saved.', 'etch-fonts'),
            'adobe_project_removed' => __('Adobe Fonts project removed.', 'etch-fonts'),
            'adobe_project_resynced' => __('Adobe Fonts project resynced.', 'etch-fonts'),
            'google_key_saved' => __('Google Fonts API key saved and validated.', 'etch-fonts'),
            'google_key_cleared' => __('Google Fonts API key removed.', 'etch-fonts'),
            'fallback_saved' => __('Font fallback saved.', 'etch-fonts'),
            'roles_saved' => __('Font roles saved.', 'etch-fonts'),
            'rescan' => __('Fonts rescanned.', 'etch-fonts'),
            'log_cleared' => __('Activity log cleared.', 'etch-fonts'),
            'family_deleted' => __('Font family deleted.', 'etch-fonts'),
        ];
        $toasts = [];

        foreach ($noticeMap as $key => $message) {
            if (!isset($_GET[$key]) || $_GET[$key] !== '1') {
                continue;
            }

            $toasts[] = [
                'tone' => 'success',
                'message' => $message,
                'role' => 'status',
            ];
        }

        if (!empty($_GET['etch_fonts_error'])) {
            $toasts[] = [
                'tone' => 'error',
                'message' => sanitize_text_field(wp_unslash((string) $_GET['etch_fonts_error'])),
                'role' => 'alert',
            ];
        }

        return $toasts;
    }

    private function assetVersionFor(string $relativePath): string
    {
        $path = ETCH_FONTS_DIR . ltrim($relativePath, '/');

        return file_exists($path) ? (string) filemtime($path) : ETCH_FONTS_VERSION;
    }

    private function buildAdminStrings(string $searchDisabledMessage): array
    {
        return [
            'searching' => __('Searching Google Fonts…', 'etch-fonts'),
            'searchEmpty' => __('No Google Fonts families matched that search.', 'etch-fonts'),
            'searchDisabled' => $searchDisabledMessage,
            'selectFamily' => __('Select a family from search results or type one manually.', 'etch-fonts'),
            'importing' => __('Importing and self-hosting selected files…', 'etch-fonts'),
            'importSuccess' => __('Font imported successfully. Reloading…', 'etch-fonts'),
            'importError' => __('The Google Fonts import failed.', 'etch-fonts'),
            'importProgress' => __('Importing %1$s: %2$d of %3$d (%4$s)…', 'etch-fonts'),
            'importSummary' => __('Imported %1$d variant%2$s. %3$d skipped. Reloading…', 'etch-fonts'),
            'importAlreadyExists' => __('%s already exists in the library for the selected variants.', 'etch-fonts'),
            'importButtonIdle' => __('Import and self-host', 'etch-fonts'),
            'importButtonBusy' => __('Importing…', 'etch-fonts'),
            'uploadReady' => __('Upload WOFF2, WOFF, TTF, or OTF files. Each row imports one face.', 'etch-fonts'),
            'uploadSubmitting' => __('Uploading font files…', 'etch-fonts'),
            'uploadProgress' => __('Uploading files… %1$d%%', 'etch-fonts'),
            'uploadSuccess' => __('Upload complete. Refreshing the library…', 'etch-fonts'),
            'uploadError' => __('The font upload failed.', 'etch-fonts'),
            'uploadNoFile' => __('No file chosen', 'etch-fonts'),
            'uploadButtonIdle' => __('Upload to library', 'etch-fonts'),
            'uploadButtonBusy' => __('Uploading…', 'etch-fonts'),
            'uploadRowQueued' => __('Queued', 'etch-fonts'),
            'uploadRowUploading' => __('Uploading…', 'etch-fonts'),
            'uploadRowImported' => __('Imported', 'etch-fonts'),
            'uploadRowSkipped' => __('Skipped', 'etch-fonts'),
            'uploadRowError' => __('Error', 'etch-fonts'),
            'uploadAddFace' => __('Add face', 'etch-fonts'),
            'uploadAddFamily' => __('Add another family', 'etch-fonts'),
            'uploadUseDetected' => __('Use detected values', 'etch-fonts'),
            'uploadDetectedSummary' => __('Detected: %1$s / %2$s / %3$s', 'etch-fonts'),
            'uploadDetectedWeightStyle' => __('Detected: %1$s / %2$s', 'etch-fonts'),
            'uploadRemoveRow' => __('Remove row', 'etch-fonts'),
            'uploadRequiresRows' => __('Add at least one upload row before submitting.', 'etch-fonts'),
            'deleteConfirm' => __('Delete "%s" and remove its files from uploads/fonts?', 'etch-fonts'),
            'fallbackSaved' => __('Saved fallback for %1$s.', 'etch-fonts'),
            'fallbackSaveError' => __('The fallback could not be saved.', 'etch-fonts'),
            'copied' => __('Copied', 'etch-fonts'),
            'copy' => __('Copy', 'etch-fonts'),
        ];
    }

    private function buildSearchDisabledMessage(array $googleApiStatus): string
    {
        return match ((string) ($googleApiStatus['state'] ?? 'empty')) {
            'invalid' => __('Search is disabled because the saved Google Fonts API key is invalid.', 'etch-fonts'),
            'unknown' => __('Search is unavailable until the saved Google Fonts API key is validated.', 'etch-fonts'),
            default => __('Add a Google Fonts API key to enable search, or use manual import below.', 'etch-fonts'),
        };
    }

    private function buildPreviewContext(array $settings): array
    {
        $previewText = isset($_GET['preview_text'])
            ? wp_strip_all_tags(sanitize_text_field(wp_unslash((string) $_GET['preview_text'])))
            : (string) ($settings['preview_sentence'] ?? '');
        $previewSize = isset($_GET['preview_size']) ? absint($_GET['preview_size']) : 32;

        return [
            'preview_text' => $previewText,
            'preview_size' => $previewSize > 0 ? $previewSize : 32,
        ];
    }

    private function buildAdobeAccessContext(): array
    {
        $projectId = $this->adobe->getProjectId();
        $projectSaved = $projectId !== '';
        $projectEnabled = $this->adobe->isEnabled();
        $projectStatus = $this->adobe->getProjectStatus();
        $projectState = (string) ($projectStatus['state'] ?? 'empty');
        $projectStatusMessage = (string) ($projectStatus['message'] ?? '');
        $detectedFamilies = $projectSaved && in_array($projectState, ['valid', 'unknown'], true)
            ? $this->adobe->getProjectFamilies($projectId)
            : [];

        return [
            'adobe_project_enabled' => $projectEnabled,
            'adobe_project_saved' => $projectSaved,
            'adobe_project_id' => $projectId,
            'adobe_status_label' => $this->buildAdobeStatusLabel($projectState, $projectEnabled),
            'adobe_status_class' => $this->buildAdobeStatusClass($projectState, $projectEnabled),
            'adobe_access_copy' => $this->buildAdobeAccessCopy($projectState, $projectEnabled, $projectStatusMessage),
            'adobe_project_link' => 'https://fonts.adobe.com/',
            'adobe_detected_families' => $detectedFamilies,
        ];
    }

    private function buildGoogleAccessContext(): array
    {
        $googleApiEnabled = $this->googleClient->canSearch();
        $googleApiSaved = $this->googleClient->hasApiKey();
        $googleApiStatus = $this->googleClient->getApiKeyStatus();
        $googleApiState = (string) ($googleApiStatus['state'] ?? 'empty');
        $googleApiStatusMessage = (string) ($googleApiStatus['message'] ?? '');

        return [
            'google_api_enabled' => $googleApiEnabled,
            'google_api_saved' => $googleApiSaved,
            'google_access_expanded' => !$googleApiEnabled,
            'google_status_label' => $this->buildGoogleStatusLabel($googleApiState),
            'google_status_class' => $this->buildGoogleStatusClass($googleApiState),
            'google_access_copy' => $this->buildGoogleAccessCopy($googleApiState, $googleApiStatusMessage),
            'google_search_disabled_copy' => $this->buildGoogleSearchDisabledCopy($googleApiState),
        ];
    }

    private function buildGoogleStatusLabel(string $googleApiState): string
    {
        return match ($googleApiState) {
            'valid' => __('Valid key', 'etch-fonts'),
            'invalid' => __('Invalid key', 'etch-fonts'),
            'unknown' => __('Needs check', 'etch-fonts'),
            default => __('API key needed', 'etch-fonts'),
        };
    }

    private function buildGoogleStatusClass(string $googleApiState): string
    {
        return match ($googleApiState) {
            'valid' => 'is-success',
            'invalid' => 'is-danger',
            'unknown' => 'is-warning',
            default => '',
        };
    }

    private function buildGoogleAccessCopy(string $googleApiState, string $googleApiStatusMessage): string
    {
        if ($googleApiStatusMessage !== '') {
            return $googleApiStatusMessage;
        }

        return match ($googleApiState) {
            'valid' => __('Google search is ready. Open key settings only when you want to replace or remove the saved key.', 'etch-fonts'),
            'invalid' => __('The saved Google Fonts API key was rejected. Update it to re-enable live search.', 'etch-fonts'),
            'unknown' => __('This saved key has not been verified yet. Save it again to validate before using live search.', 'etch-fonts'),
            default => __('Enable live family search with a Google Fonts Developer API key. Imported files are still downloaded and stored locally after import.', 'etch-fonts'),
        };
    }

    private function buildGoogleSearchDisabledCopy(string $googleApiState): string
    {
        return match ($googleApiState) {
            'invalid' => __('Search is disabled because the saved API key is invalid. Update or remove it to continue.', 'etch-fonts'),
            'unknown' => __('Search is disabled until the saved API key has been validated.', 'etch-fonts'),
            default => __('Search is disabled until you save a Google Fonts API key.', 'etch-fonts'),
        };
    }

    private function buildAdobeStatusLabel(string $projectState, bool $projectEnabled): string
    {
        if (!$projectEnabled && $projectState === 'valid') {
            return __('Saved project', 'etch-fonts');
        }

        return match ($projectState) {
            'valid' => __('Project ready', 'etch-fonts'),
            'invalid' => __('Invalid project', 'etch-fonts'),
            'unknown' => __('Needs check', 'etch-fonts'),
            default => __('Project ID needed', 'etch-fonts'),
        };
    }

    private function buildAdobeStatusClass(string $projectState, bool $projectEnabled): string
    {
        if (!$projectEnabled && $projectState === 'valid') {
            return 'is-warning';
        }

        return match ($projectState) {
            'valid' => 'is-success',
            'invalid' => 'is-danger',
            'unknown' => 'is-warning',
            default => '',
        };
    }

    private function buildAdobeAccessCopy(string $projectState, bool $projectEnabled, string $projectStatusMessage): string
    {
        if ($projectStatusMessage !== '') {
            if (!$projectEnabled && $projectState === 'valid') {
                return __('The Adobe project is saved and validated but currently disabled. Enable it to load Adobe-hosted fonts on the site and in editors.', 'etch-fonts');
            }

            return $projectStatusMessage;
        }

        return match ($projectState) {
            'valid' => $projectEnabled
                ? __('This Adobe Fonts project will load remotely from use.typekit.net. Manage families and domains in Adobe Fonts, then resync here when needed.', 'etch-fonts')
                : __('The Adobe project is saved, but remote loading is disabled until you enable it below.', 'etch-fonts'),
            'invalid' => __('Adobe rejected the saved web project ID. Check the project ID and allowed domains in Adobe Fonts before saving again.', 'etch-fonts'),
            'unknown' => __('This Adobe project has not been validated yet. Save or resync it to fetch the project stylesheet and detected families.', 'etch-fonts'),
            default => __('Connect an existing Adobe Fonts web project to make its hosted families available for previews, roles, Gutenberg, and the Etch canvas.', 'etch-fonts'),
        };
    }

    private function buildSelectableFamilyNames(array $catalog): array
    {
        $families = array_keys($catalog);

        foreach ($this->adobe->getConfiguredFamilies() as $family) {
            $familyName = trim((string) ($family['family'] ?? ''));

            if ($familyName === '') {
                continue;
            }

            $families[] = $familyName;
        }

        $storedRoles = $this->settings->getRoles([]);

        foreach (['heading', 'body'] as $roleKey) {
            $familyName = trim((string) ($storedRoles[$roleKey] ?? ''));

            if ($familyName !== '') {
                $families[] = $familyName;
            }
        }

        $families = array_values(array_unique($families));
        natcasesort($families);

        return array_values($families);
    }

    private function handleSaveAdobeProjectAction(): bool
    {
        if (
            !isset($_POST['etch_fonts_save_adobe_project'])
            && !isset($_POST['etch_fonts_remove_adobe_project'])
            && !isset($_POST['etch_fonts_resync_adobe_project'])
        ) {
            return false;
        }

        check_admin_referer('etch_fonts_save_adobe_project');

        if (isset($_POST['etch_fonts_remove_adobe_project'])) {
            $existingProjectId = $this->settings->getAdobeProjectId();

            $this->settings->clearAdobeProject();
            $this->adobe->clearProjectCache($existingProjectId);
            $this->log->add(__('Adobe Fonts project removed.', 'etch-fonts'));
            $this->redirect(['adobe_project_removed' => '1']);
        }

        $projectId = isset($_POST['etch_fonts_resync_adobe_project'])
            ? $this->settings->getAdobeProjectId()
            : $this->getPostedText('adobe_project_id');
        $enabled = isset($_POST['etch_fonts_resync_adobe_project'])
            ? $this->settings->isAdobeEnabled()
            : !empty($_POST['adobe_enabled']);

        $existingProjectId = $this->settings->getAdobeProjectId();

        if (!isset($_POST['etch_fonts_resync_adobe_project'])) {
            $this->settings->saveAdobeProject($projectId, $enabled);
        }

        if ($projectId === '') {
            $this->adobe->clearProjectCache($existingProjectId);
            $this->log->add(__('Adobe Fonts project cleared.', 'etch-fonts'));
            $this->redirect(['adobe_project_removed' => '1']);
        }

        $this->adobe->clearProjectCache($projectId);
        $validation = $this->adobe->validateProject($projectId);

        $this->settings->saveAdobeProjectStatus(
            (string) ($validation['state'] ?? 'unknown'),
            (string) ($validation['message'] ?? '')
        );

        if (($validation['state'] ?? 'unknown') !== 'valid') {
            $this->log->add(__('Adobe Fonts project validation failed.', 'etch-fonts'));
            $this->redirect(
                [
                    'etch_fonts_error' => (string) (
                        $validation['message']
                        ?? __('Adobe Fonts project could not be validated.', 'etch-fonts')
                    ),
                ]
            );
        }

        $this->log->add(
            isset($_POST['etch_fonts_resync_adobe_project'])
                ? __('Adobe Fonts project resynced.', 'etch-fonts')
                : __('Adobe Fonts project saved.', 'etch-fonts')
        );
        $this->redirect(
            [
                isset($_POST['etch_fonts_resync_adobe_project']) ? 'adobe_project_resynced' : 'adobe_project_saved' => '1',
            ]
        );
    }

    private function assertManageOptionsAjax(string $message): void
    {
        if (!current_user_can('manage_options')) {
            $this->sendAjaxError($message, 403);
        }
    }

    private function getPostedText(string $key, string $default = ''): string
    {
        if (!isset($_POST[$key])) {
            return $default;
        }

        return sanitize_text_field(wp_unslash((string) $_POST[$key]));
    }

    private function getPostedFallback(string $key, string $default = 'sans-serif'): string
    {
        return FontUtils::sanitizeFallback($this->getPostedText($key, $default));
    }

    private function getPostedGoogleVariants(): array
    {
        $variants = isset($_POST['variants'])
            ? array_map('sanitize_text_field', (array) wp_unslash($_POST['variants']))
            : [];

        if ($variants !== []) {
            return $variants;
        }

        $tokens = $this->getPostedText('variant_tokens');

        if ($tokens === '') {
            return [];
        }

        return array_map('trim', explode(',', $tokens));
    }

    private function getPostedUploadRows(): array
    {
        $postedRows = isset($_POST['rows']) && is_array($_POST['rows'])
            ? wp_unslash($_POST['rows'])
            : [];
        $uploadedFiles = $this->normalizeUploadedFiles($_FILES['files'] ?? []);
        $rows = [];

        foreach ($postedRows as $index => $row) {
            $rows[] = [
                'family' => sanitize_text_field((string) ($row['family'] ?? '')),
                'weight' => sanitize_text_field((string) ($row['weight'] ?? '400')),
                'style' => sanitize_text_field((string) ($row['style'] ?? 'normal')),
                'fallback' => sanitize_text_field((string) ($row['fallback'] ?? 'sans-serif')),
                'file' => $uploadedFiles[$index] ?? [],
            ];
        }

        return $rows;
    }

    private function normalizeUploadedFiles(mixed $rawFiles): array
    {
        if (
            !is_array($rawFiles)
            || !isset($rawFiles['name'], $rawFiles['tmp_name'], $rawFiles['error'], $rawFiles['size'])
            || !is_array($rawFiles['name'])
        ) {
            return [];
        }

        $normalized = [];

        foreach ($rawFiles['name'] as $index => $name) {
            $normalized[$index] = [
                'name' => is_string($name) ? $name : '',
                'type' => is_array($rawFiles['type'] ?? null) ? (string) ($rawFiles['type'][$index] ?? '') : '',
                'tmp_name' => is_array($rawFiles['tmp_name'] ?? null) ? (string) ($rawFiles['tmp_name'][$index] ?? '') : '',
                'error' => is_array($rawFiles['error'] ?? null) ? (int) ($rawFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
                'size' => is_array($rawFiles['size'] ?? null) ? (int) ($rawFiles['size'][$index] ?? 0) : 0,
            ];
        }

        return $normalized;
    }

    private function sendAjaxError(string $message, int $status): never
    {
        wp_send_json_error(['message' => $message], $status);
    }

    private function redirect(array $queryArgs): never
    {
        wp_safe_redirect(
            add_query_arg(
                array_merge(['page' => self::MENU_SLUG], $queryArgs),
                admin_url('admin.php')
            )
        );
        exit;
    }

    public static function isPluginAdminHook(string $hookSuffix): bool
    {
        return $hookSuffix === 'toplevel_page_' . self::MENU_SLUG;
    }
}
