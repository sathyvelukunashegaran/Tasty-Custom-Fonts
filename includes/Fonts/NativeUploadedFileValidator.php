<?php

declare(strict_types=1);

namespace TastyFonts\Fonts;

defined('ABSPATH') || exit;

if (!function_exists(__NAMESPACE__ . '\\tasty_fonts_native_is_uploaded_file')) {
    function tasty_fonts_native_is_uploaded_file(string $tmpName): bool
    {
        return \is_uploaded_file($tmpName);
    }
}

final class NativeUploadedFileValidator implements UploadedFileValidatorInterface
{
    /**
     * Determine whether a file path belongs to a valid HTTP upload.
     *
     * @since 1.4.0
     *
     * @param string $tmpName Temporary file path provided by WordPress.
     * @return bool True when PHP recognizes the file as an uploaded file.
     */
    public function isUploadedFile(string $tmpName): bool
    {
        return tasty_fonts_native_is_uploaded_file($tmpName);
    }
}
