<?php

/**
 * Simple .xlsx writer.
 */
final class XlsxBuilder
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const MIN_WIDTH = 8;
    private const MAX_WIDTH = 60;

    public static function available(): bool
    {
        return class_exists('ZipArchive');
    }

    /**
     * Build one sheet.
     * @param array    $header  Column titles.
     * @param iterable $rows    Each row is a list of cell values.
     * @param array    $options Sheet settings.
     */
    public static function build(array $header, iterable $rows, array $options = []): string
    {
        return self::buildWorkbook([[
            'name'    => (string) ($options['sheet'] ?? 'Sheet1'),
            'header'  => $header,
            'rows'    => $rows,
            'numeric' => $options['numeric'] ?? [],
        ]]);
    }

    /**
     * Build a workbook with multiple sheets.
     * @param array $sheets List of sheet data.
     */
    public static function buildWorkbook(array $sheets): string
    {
        if (!self::available()) {
            throw new RuntimeException('The PHP zip extension is required for Excel exports.');
        }
        if ($sheets === []) {
            throw new InvalidArgumentException('A workbook needs at least one sheet.');
        }

        $sheets = array_values($sheets);
        $temp = [];
        $names = [];
        $zipFile = tempnam(sys_get_temp_dir(), 'sqx');
        $temp[] = $zipFile;

        try {
            $sheetFiles = [];
            foreach ($sheets as $index => $sheet) {
                $names[] = self::uniqueSheetName((string) ($sheet['name'] ?? ''), $names, $index);
                $sheetFiles[] = $temp[] = self::writeSheet(
                    array_values($sheet['header'] ?? []),
                    $sheet['rows'] ?? [],
                    array_flip($sheet['numeric'] ?? [])
                );
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the Excel file.');
            }
            $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
            $zip->addFromString('_rels/.rels', self::rootRels());
            $zip->addFromString('xl/workbook.xml', self::workbook($names));
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
            $zip->addFromString('xl/styles.xml', self::styles());
            foreach ($sheetFiles as $i => $file) {
                $zip->addFile($file, 'xl/worksheets/sheet' . ($i + 1) . '.xml');
            }
            if ($zip->close() !== true) {
                throw new RuntimeException('Could not save the Excel file.');
            }

            return (string) file_get_contents($zipFile);
        } finally {
            foreach ($temp as $file) {
                if (is_string($file) && is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    // Write one sheet to a temp file.
    private static function writeSheet(array $header, iterable $rows, array $numeric): string
    {
        $colCount = count($header);
        $dataFile = tempnam(sys_get_temp_dir(), 'sqx');
        $sheetFile = tempnam(sys_get_temp_dir(), 'sqx');

        try {
            // Save rows in a temp file so memory stays low.
            $widths = [];
            foreach ($header as $i => $label) {
                $widths[$i] = self::textWidth((string) $label) + 4;
            }

            $data = fopen($dataFile, 'wb');
            fwrite($data, self::rowXml(1, $header, true, $numeric));

            $rowNumber = 1;
            foreach ($rows as $row) {
                $rowNumber++;
                $row = array_values($row);
                foreach ($row as $i => $value) {
                    if ($i < $colCount) {
                        $widths[$i] = max($widths[$i], self::textWidth((string) $value) + 2);
                    }
                }
                fwrite($data, self::rowXml($rowNumber, $row, false, $numeric));
            }
            fclose($data);

            $cols = '';
            foreach ($widths as $i => $width) {
                $width = max(self::MIN_WIDTH, min(self::MAX_WIDTH, $width));
                $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
            }

            $sheet = fopen($sheetFile, 'wb');
            fwrite($sheet,
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetViews><sheetView workbookViewId="0">'
                . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
                . '<selection pane="bottomLeft"/>'
                . '</sheetView></sheetViews>'
                . '<sheetFormatPr defaultRowHeight="15"/>'
                . '<cols>' . $cols . '</cols>'
                . '<sheetData>'
            );
            $data = fopen($dataFile, 'rb');
            stream_copy_to_stream($data, $sheet);
            fclose($data);
            fwrite($sheet,
                '</sheetData>'
                . '<autoFilter ref="A1:' . self::columnLetter($colCount - 1) . $rowNumber . '"/>'
                . '</worksheet>'
            );
            fclose($sheet);

            return $sheetFile;
        } finally {
            if (is_file($dataFile)) {
                @unlink($dataFile);
            }
        }
    }

    // One row. Styles: 1 = header, 4 = centered header, 2 = text, 3 = centered number.
    private static function rowXml(int $rowNumber, array $cells, bool $isHeader, array $numeric): string
    {
        $xml = '<row r="' . $rowNumber . '">';

        foreach (array_values($cells) as $i => $value) {
            $ref = self::columnLetter($i) . $rowNumber;
            $value = (string) $value;
            $isNumber = isset($numeric[$i]);

            if ($isHeader) {
                $style = $isNumber ? 4 : 1;
            } else {
                $style = $isNumber ? 3 : 2;
            }

            if (!$isHeader && $isNumber && is_numeric($value)) {
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                    . self::esc($value) . '</t></is></c>';
            }
        }

        return $xml . '</row>';
    }

    // Longest line in characters.
    private static function textWidth(string $text): int
    {
        $longest = 0;
        foreach (preg_split('/\R/u', $text) ?: [''] as $line) {
            $longest = max($longest, mb_strlen($line));
        }

        return $longest;
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        for ($n = $index; $n >= 0; $n = intdiv($n, 26) - 1) {
            $letter = chr(65 + $n % 26) . $letter;
        }

        return $letter;
    }

    private static function esc(string $text): string
    {
        // Strip control chars that XML can't handle.
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Sheet names must be unique, ignoring case.
    private static function uniqueSheetName(string $name, array $taken, int $index): string
    {
        $base = self::sheetName($name === '' ? 'Sheet' . ($index + 1) : $name);
        $candidate = $base;
        $n = 2;
        $lower = array_map('mb_strtolower', $taken);
        while (in_array(mb_strtolower($candidate), $lower, true)) {
            $suffix = ' ' . $n++;
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
        }

        return $candidate;
    }

    private static function sheetName(string $name): string
    {
        $name = trim((string) preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $name));

        return mb_substr($name === '' ? 'Sheet1' : $name, 0, 31);
    }

    private static function contentTypes(int $sheetCount): string
    {
        $sheets = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $sheets
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $i => $name) {
            $sheets .= '<sheet name="' . self::esc($name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private static function workbookRels(int $sheetCount): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '<Relationship Id="rId' . ($sheetCount + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private static function styles(): string
    {
        $edge = '<color rgb="FFE6DCD3"/>';
        $border = '<border><left style="thin">' . $edge . '</left><right style="thin">' . $edge . '</right>'
            . '<top style="thin">' . $edge . '</top><bottom style="thin">' . $edge . '</bottom><diagonal/></border>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF6B3F2A"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF5EDE6"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $border . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="5">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
