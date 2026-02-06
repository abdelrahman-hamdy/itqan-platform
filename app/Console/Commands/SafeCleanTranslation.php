<?php

namespace App\Console\Commands;

use App\Support\TranslationChecker\SafeMissingKeysFinder;
use Bottelet\TranslationChecker\File\FileManagement;
use Bottelet\TranslationChecker\File\Language\LanguageFileManagerFactory;
use Bottelet\TranslationChecker\Finder\TranslationFinder;
use Illuminate\Console\Command;

class SafeCleanTranslation extends Command
{
    protected $signature = 'translations:safe-clean
                            {--source= : The source language for the translations to clean (defaults to app.locale)}
                            {--print : Print the cleaned translations to the console, instead of updating the file}
                            {--all : Clean all language files (default when no source specified)}';

    protected $description = 'Clean translations by removing unused keys (safe version that handles blade parsing errors)';

    public function handle(): void
    {
        $this->info('Cleaning translations (safe mode)...');

        $source = $this->option('source') ?: config('app.locale', 'en');
        $cleanAll = $this->option('all') || empty($this->option('source'));
        $print = (bool) $this->option('print');

        $sourceFilePaths = $this->getSourceFilePaths();

        if ($cleanAll) {
            // Get JSON language files manually
            $langDir = base_path('lang');
            $files = glob($langDir.'/*.json');

            foreach ($files as $file) {
                if (basename($file) === '.DS_Store') {
                    continue;
                }
                $this->cleanFile($file, $sourceFilePaths, $print);
            }
        } else {
            $sourceJsonPath = $this->getTargetLanguagePath($source);
            $this->cleanFile($sourceJsonPath, $sourceFilePaths, $print);
        }
    }

    /**
     * @return array<string>
     */
    protected function getSourceFilePaths(): array
    {
        return config('translator.source_paths', [
            base_path('app/'),
            base_path('resources/'),
        ]);
    }

    protected function getTargetLanguagePath(string $locale): string
    {
        return base_path("lang/{$locale}.json");
    }

    /**
     * @param  array<string>  $sourceFilePaths
     */
    private function cleanFile(string $languageFilePath, array $sourceFilePaths, bool $print): void
    {
        // Use our SafeMissingKeysFinder instead of the default one
        $translationFinder = new TranslationFinder(
            new FileManagement,
            new LanguageFileManagerFactory($languageFilePath),
            new SafeMissingKeysFinder
        );

        $foundTranslations = $translationFinder->findAllTranslations($sourceFilePaths)->getKeys();
        $sourceFileManager = new LanguageFileManagerFactory($languageFilePath);
        $sourceTranslations = $sourceFileManager->readFile();

        // Keep only translations that are actually used in the codebase
        $cleanedTranslations = array_intersect_key($sourceTranslations, $foundTranslations);

        // Calculate what would be removed
        $removedCount = count($sourceTranslations) - count($cleanedTranslations);
        $removedKeys = array_diff_key($sourceTranslations, $foundTranslations);

        if ($print) {
            $this->info("File: {$languageFilePath}");
            $this->info("Found {$removedCount} unused translations:");
            foreach ($removedKeys as $key => $value) {
                $displayValue = is_string($value) ? (strlen($value) > 50 ? substr($value, 0, 50).'...' : $value) : 'null';
                $this->line("  - \"{$key}\" => \"{$displayValue}\"");
            }
            $this->newLine();
        } else {
            $sourceFileManager->updateFile($cleanedTranslations);
            $this->info("Removed {$removedCount} unused translations from {$languageFilePath}");
        }
    }
}
