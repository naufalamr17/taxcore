<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class SpreadsheetService
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @param array<int, array<int, string|int|float|null>> $rows */
    public function write(array $rows, string $sheetName = 'Payroll', int $headerRow = 1): string
    {
        $sheet = new \XMLWriter;
        $sheet->openMemory();
        $sheet->startDocument('1.0', 'UTF-8');
        $sheet->startElement('worksheet');
        $sheet->writeAttribute('xmlns', self::NS);
        $sheet->startElement('sheetViews');
        $sheet->startElement('sheetView');
        $sheet->writeAttribute('workbookViewId', '0');
        $sheet->startElement('pane');
        $sheet->writeAttribute('ySplit', (string) $headerRow);
        $sheet->writeAttribute('topLeftCell', 'A'.($headerRow + 1));
        $sheet->writeAttribute('state', 'frozen');
        $sheet->endElement();
        $sheet->endElement();
        $sheet->endElement();
        $sheet->startElement('cols');
        $sheet->startElement('col');
        $sheet->writeAttribute('min', '1');
        $sheet->writeAttribute('max', '40');
        $sheet->writeAttribute('width', '23');
        $sheet->writeAttribute('customWidth', '1');
        $sheet->endElement();
        $sheet->endElement();
        $sheet->startElement('sheetData');
        foreach ($rows as $index => $row) {
            $sheet->startElement('row');
            $sheet->writeAttribute('r', (string) ($index + 1));
            foreach ($row as $column => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $sheet->startElement('c');
                $sheet->writeAttribute('r', $this->column($column).($index + 1));
                $sheet->writeAttribute('s', $index + 1 === $headerRow ? '1' : '0');
                if (is_int($value) || is_float($value)) {
                    $sheet->writeElement('v', (string) $value);
                } else {
                    $sheet->writeAttribute('t', 'inlineStr');
                    $sheet->startElement('is');
                    $sheet->writeElement('t', $value);
                    $sheet->endElement();
                }
                $sheet->endElement();
            }
            $sheet->endElement();
        }
        $sheet->endElement();
        $sheet->endElement();
        $sheet->endDocument();
        $path = tempnam(sys_get_temp_dir(), 'taxcore-xlsx-');
        if ($path === false) {
            throw new \RuntimeException('Cannot create export file.');
        }
        $zip = new ZipArchive;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create XLSX archive.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.htmlspecialchars($sheetName, ENT_QUOTES | ENT_XML1, 'UTF-8').'" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $zip->addFromString('xl/styles.xml', '<?xml version="1.0"?><styleSheet xmlns="'.self::NS.'"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF172554"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf fontId="0" fillId="0" borderId="0" xfId="0"/><xf fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"/></cellXfs></styleSheet>');
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet->outputMemory());
            if (! $zip->close()) {
                throw new \RuntimeException('Cannot finish XLSX archive.');
            }
            $bytes = file_get_contents($path);
            if ($bytes === false) {
                throw new \RuntimeException('Cannot read XLSX archive.');
            }

            return $bytes;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @return array<int, array<int, string>> */
    public function read(string $path, string $extension): array
    {
        // shortcut: Read the first standard XLSX worksheet; use a library when multi-sheet or legacy XLS imports are required.
        if ($extension !== 'xlsx') {
            $content = file_get_contents($path);
            if ($content === false) {
                throw ValidationException::withMessages(['file' => 'File tidak dapat dibaca.']);
            }

            return $this->delimited($content);
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'File XLSX tidak valid.']);
        }
        try {
            $total = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $total += (int) $stat['size'];
            }
            if ($total > 30000000 || $zip->numFiles > 2000) {
                throw ValidationException::withMessages(['file' => 'Ukuran XLSX setelah ekstraksi melebihi batas 30 MB.']);
            }
            $shared = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $strings = $this->xml((string) $zip->getFromName('xl/sharedStrings.xml'));
                foreach ($strings->children(self::NS)->si as $item) {
                    $parts = $item->xpath('.//*[local-name()="t"]');
                    $shared[] = implode('', array_map(fn ($part) => (string) $part, $parts));
                }
            }
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($xml === false) {
                throw ValidationException::withMessages(['file' => 'Worksheet pertama tidak ditemukan. Simpan ulang sebagai XLSX standar.']);
            }
            $sheet = $this->xml($xml);
            $rows = [];
            $previousRow = 0;
            foreach ($sheet->children(self::NS)->sheetData->row as $row) {
                $rowAttributes = $row->attributes();
                $rowReference = (string) $rowAttributes['r'];
                if ($rowReference !== '' && ! preg_match('/^[1-9][0-9]{0,6}$/D', $rowReference)) {
                    throw ValidationException::withMessages(['file' => 'Referensi baris XLSX tidak valid.']);
                }
                $rowNumber = $rowReference === '' ? $previousRow + 1 : (int) $rowReference;
                if ($rowNumber <= $previousRow || $rowNumber > 1048576) {
                    throw ValidationException::withMessages(['file' => 'Referensi baris XLSX tidak valid.']);
                }
                $previousRow = $rowNumber;
                $cells = [];
                $nextColumn = 1;
                foreach ($row->c as $cell) {
                    if (isset($cell->f)) {
                        throw ValidationException::withMessages(['file' => 'Ganti formula dengan nilai sebelum mengimpor.']);
                    }
                    $attributes = $cell->attributes();
                    $reference = (string) $attributes['r'];
                    $column = $nextColumn;
                    if ($reference !== '') {
                        if (! preg_match('/^([A-Z]{1,3})([1-9][0-9]{0,6})$/D', $reference, $match) || (int) $match[2] !== $rowNumber) {
                            throw ValidationException::withMessages(['file' => 'Referensi sel XLSX tidak valid.']);
                        }
                        $column = 0;
                        foreach (str_split($match[1]) as $character) {
                            $column = $column * 26 + ord($character) - 64;
                        }
                    }
                    if ($column > 16384 || array_key_exists($column - 1, $cells)) {
                        throw ValidationException::withMessages(['file' => 'Referensi sel XLSX tidak valid.']);
                    }
                    $nextColumn = $column + 1;
                    $value = (string) $cell->v;
                    if ((string) $attributes['t'] === 's') {
                        $value = $shared[(int) $value] ?? '';
                    } elseif ((string) $attributes['t'] === 'inlineStr') {
                        $value = implode('', array_map(fn ($part) => (string) $part, $cell->xpath('.//*[local-name()="t"]')));
                    }
                    $cells[$column - 1] = $value;
                }
                if ($cells) {
                    ksort($cells);
                    $rows[] = $cells;
                }
                if (count($rows) > 10001) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 10.000 baris per impor.']);
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, array<int, string>> */
    public function delimited(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $first = strtok($text, "\r\n") ?: '';
        $delimiter = str_contains($first, "\t") ? "\t" : (substr_count($first, ';') > substr_count($first, ',') ? ';' : ',');
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $text);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            if (count($rows) > 10000) {
                fclose($stream);
                throw ValidationException::withMessages(['pasted' => 'Maksimal 10.000 baris per impor.']);
            }
            if (array_filter($row, fn ($value) => $value !== null && $value !== '')) {
                $rows[] = array_map(fn ($value) => (string) $value, $row);
            }
        }
        fclose($stream);

        return $rows;
    }

    private function xml(string $xml): \SimpleXMLElement
    {
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            throw ValidationException::withMessages(['file' => 'Deklarasi entitas XML tidak diizinkan.']);
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $parsed = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            if ($parsed === false) {
                throw ValidationException::withMessages(['file' => 'Struktur XML dalam XLSX tidak valid.']);
            }

            return $parsed;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function column(int $index): string
    {
        $name = '';
        do {
            $name = chr(65 + $index % 26).$name;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $name;
    }
}
