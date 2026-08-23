<?php

namespace App\Services;

/**
 * Turn an uploaded CSV file into the same associative row shape that the
 * Google Sheet push sends, so both feed the exact same SheetSync logic.
 *
 * The first line is the header row; its cell values become the keys of every
 * following row (name, slug, category_id, ... — the same names as the sheet).
 * Quoted fields (e.g. a price written as "153,913,000") are handled by the
 * CSV reader; SheetSync then normalises the digits.
 *
 * All identifiers, comments and messages are intentionally in English.
 */
class SheetCsv
{
    /**
     * Parse CSV text into a list of associative rows.
     *
     * @return array<int,array<string,string>>
     */
    public static function parse(string $content): array
    {
        // Strip a UTF-8 byte-order mark that Excel/Sheets often prepend, so the
        // first header key is not silently corrupted.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return [];
        }
        fwrite($handle, $content);
        rewind($handle);

        $headers = null;
        $rows    = [];

        // escape = "" selects standard RFC-4180 CSV quoting (no backslash magic).
        while (($cells = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            // Skip blank lines.
            if ($cells === [null] || (count($cells) === 1 && trim((string) ($cells[0] ?? '')) === '')) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(static fn ($h) => trim((string) $h), $cells);
                continue;
            }

            $row     = [];
            $isEmpty = true;
            foreach ($headers as $i => $key) {
                if ($key === '') {
                    continue;
                }
                $value = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
                if ($value !== '') {
                    $isEmpty = false;
                }
                $row[$key] = $value;
            }

            if (!$isEmpty) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }
}
