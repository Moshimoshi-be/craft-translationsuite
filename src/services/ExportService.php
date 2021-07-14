<?php

namespace moshimoshi\translationsuite\services;

use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\WriterAbstract;
use craft\base\Component;
use ZipArchive;

/**
 * ExportService
 *
 * @author    Moshi Moshi
 * @package   Translationsuite
 * @since     1.0.6
 */
class ExportService extends Component
{

    /**
     * Write translations to a PHP file
     *
     * @param array $translations
     * @param string $path
     * @return false|string
     */
    public function toPhp(array $translations, string $path) {
        // Actually parse the translations to this format
        // Create a zip and add all the languages
        // Return the zip file
        $localizedArray = [];

        foreach ($translations as $translation) {
            foreach ($translation['languages'] as $language) {
                $locale = $language['locale'];
                $message = $translation['message'];
                $category = $translation['category'];
                $translated = $language['db'] ?? $language['file'] ?? '';
                $localizedArray[$locale][$category][$message] = $translated;
            }
        }

        if (!mkdir($path) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $path));
        }

        $zip = new ZipArchive();
        $zipName = 'export.zip';
        $zipPath = $path . '/' . $zipName;
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->filename = $zipName;

        $phpPath = $path . '/files';
        foreach ($localizedArray as $locale => $categories) {
            foreach ($categories as $category => $translations) {
                $phpString = $this->parseToPhp($translations);
                $localPath = $locale . '/' . $category;
                $translatePath = $phpPath . '/' . $localPath;
                if (!mkdir($translatePath, 0777, true) && !is_dir($translatePath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $translatePath));
                }
                $filePath = $translatePath . ".php";
                $success = file_put_contents($filePath, $phpString);
                $localPath .= ".php";
                $zip->addFile($filePath, $localPath);
            }
        }

        $zip->close();

        return $success ? $zipPath : false;
    }

    public function toExcel(array $translations, string $filepath) {
        $writer = WriterEntityFactory::createXLSXWriter();
        $this->writeHelper($writer, $translations, $filepath);
        return $filepath;
    }

    public function toCsv(array $translations, string $filepath) {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $this->writeHelper($writer, $translations, $filepath);
        return $filepath;
    }

    private function writeHelper(WriterAbstract $writer, array $translations, string $filepath) {
        $writer->openToFile($filepath);
        $header = [
            'Message',
            'Category'
        ];
        $availableLanguages = reset($translations)['languages'];

        foreach ($availableLanguages as $language) {
            $header[] = strtoupper($language['locale']);
        }
        $border = (new BorderBuilder())->setBorderBottom()->build();
        $style = (new StyleBuilder())->setBorder($border)->setFontBold()->setFontSize(12)->build();
        $writer->addRow(WriterEntityFactory::createRowFromArray($header)->setStyle($style));

        foreach ($translations as $message => $translation) {
            $arr = [
                $translation['message'],
                $translation['category'],
            ];

            $languages = $translation['languages'];
            foreach($languages as $language) {
                $arr[] = $language['db'] ?? $language['file'] ?? '';
            }

            $row = WriterEntityFactory::createRowFromArray($arr);
            $writer->addRow($row);
        }
        $writer->close();
    }

    private function parseToPhp(array $translations) {
        $fileContent = "<?php \n\n return [";
        foreach ($translations as $message => $translation) {
            $fileContent .= "\n\t\"" . $message . "\" => \"" . $translation . "\",";
        }
        $fileContent .= "\n ];";

        return $fileContent;
    }
}