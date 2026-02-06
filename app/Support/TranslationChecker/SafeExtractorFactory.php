<?php

namespace App\Support\TranslationChecker;

use Bottelet\TranslationChecker\Extractor\ExtractorContract;
use Bottelet\TranslationChecker\Extractor\PhpClassExtractor;
use Bottelet\TranslationChecker\Extractor\RegexExtractor;
use SplFileInfo;

/**
 * Custom ExtractorFactory that uses RegexExtractor for blade files
 * to avoid PHP parsing issues with inline JavaScript.
 */
class SafeExtractorFactory
{
    public static function createExtractorForFile(SplFileInfo $file): ExtractorContract
    {
        // Use RegexExtractor for blade files to avoid PHP parsing issues
        // with inline JavaScript that uses template literals or other non-PHP syntax
        if (str_ends_with($file->getFilename(), '.blade.php')) {
            return app(RegexExtractor::class);
        }

        if ($file->getExtension() === 'php') {
            return new PhpClassExtractor;
        }

        return app(RegexExtractor::class);
    }
}
