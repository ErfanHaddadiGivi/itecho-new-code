<?php

namespace App\Services;

use App\Core\Database;
use App\Models\SyncLog;

/**
 * Google Sheet product sync.
 *
 * Applies a batch of product rows pushed from the store owner's Google Sheet:
 *   - Rows are matched to existing products by SKU (the primary key).
 *   - A matching SKU updates only `price` and `stock`.
 *   - A new SKU inserts a new simple product (images and variants are never
 *     handled here — those stay in the admin panel).
 *   - Any active simple product whose SKU is absent from the sheet is
 *     deactivated (soft delete: is_active = 0), never hard-deleted.
 *
 * Every row is validated independently: one bad row is rejected with a reason
 * and never stops the rest of the batch. A report is returned and persisted.
 *
 * All identifiers, comments and reasons here are intentionally in English.
 */
class SheetSync
{
    /** Allowed values for the products.condition_type enum. */
    private const VALID_CONDITIONS = ['new', 'used'];

    /**
     * Run the sync for a batch of rows and return the report.
     *
     * @param array $rows list of associative product rows from the sheet
     * @return array{success:bool,summary:array,rejected_rows:array}
     */
    public function run(array $rows): array
    {
        $summary  = ['inserted' => 0, 'updated' => 0, 'deactivated' => 0, 'rejected' => 0];
        $rejected = [];

        // Reference data is loaded once and reused for the whole batch.
        $categoryIds = $this->loadIdSet('categories');
        $brandIds    = $this->loadIdSet('brands');
        $skuMap      = $this->loadSkuMap();

        // Every SKU seen in the payload (valid or not) is remembered, so a
        // malformed row can never cause its product to be deactivated.
        $seenSkus = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $summary['rejected']++;
                $rejected[] = ['sku' => null, 'reason' => 'row is not an object'];
                continue;
            }

            $result = $this->processRow($row, $categoryIds, $brandIds, $skuMap, $seenSkus);

            if ($result['action'] === 'rejected') {
                $summary['rejected']++;
                $rejected[] = ['sku' => $result['sku'], 'reason' => $result['reason']];
            } else {
                $summary[$result['action']]++;
            }
        }

        // Deactivation sweep. Skipped when no SKU was received at all, so an
        // empty or broken sheet can never wipe the whole catalogue.
        if ($seenSkus !== []) {
            $summary['deactivated'] = $this->deactivateMissing($seenSkus);
        }

        $report = [
            'success'       => true,
            'summary'       => $summary,
            'rejected_rows' => $rejected,
        ];

        // Persist the report so the owner can review past runs in the panel.
        SyncLog::record('google_sheet', count($rows), $summary, $rejected);

        return $report;
    }

    // ------------------------------------------------------------------
    //  Per-row processing
    // ------------------------------------------------------------------

    /**
     * Validate and apply a single row.
     *
     * @return array{action:string,sku:?string,reason:?string}
     */
    private function processRow(
        array $row,
        array $categoryIds,
        array $brandIds,
        array &$skuMap,
        array &$seenSkus
    ): array {
        $sku = $this->str($row['sku'] ?? '');

        // SKU is the matching key: without it we can neither match nor insert.
        if ($sku === '') {
            return $this->reject(null, 'missing required field: sku');
        }

        // Remember the SKU up front (even if the row is later rejected) so it
        // is treated as "present in the sheet" for the deactivation sweep.
        $seenSkus[$sku] = true;

        $existingId = $skuMap[$sku] ?? null;

        // Optional id column: if provided it must point to the same product
        // the SKU points to. A disagreement is a data error for this row.
        $rawId = $row['id'] ?? null;
        if ($rawId !== null && $this->str((string) $rawId) !== '') {
            $id = $this->parseIntValue((string) $rawId);
            if ($id === null || $existingId === null || $id !== $existingId) {
                return $this->reject($sku, 'id/sku mismatch');
            }
        }

        if ($existingId !== null) {
            return $this->updateExisting($existingId, $sku, $row);
        }

        return $this->insertNew($sku, $row, $categoryIds, $brandIds, $skuMap);
    }

    /**
     * Existing product: only price and stock may change (per spec).
     * All other columns in the row are ignored.
     */
    private function updateExisting(int $id, string $sku, array $row): array
    {
        $fields = [];

        $priceRaw = $this->str($row['price'] ?? '');
        if ($priceRaw !== '') {
            $price = $this->parseIntValue($priceRaw);
            if ($price === null || $price < 0) {
                return $this->reject($sku, 'invalid price');
            }
            $fields['price'] = $price;
        }

        $stockRaw = $this->str($row['stock'] ?? '');
        if ($stockRaw !== '') {
            $stock = $this->parseIntValue($stockRaw);
            if ($stock === null || $stock < 0) {
                return $this->reject($sku, 'invalid stock');
            }
            $fields['stock'] = $stock;
        }

        // A row with blank price and stock is still a valid "keep alive" signal:
        // it confirms the product is in the sheet, so it stays active.
        if ($fields !== []) {
            Database::update('products', $fields, 'id = ?', [$id]);
        }

        return ['action' => 'updated', 'sku' => $sku, 'reason' => null];
    }

    /**
     * New product: insert a simple product from the row.
     * Required: name, sku, price, category_id (and slug, which the column
     * requires as NOT NULL UNIQUE). Everything else falls back to defaults.
     */
    private function insertNew(
        string $sku,
        array $row,
        array $categoryIds,
        array $brandIds,
        array &$skuMap
    ): array {
        $name        = $this->str($row['name'] ?? '');
        $slug        = $this->str($row['slug'] ?? '');
        $priceRaw    = $this->str($row['price'] ?? '');
        $categoryRaw = $this->str($row['category_id'] ?? '');

        if ($name === '') {
            return $this->reject($sku, 'missing required field: name');
        }
        if ($priceRaw === '') {
            return $this->reject($sku, 'missing required field: price');
        }
        if ($categoryRaw === '') {
            return $this->reject($sku, 'missing required field: category_id');
        }
        // slug is entered manually in the sheet and is required by the schema.
        if ($slug === '') {
            return $this->reject($sku, 'missing required field: slug');
        }

        $price = $this->parseIntValue($priceRaw);
        if ($price === null || $price < 0) {
            return $this->reject($sku, 'invalid price');
        }

        $categoryId = $this->parseIntValue($categoryRaw);
        if ($categoryId === null || !isset($categoryIds[$categoryId])) {
            return $this->reject($sku, 'invalid category');
        }

        // brand_id is optional, but if given it must exist.
        $brandId  = null;
        $brandRaw = $this->str($row['brand_id'] ?? '');
        if ($brandRaw !== '') {
            $brandId = $this->parseIntValue($brandRaw);
            if ($brandId === null || !isset($brandIds[$brandId])) {
                return $this->reject($sku, 'invalid brand');
            }
        }

        // condition_type is optional; blank falls back to the column default.
        $condition = strtolower($this->str($row['condition_type'] ?? ''));
        if ($condition === '') {
            $condition = 'new';
        } elseif (!in_array($condition, self::VALID_CONDITIONS, true)) {
            return $this->reject($sku, 'invalid condition_type');
        }

        // stock is optional (default 0).
        $stock    = 0;
        $stockRaw = $this->str($row['stock'] ?? '');
        if ($stockRaw !== '') {
            $stock = $this->parseIntValue($stockRaw);
            if ($stock === null || $stock < 0) {
                return $this->reject($sku, 'invalid stock');
            }
        }

        // compare_at_price is optional.
        $compare    = null;
        $compareRaw = $this->str($row['compare_at_price'] ?? '');
        if ($compareRaw !== '') {
            $compare = $this->parseIntValue($compareRaw);
            if ($compare === null || $compare < 0) {
                return $this->reject($sku, 'invalid compare_at_price');
            }
        }

        // slug must be unique across products.
        if ($this->slugExists($slug)) {
            return $this->reject($sku, 'duplicate slug');
        }

        $data = [
            'name'             => $name,
            'slug'             => $slug,
            'sku'              => $sku,
            'category_id'      => $categoryId,
            'brand_id'         => $brandId,
            'condition_type'   => $condition,
            'price'            => $price,
            'stock'            => $stock,
            'compare_at_price' => $compare,
            // is_active (1), has_variants (0), etc. use the table defaults.
        ];

        try {
            $id = Database::insert('products', $data);
        } catch (\PDOException $e) {
            return $this->reject($sku, 'database error on insert');
        }

        // Register the new SKU so a duplicate row for it later in the same
        // batch is treated as an update instead of a second insert.
        $skuMap[$sku] = $id;

        return ['action' => 'inserted', 'sku' => $sku, 'reason' => null];
    }

    /**
     * Deactivate active simple products whose SKU is not in the sheet.
     * Variant products and SKU-less products are out of scope and untouched.
     *
     * @return int number of products deactivated
     */
    private function deactivateMissing(array $seenSkus): int
    {
        $candidates = Database::fetchAll(
            "SELECT id, sku FROM products
              WHERE is_active = 1 AND has_variants = 0 AND sku IS NOT NULL AND sku <> ''"
        );

        $count = 0;
        foreach ($candidates as $product) {
            if (!isset($seenSkus[$product['sku']])) {
                Database::update('products', ['is_active' => 0], 'id = ?', [(int) $product['id']]);
                $count++;
            }
        }

        return $count;
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /** Load a set of existing ids from a reference table as [id => true]. */
    private function loadIdSet(string $table): array
    {
        // $table is a fixed literal from this class, never user input.
        $rows = Database::fetchAll('SELECT id FROM `' . $table . '`');
        $set  = [];
        foreach ($rows as $row) {
            $set[(int) $row['id']] = true;
        }
        return $set;
    }

    /** Map of existing product SKU => id. */
    private function loadSkuMap(): array
    {
        $rows = Database::fetchAll(
            "SELECT id, sku FROM products WHERE sku IS NOT NULL AND sku <> ''"
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['sku']] = (int) $row['id'];
        }
        return $map;
    }

    private function slugExists(string $slug): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM products WHERE slug = ?',
            [$slug]
        ) > 0;
    }

    /** Trim any scalar to a string; non-scalars become an empty string. */
    private function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    /**
     * Parse an integer that may arrive with Persian/Arabic digits or thousand
     * separators. Returns null when the value is not a whole number.
     */
    private function parseIntValue(string $raw): ?int
    {
        $raw = en_digits(trim($raw));
        $raw = str_replace([',', '،', ' ', "\xc2\xa0"], '', $raw); // commas, spaces, nbsp
        if ($raw === '' || !preg_match('/^-?\d+$/', $raw)) {
            return null;
        }
        return (int) $raw;
    }

    /** @return array{action:string,sku:?string,reason:string} */
    private function reject(?string $sku, string $reason): array
    {
        return ['action' => 'rejected', 'sku' => $sku, 'reason' => $reason];
    }
}
