<?php

declare(strict_types=1);

namespace TastyFonts\Fonts {
    $GLOBALS['nativeUploadedFileValidatorChecks'] = [];
    $GLOBALS['nativeUploadedFileValidatorResults'] = [];

    if (!function_exists(__NAMESPACE__ . '\\tasty_fonts_native_is_uploaded_file')) {
        function tasty_fonts_native_is_uploaded_file(string $tmpName): bool
        {
            $GLOBALS['nativeUploadedFileValidatorChecks'][] = $tmpName;

            return (bool) ($GLOBALS['nativeUploadedFileValidatorResults'][$tmpName] ?? false);
        }
    }
}

namespace {
    use TastyFonts\Fonts\HostedCssParser;
    use TastyFonts\Fonts\NativeUploadedFileValidator;
    use TastyFonts\Support\SiteEnvironment;

    $tests['hosted_css_parser_filters_by_family_and_preserves_unicode_range'] = static function (): void {
        $parser = new HostedCssParser('google');
        $faces = $parser->parse(
            <<<'CSS'
            @font-face {
                font-family: 'Inter';
                font-style: italic;
                font-weight: 700;
                src: url(https://fonts.example/inter-700italic.woff2) format('woff2'),
                    url(https://fonts.example/inter-700italic.woff) format('woff');
                unicode-range: U+0100-024F;
            }
            @font-face {
                font-family: 'Roboto';
                font-style: normal;
                font-weight: 400;
                src: url(https://fonts.example/roboto-400.woff2) format('woff2');
            }
            CSS,
            'Inter'
        );

        assertSameValue(1, count($faces), 'Hosted CSS parsing should keep only font-face blocks for the requested family.');
        assertArrayHasKeys(['family', 'slug', 'source', 'weight', 'style', 'unicode_range', 'files', 'provider'], $faces[0], 'Hosted CSS parsing should build the expected face payload shape.');
        assertSameValue('Inter', $faces[0]['family'], 'Hosted CSS parsing should preserve the matching family name.');
        assertSameValue('700', $faces[0]['weight'], 'Hosted CSS parsing should normalize weights through FontUtils.');
        assertSameValue('italic', $faces[0]['style'], 'Hosted CSS parsing should preserve font style values.');
        assertSameValue('U+0100-024F', $faces[0]['unicode_range'], 'Hosted CSS parsing should retain unicode-range declarations.');
        assertSameValue('https://fonts.example/inter-700italic.woff2', $faces[0]['files']['woff2'] ?? '', 'Hosted CSS parsing should keep the WOFF2 source URL.');
    };

    $tests['hosted_css_parser_rejects_non_woff2_faces_and_uses_fallback_urls'] = static function (): void {
        $parser = new HostedCssParser('bunny');

        $fallbackFaces = $parser->parse(
            <<<'CSS'
            @font-face {
                font-family: "Satoshi";
                font-style: normal;
                font-weight: 400;
                src: local("Satoshi"), url("https://fonts.example/satoshi-400.woff2?v=1");
            }
            CSS
        );

        assertSameValue(1, count($fallbackFaces), 'Hosted CSS parsing should recover a bare WOFF2 URL when the format() marker is missing.');
        assertSameValue('https://fonts.example/satoshi-400.woff2?v=1', $fallbackFaces[0]['files']['woff2'] ?? '', 'Hosted CSS parsing should extract fallback WOFF2 URLs verbatim.');

        $rejectedFaces = $parser->parse(
            <<<'CSS'
            @font-face {
                font-family: "Satoshi";
                font-style: normal;
                font-weight: 400;
                src: url("https://fonts.example/satoshi-400.woff") format("woff");
            }
            CSS
        );

        assertSameValue([], $rejectedFaces, 'Hosted CSS parsing should ignore font-face blocks that do not expose any WOFF2 source.');
    };

    $tests['site_environment_detects_local_hosts_from_suffixes_and_private_ranges'] = static function (): void {
        $localCases = [
            ['url' => 'https://studio.local', 'environment' => ''],
            ['url' => 'https://preview.ddev.site', 'environment' => ''],
            ['url' => 'http://127.0.0.1:8080', 'environment' => ''],
            ['url' => 'https://192.168.10.25', 'environment' => ''],
            ['url' => 'https://172.20.10.5', 'environment' => ''],
            ['url' => 'https://example.com', 'environment' => 'local'],
        ];

        foreach ($localCases as $case) {
            assertTrueValue(
                SiteEnvironment::isLikelyLocalEnvironment($case['url'], $case['environment']),
                'Site environment detection should treat local suffixes, loopback/private IPs, and explicit local environment types as local.'
            );
        }
    };

    $tests['site_environment_rejects_public_hosts_and_detects_tls_trust_markers'] = static function (): void {
        foreach (['https://example.com', 'https://8.8.8.8', ''] as $url) {
            assertFalseValue(
                SiteEnvironment::isLikelyLocalEnvironment($url, ''),
                'Site environment detection should not classify public hosts or empty URLs as local.'
            );
        }

        assertTrueValue(
            SiteEnvironment::isLoopbackTlsTrustError('cURL error 60: SSL certificate problem: self signed certificate'),
            'TLS trust detection should match cURL trust-failure markers case-insensitively.'
        );
        assertTrueValue(
            SiteEnvironment::isLoopbackTlsTrustError('Unable to verify the first certificate'),
            'TLS trust detection should recognize alternate certificate verification markers.'
        );
        assertFalseValue(
            SiteEnvironment::isLoopbackTlsTrustError('Connection timed out.'),
            'TLS trust detection should not treat unrelated transport failures as certificate trust errors.'
        );
    };

    $tests['native_uploaded_file_validator_uses_the_namespace_level_upload_seam'] = static function (): void {
        $GLOBALS['nativeUploadedFileValidatorChecks'] = [];
        $GLOBALS['nativeUploadedFileValidatorResults'] = [
            '/tmp/valid-font.woff2' => true,
            '/tmp/invalid-font.woff2' => false,
        ];

        $validator = new NativeUploadedFileValidator();

        assertTrueValue(
            $validator->isUploadedFile('/tmp/valid-font.woff2'),
            'Native uploaded-file validation should delegate to the namespace-level seam for positive cases.'
        );
        assertFalseValue(
            $validator->isUploadedFile('/tmp/invalid-font.woff2'),
            'Native uploaded-file validation should delegate to the namespace-level seam for negative cases.'
        );
        assertSameValue(
            ['/tmp/valid-font.woff2', '/tmp/invalid-font.woff2'],
            $GLOBALS['nativeUploadedFileValidatorChecks'],
            'Native uploaded-file validation should forward the exact temporary paths into the upload seam.'
        );
    };
}
