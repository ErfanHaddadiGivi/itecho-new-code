<?php

namespace App\Core;

/**
 * صفحه‌بندی ساده.
 *
 * روش استفاده:
 *      $page = new Paginator($totalRows, 12, $currentPage);
 *      $sql .= ' LIMIT ' . $page->limit() . ' OFFSET ' . $page->offset();
 *
 * و در قالب:
 *      View::partial('site/partials/pagination', ['paginator' => $page]);
 */
class Paginator
{
    public readonly int $total;
    public readonly int $perPage;
    public readonly int $current;
    public readonly int $lastPage;

    public function __construct(int $total, int $perPage = 12, int $current = 1)
    {
        $this->total    = max(0, $total);
        $this->perPage  = max(1, $perPage);
        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));

        // شماره صفحه هرگز خارج از بازه معتبر نمی‌رود
        $this->current = min(max(1, $current), $this->lastPage);
    }

    public function limit(): int
    {
        return $this->perPage;
    }

    public function offset(): int
    {
        return ($this->current - 1) * $this->perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    /**
     * شماره صفحه‌هایی که در نوار صفحه‌بندی نمایش داده می‌شوند.
     * برای اینکه با ۵۰ صفحه، ۵۰ دکمه نمایش داده نشود، فقط اطراف صفحه فعلی نشان داده می‌شود.
     * مقدار 0 یعنی «…»
     */
    public function pages(int $around = 2): array
    {
        if ($this->lastPage <= 7) {
            return range(1, $this->lastPage);
        }

        $pages = [1];

        $start = max(2, $this->current - $around);
        $end   = min($this->lastPage - 1, $this->current + $around);

        if ($start > 2) {
            $pages[] = 0;
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $this->lastPage - 1) {
            $pages[] = 0;
        }

        $pages[] = $this->lastPage;

        return $pages;
    }

    /**
     * ساخت آدرس یک صفحه، با حفظ فیلترهای فعلی (برند، قیمت و ...)
     */
    public function urlFor(int $page): string
    {
        $query = $_GET;
        $query['page'] = $page;

        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

        return $path . '?' . http_build_query($query);
    }
}
