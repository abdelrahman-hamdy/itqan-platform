<?php

namespace App\Support\TranslationChecker;

use Bottelet\TranslationChecker\Exception\TranslationServiceException;
use Bottelet\TranslationChecker\File\FileManagement;
use Bottelet\TranslationChecker\File\Language\LanguageFileManagerFactory;
use Bottelet\TranslationChecker\Finder\TranslationFinder;
use Bottelet\TranslationChecker\TranslationManager;

/**
 * Custom TranslationManager that uses SafeMissingKeysFinder for error-tolerant extraction.
 */
class SafeTranslationManager extends TranslationManager
{
    /**
     * @param  array<string>  $sourceFilePaths
     * @return array<string, string|null>
     */
    public function updateTranslationsFromFile(array $sourceFilePaths, string $targetJsonPath, bool $sort = false, ?string $targetLanguage = null, bool $translateMissing = false, string $sourceLanguage = 'en'): array
    {
        // Use our SafeMissingKeysFinder instead of the default one
        $translationFinder = new TranslationFinder(
            new FileManagement,
            new LanguageFileManagerFactory($targetJsonPath),
            new SafeMissingKeysFinder
        );

        $missingTranslations = $translationFinder->findMissingTranslations($sourceFilePaths)->getKeys();

        if ($translateMissing && $targetLanguage !== null) {
            if (! $this->translationService->isConfigured()) {
                throw TranslationServiceException::notConfigured(get_class($this->translationService));
            }

            $missingTranslations = $this->translationService->translateBatch(array_keys($missingTranslations), $targetLanguage, $sourceLanguage);
        }

        /** @var array<string, string> $allTranslations */
        $allTranslations = array_merge($translationFinder->getLanguageFileManager()->readFile(), $missingTranslations);

        if ($sort) {
            $allTranslations = $this->sorter->sortByKey($allTranslations);
        }

        $translationFinder->getLanguageFileManager()->updateFile($allTranslations);

        return $missingTranslations;
    }
}
