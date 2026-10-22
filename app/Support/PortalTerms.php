<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Terms of use of the patient portal (US-6.3, D-5): a Markdown file kept outside the code
 * (config portal.terms_path). Any change to the text is a new version.
 */
class PortalTerms
{
    public static function available(): bool
    {
        return self::text() !== null;
    }

    /**
     * "sha256:" and the first 12 characters of the hash of the text, or null without it.
     */
    public static function version(): ?string
    {
        $text = self::text();

        return $text === null ? null : 'sha256:'.substr(hash('sha256', $text), 0, 12);
    }

    public static function html(): ?string
    {
        $text = self::text();

        return $text === null ? null : Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    private static function text(): ?string
    {
        $path = config('portal.terms_path');
        $text = $path && is_file($path) ? trim((string) file_get_contents($path)) : '';

        return $text === '' ? null : $text;
    }
}
