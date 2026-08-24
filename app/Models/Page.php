<?php

namespace App\Models;

use App\Core\Database;

/**
 * صفحات ثابت سایت (درباره ما، تماس، قوانین، حریم خصوصی و ...).
 */
class Page extends Model
{
    protected static string $table = 'pages';

    /**
     * همه صفحات برای فهرست پنل مدیریت
     */
    public static function allForAdmin(): array
    {
        return Database::fetchAll(
            'SELECT id, slug, title, is_active, updated_at FROM pages ORDER BY sort_order, id'
        );
    }
}
