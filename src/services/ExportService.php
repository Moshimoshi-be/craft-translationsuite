<?php

namespace moshimoshi\translationsuite\services;

use Box\Spout\Writer\Common\Creator\Style\BorderBuilder;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
use Box\Spout\Writer\WriterAbstract;
use craft\base\Component;

/**
 * ExportService
 *
 * @since       2021-07-14 15:19
 * @author      pieterjangeeroms
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
        $fileContent = "<?php \n\n return [";
        foreach ($translations as $message => $translation) {
            $fileContent .= "\n\t\"" . $message . "\" => \"" . $translation . "\",";
        }
        $fileContent .= "\n ];";

        $success = file_put_contents($path, $fileContent);

        return $success ? $path : false;
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
}