<?php

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table as TableStyle;

/**
 * Small wrapper around PhpWord (composer require phpoffice/phpword).
 * It saves a real .docx file that opens in Word and Google Docs.
 */
final class DocxBuilder
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    private PhpWord $word;
    private $section;
    private int $contentW;

    // Load composer and check that PhpWord is installed.
    public static function available(): bool
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (is_readable($autoload)) {
            require_once $autoload;
        }

        return class_exists(PhpWord::class);
    }

    public function __construct(bool $landscape = false, string $footerText = '')
    {
        if (!self::available()) {
            throw new RuntimeException('PhpWord is not installed. Run "composer require phpoffice/phpword".');
        }

        // Let PhpWord escape &, < and > for us.
        Settings::setOutputEscapingEnabled(true);

        $this->word = new PhpWord();
        $this->word->setDefaultFontName('Calibri');
        $this->word->setDefaultFontSize(10);
        $this->word->setDefaultParagraphStyle(['spaceBefore' => 0, 'spaceAfter' => 60]);

        $this->word->addTitleStyle(
            1,
            ['bold' => true, 'size' => 20, 'color' => '6B3F2A'],
            ['spaceBefore' => 0, 'spaceAfter' => 60, 'keepNext' => true]
        );
        $this->word->addTitleStyle(
            2,
            ['bold' => true, 'size' => 13, 'color' => '6B3F2A'],
            [
                'spaceBefore'       => 360,
                'spaceAfter'        => 160,
                'keepNext'          => true,
                'borderBottomSize'  => 12,
                'borderBottomColor' => '6B3F2A',
            ]
        );

        // A4, in twips
        $margin = 850;
        $this->contentW = ($landscape ? 16838 : 11906) - (2 * $margin);

        $this->section = $this->word->addSection([
            'orientation'  => $landscape ? 'landscape' : 'portrait',
            'marginLeft'   => $margin,
            'marginRight'  => $margin,
            'marginTop'    => 1000,
            'marginBottom' => 1000,
            'footerHeight' => 500,
        ]);

        $footer = $this->section->addFooter();
        $footer->addPreserveText(
            self::clean($footerText) . '  ·  Page {PAGE} of {NUMPAGES}',
            ['size' => 8, 'color' => '737373'],
            ['alignment' => 'center', 'spaceAfter' => 0]
        );
    }

    // Level 1 is the title, level 2 is a section title.
    public function heading(string $text, int $level = 1): static
    {
        $this->section->addTitle(self::clean($text), $level === 2 ? 2 : 1);

        return $this;
    }

    /**
     * Add a paragraph.
     * $content is a string, or a list of runs: [[text, options], ...]
     * Options: bold, italic, color (hex), size (half-points), mono, before, after, align, keepNext
     */
    public function paragraph(string|array $content, array $options = []): static
    {
        $runs = is_array($content) ? $content : [[$content, $options]];
        $paragraph = $this->section->addTextRun(self::paraStyle($options));
        self::addRuns($paragraph, $runs);

        return $this;
    }

    // Empty space between blocks (height in twips).
    public function spacer(int $height = 120): static
    {
        $this->section->addText(
            '',
            ['size' => max(1, $height / 20)],
            ['spaceBefore' => 0, 'spaceAfter' => 0]
        );

        return $this;
    }

    /**
     * Add a table.
     * $rows: list of rows, each row a list of cells.
     * A cell is a string or an array with:
     *   text, bold, italic, color, size, mono, shade (hex fill), span, align
     *   paras: list of paragraphs, each paragraph a list of runs [[text, options], ...]
     * $widths: relative column widths.
     * Options: border (hex), headerRows (int), after (spacer height)
     */
    public function table(array $rows, array $widths, array $options = []): static
    {
        $widths = array_values($widths);
        $count = count($widths);
        $total = array_sum($widths) ?: 1;

        $grid = [];
        $used = 0;
        foreach ($widths as $i => $w) {
            $tw = $i === $count - 1
                ? $this->contentW - $used
                : (int) round($this->contentW * $w / $total);
            $grid[] = $tw;
            $used += $tw;
        }

        $headerRows = (int) ($options['headerRows'] ?? 0);

        $table = $this->section->addTable([
            'borderSize'       => 4,
            'borderColor'      => self::hex($options['border'] ?? 'E6DCD3'),
            'cellMarginTop'    => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft'   => 100,
            'cellMarginRight'  => 100,
            'width'            => $this->contentW,
            'unit'             => TblWidth::TWIP,
            'layout'           => TableStyle::LAYOUT_FIXED,
        ]);

        foreach (array_values($rows) as $r => $row) {
            $rowStyle = ['cantSplit' => true];
            if ($r < $headerRows) {
                $rowStyle['tblHeader'] = true;
            }
            $table->addRow(null, $rowStyle);

            $col = 0;
            foreach ($row as $cell) {
                if (!is_array($cell)) {
                    $cell = ['text' => (string) $cell];
                }
                $span = max(1, (int) ($cell['span'] ?? 1));
                $width = array_sum(array_slice($grid, $col, $span));
                $col += $span;

                $cellStyle = [];
                if ($span > 1) {
                    $cellStyle['gridSpan'] = $span;
                }
                if (!empty($cell['shade'])) {
                    $cellStyle['bgColor'] = self::hex($cell['shade']);
                }

                $target = $table->addCell($width, $cellStyle);
                $paraStyle = ['spaceBefore' => 0, 'spaceAfter' => 0, 'alignment' => self::align($cell['align'] ?? null)];

                if (!empty($cell['paras'])) {
                    foreach ($cell['paras'] as $runs) {
                        self::addRuns($target->addTextRun($paraStyle), $runs);
                    }
                } else {
                    $runOptions = array_intersect_key($cell, array_flip(['bold', 'italic', 'color', 'size', 'mono']));
                    self::addRuns($target->addTextRun($paraStyle), [[(string) ($cell['text'] ?? ''), $runOptions]]);
                }
            }
        }

        // Space after the table so the next block does not touch it.
        $this->spacer((int) ($options['after'] ?? 160));

        return $this;
    }

    // Return the finished .docx file as a string.
    public function build(): string
    {
        $writer = IOFactory::createWriter($this->word, 'Word2007');

        $tmp = tempnam(sys_get_temp_dir(), 'squirdoc');
        if ($tmp === false) {
            throw new RuntimeException('Could not create a temporary file for the export.');
        }

        try {
            $writer->save($tmp);
            $body = file_get_contents($tmp);
        } finally {
            @unlink($tmp);
        }

        if ($body === false || $body === '') {
            throw new RuntimeException('Could not read the generated Docs file.');
        }

        return $body;
    }

    // ---------------------------------------------------------------- helpers

    // Remove characters that XML cannot hold.
    private static function clean(mixed $value): string
    {
        $text = (string) ($value ?? '');
        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        $clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text);

        return $clean === null ? $text : $clean;
    }

    private static function hex(mixed $value): string
    {
        $hex = preg_replace('/[^0-9A-Fa-f]/', '', (string) $value);

        return ($hex === null || $hex === '') ? 'FFFFFF' : strtoupper($hex);
    }

    private static function align(mixed $value): string
    {
        return $value === 'center' ? 'center' : ($value === 'right' ? 'right' : 'left');
    }

    private static function paraStyle(array $o): array
    {
        $style = [
            'spaceBefore' => (int) ($o['before'] ?? 0),
            'spaceAfter'  => (int) ($o['after'] ?? 60),
            'alignment'   => self::align($o['align'] ?? null),
        ];
        if (!empty($o['keepNext'])) {
            $style['keepNext'] = true;
        }

        return $style;
    }

    private static function fontStyle(array $o): array
    {
        $style = [];
        if (!empty($o['bold'])) {
            $style['bold'] = true;
        }
        if (!empty($o['italic'])) {
            $style['italic'] = true;
        }
        if (!empty($o['color'])) {
            $style['color'] = self::hex($o['color']);
        }
        if (!empty($o['size'])) {
            // Sizes are given in half-points.
            $style['size'] = ((int) $o['size']) / 2;
        }
        if (!empty($o['mono'])) {
            $style['name'] = 'Consolas';
        }

        return $style;
    }

    // Add runs of text to a text run. New lines become line breaks.
    private static function addRuns($textRun, array $runs): void
    {
        foreach ($runs as $run) {
            $text = str_replace(["\r\n", "\r", "\t"], ["\n", "\n", '    '], self::clean($run[0] ?? ''));
            $font = self::fontStyle((array) ($run[1] ?? []));

            foreach (explode("\n", $text) as $i => $line) {
                if ($i > 0) {
                    $textRun->addTextBreak();
                }
                if ($line !== '') {
                    $textRun->addText($line, $font);
                }
            }
        }
    }
}