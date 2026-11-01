<?php

namespace App\Services;

use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SpreadsheetService
{
    /**
     * Download data as an .xlsx or .csv file
     *
     * @param string $filename Base filename without extension
     * @param array $headers List of column header titles
     * @param array $rows 2D array of values matching headers order
     * @param string $format 'xlsx' or 'csv'
     * @param string $sheetName Worksheet name for xlsx
     */
    public static function download(
        string $filename,
        array $headers,
        array $rows,
        string $format = 'xlsx',
        string $sheetName = 'Data'
    ): StreamedResponse {
        $format = strtolower($format) === 'csv' ? 'csv' : 'xlsx';

        if ($format === 'csv') {
            return self::downloadCsv($filename . '.csv', $headers, $rows);
        }

        return self::downloadXlsx($filename . '.xlsx', $headers, $rows, $sheetName);
    }

    /**
     * Stream a UTF-8 BOM CSV file compatible with Microsoft Excel
     */
    public static function downloadCsv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Microsoft Excel recognizes UTF-8 characters properly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Generate and stream a native OpenXML (.xlsx) spreadsheet file
     */
    public static function downloadXlsx(
        string $filename,
        array $headers,
        array $rows,
        string $sheetName = 'Sheet1'
    ): StreamedResponse {
        $tempFile = tempnam(sys_get_temp_dir(), 'educard_xlsx_');

        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new Exception('Gagal membuat file spreadsheet XLSX.');
        }

        $cleanSheetName = preg_replace('/[\[\]\*\/\\\\\?\:]/', '', $sheetName) ?: 'Sheet1';
        $cleanSheetName = mb_substr($cleanSheetName, 0, 31);

        // 1. [Content_Types].xml
        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);

        // 2. _rels/.rels
        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $relsXml);

        // 3. xl/_rels/workbook.xml.rels
        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);

        // 4. xl/workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>'
            . '<sheet name="' . self::xmlEscape($cleanSheetName) . '" sheetId="1" r:id="rId1"/>'
            . '</sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbookXml);

        // 5. xl/styles.xml (Style 0 = normal, Style 1 = bold header with dark slate fill)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFE2E8F0"/></left>'
            . '<right style="thin"><color rgb="FFE2E8F0"/></right>'
            . '<top style="thin"><color rgb="FFE2E8F0"/></top>'
            . '<bottom style="thin"><color rgb="FFE2E8F0"/></bottom>'
            . '<diagonal/>'
            . '</border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. xl/worksheets/sheet1.xml
        $allRows = array_merge([$headers], $rows);
        $colCount = count($headers);

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Auto column widths
        if ($colCount > 0) {
            $sheetXml .= '<cols>';
            for ($c = 0; $c < $colCount; $c++) {
                $maxLen = mb_strlen((string) ($headers[$c] ?? ''));
                foreach ($rows as $r) {
                    $valLen = mb_strlen((string) ($r[$c] ?? ''));
                    if ($valLen > $maxLen) {
                        $maxLen = min($valLen, 50);
                    }
                }
                $width = max(12, $maxLen + 5);
                $colIdx = $c + 1;
                $sheetXml .= '<col min="' . $colIdx . '" max="' . $colIdx . '" width="' . $width . '" customWidth="1"/>';
            }
            $sheetXml .= '</cols>';
        }

        $sheetXml .= '<sheetData>';

        foreach ($allRows as $rowIdx => $rowValues) {
            $excelRow = $rowIdx + 1;
            $styleAttr = $rowIdx === 0 ? ' s="1"' : ' s="0"';
            $sheetXml .= '<row r="' . $excelRow . '">';

            foreach (array_values($rowValues) as $colIdx => $cellValue) {
                $colLetter = self::columnLetter($colIdx);
                $cellRef = $colLetter . $excelRow;
                $strVal = (string) ($cellValue ?? '');

                // Keep leading-zero strings (like NISN, phone numbers, card UIDs) as inline strings
                $isPureNumber = is_int($cellValue) || is_float($cellValue) || (
                    is_numeric($strVal)
                    && !preg_match('/^0\d+/', $strVal)
                    && strlen($strVal) <= 12
                );

                if ($rowIdx > 0 && $isPureNumber) {
                    $sheetXml .= '<c r="' . $cellRef . '"' . $styleAttr . '><v>' . $strVal . '</v></c>';
                } else {
                    $sheetXml .= '<c r="' . $cellRef . '" t="inlineStr"' . $styleAttr . '><is><t xml:space="preserve">'
                        . self::xmlEscape($strVal)
                        . '</t></is></c>';
                }
            }

            $sheetXml .= '</row>';
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Parse an uploaded .xlsx or .csv file into an array of associative rows
     * Keys are normalized lowercase snake_case column headers.
     */
    public static function parseUploadedFile(string $filePath, string $originalExtension = ''): array
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        // Detect ZIP/XLSX magic bytes (PK\x03\x04)
        $fh = fopen($filePath, 'rb');
        $magic = $fh ? fread($fh, 4) : '';
        if ($fh) {
            fclose($fh);
        }

        if ($ext === 'xlsx' || $magic === "PK\x03\x04") {
            $rawRows = self::readXlsxRows($filePath);
        } else {
            $rawRows = self::readCsvRows($filePath);
        }

        if (count($rawRows) < 2) {
            return [];
        }

        // Extract and normalize headers from first non-empty row
        $headerRow = null;
        $dataStartIndex = 0;
        foreach ($rawRows as $idx => $r) {
            $nonEmpty = array_filter($r, fn ($v) => trim((string) $v) !== '');
            if (!empty($nonEmpty)) {
                $headerRow = $r;
                $dataStartIndex = $idx + 1;
                break;
            }
        }

        if (!$headerRow) {
            return [];
        }

        $headers = array_map(fn ($h) => self::normalizeHeader((string) $h), $headerRow);

        $result = [];
        for ($i = $dataStartIndex; $i < count($rawRows); $i++) {
            $row = $rawRows[$i];
            $nonEmpty = array_filter($row, fn ($v) => trim((string) $v) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $colIdx => $key) {
                if ($key !== '') {
                    $assoc[$key] = isset($row[$colIdx]) ? trim((string) $row[$colIdx]) : '';
                }
            }
            $result[] = $assoc;
        }

        return $result;
    }

    /**
     * Read raw rows from a CSV file (auto-detecting ',' or ';' or "\t" delimiter)
     */
    private static function readCsvRows(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception('Gagal membaca file CSV.');
        }

        // Remove UTF-8 BOM if present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $firstLine = $lines[0] ?? '';

        // Detect delimiter
        $commaCount = substr_count($firstLine, ',');
        $semiCount = substr_count($firstLine, ';');
        $tabCount = substr_count($firstLine, "\t");

        $delimiter = ',';
        if ($semiCount > $commaCount && $semiCount >= $tabCount) {
            $delimiter = ';';
        } elseif ($tabCount > $commaCount && $tabCount > $semiCount) {
            $delimiter = "\t";
        }

        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Read raw rows from an XLSX file using ZipArchive + SimpleXML
     */
    private static function readXlsxRows(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('File XLSX tidak valid atau rusak.');
        }

        // 1. Read sharedStrings.xml if present
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $sst = new SimpleXMLElement($sharedStringsXml);
            foreach ($sst->si as $si) {
                if (isset($si->t)) {
                    $sharedStrings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $text = '';
                    foreach ($si->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }

        // 2. Read sheet1.xml
        $sheetXmlStr = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXmlStr === false) {
            throw new Exception('Worksheet tidak ditemukan di dalam file XLSX.');
        }

        $sheet = new SimpleXMLElement($sheetXmlStr);
        $rows = [];

        if (!isset($sheet->sheetData->row)) {
            return [];
        }

        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            $maxColIdx = 0;

            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r']; // e.g., "A1", "C2"
                $colLetters = preg_replace('/[0-9]+/', '', $cellRef);
                $colIdx = $colLetters !== '' ? self::columnIndexFromString($colLetters) : count($rowData);

                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = isset($cell->is->t) ? (string) $cell->is->t : '';
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }

                $rowData[$colIdx] = $value;
                if ($colIdx > $maxColIdx) {
                    $maxColIdx = $colIdx;
                }
            }

            // Fill any gaps in columns with empty string
            $normalizedRow = [];
            for ($c = 0; $c <= $maxColIdx; $c++) {
                $normalizedRow[$c] = $rowData[$c] ?? '';
            }
            $rows[] = $normalizedRow;
        }

        return $rows;
    }

    /**
     * Normalize header string for flexible matching
     */
    private static function normalizeHeader(string $header): string
    {
        $h = strtolower(trim($header));
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);
        return trim($h, '_');
    }

    /**
     * Convert 0-based column index to Excel column letter (0 -> A, 25 -> Z, 26 -> AA)
     */
    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(($index % 26) + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    /**
     * Convert Excel column letters to 0-based index (A -> 0, B -> 1, AA -> 26)
     */
    private static function columnIndexFromString(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    private static function xmlEscape(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
