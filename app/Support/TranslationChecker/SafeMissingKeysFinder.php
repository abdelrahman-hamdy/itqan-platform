<?php

namespace App\Support\TranslationChecker;

use Bottelet\TranslationChecker\Extractor\ExtractorFactory;
use Bottelet\TranslationChecker\Dto\TranslationCollection;
use Bottelet\TranslationChecker\Dto\TranslationItem;
use Bottelet\TranslationChecker\Extractor\RegexExtractor;
use Bottelet\TranslationChecker\Finder\MissingKeysFinder;
use Bottelet\TranslationChecker\Finder\PersistentKeysManager;
use Illuminate\Support\Facades\Log;
use SplFileInfo;
use Throwable;

/**
 * Safe MissingKeysFinder that catches parsing errors and falls back to regex extraction.
 */
class SafeMissingKeysFinder extends MissingKeysFinder
{
    /**
     * Finds translatable strings in a set of files.
     * Falls back to RegexExtractor if PHP parsing fails.
     *
     * @param  array<int, SplFileInfo>  $files
     */
    public function findTranslatableStrings(array $files): TranslationCollection
    {
        $translationList = new TranslationCollection;
        $regexExtractor = app(RegexExtractor::class);

        foreach ($files as $file) {
            if ($file->isFile()) {
                try {
                    // Always use regex for blade files to avoid JavaScript parsing issues
                    if (str_ends_with($file->getFilename(), '.blade.php')) {
                        $translationKeys = $regexExtractor->extractFromFile($file);
                    } else {
                        $extractor = ExtractorFactory::createExtractorForFile($file);
                        $translationKeys = $extractor->extractFromFile($file);
                    }
                } catch (Throwable $e) {
                    // Fall back to regex extraction on error
                    Log::warning("Translation check: Error parsing {$file->getPathname()}, falling back to regex: {$e->getMessage()}");
                    $translationKeys = $regexExtractor->extractFromFile($file);
                }

                foreach ($translationKeys as $key) {
                    $translationList->addTranslation(new TranslationItem($key, $file->getPathname()));
                }
            }
        }

        $persistentKeys = (new PersistentKeysManager)->getKeys();

        foreach ($persistentKeys as $key) {
            $translationList->addTranslation(new TranslationItem($key, config_path('translator')));
        }

        return $translationList;
    }
}
