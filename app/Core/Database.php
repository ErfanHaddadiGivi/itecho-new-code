<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * اتصال به دیتابیس با PDO + چند تابع کمکی برای کوئری‌های روزمره.
 *
 * ⚠️ قانون طلایی: هرگز مقدار ورودی کاربر را داخل رشته SQL نچسبانید.
 * همیشه از پارامتر استفاده کنید:
 *      Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
 * این کار جلوی حمله SQL Injection را می‌گیرد.
 */
class Database
{
    private static ?PDO $pdo = null;

    /**
     * گرفتن اتصال (فقط یک بار ساخته می‌شود)
     */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = (string) config('db.host', 'localhost');
        $name    = (string) config('db.name', '');
        $user    = (string) config('db.user', '');
        $pass    = (string) config('db.pass', '');
        $charset = (string) config('db.charset', 'utf8mb4');

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                // خطاها به صورت Exception گزارش شوند تا بی‌صدا رد نشوند
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // نتیجه‌ها به صورت آرایه انجمنی برگردند
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // استفاده از Prepared Statement واقعی سرور
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            self::connectionFailed($e);
        }

        return self::$pdo;
    }

    /**
     * اجرای یک کوئری با پارامتر
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    /**
     * گرفتن یک ردیف (یا null)
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * گرفتن همه ردیف‌ها
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /**
     * گرفتن یک مقدار تکی — مثلاً COUNT(*)
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /**
     * درج یک ردیف و برگرداندن شناسه آن
     */
    public static function insert(string $table, array $data): int
    {
        $columns      = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        self::run($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * به‌روزرسانی ردیف‌ها و برگرداندن تعداد ردیف‌های تغییرکرده
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = "`{$column}` = ?";
        }

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE %s',
            $table,
            implode(', ', $assignments),
            $where
        );

        return self::run($sql, array_merge(array_values($data), $whereParams))->rowCount();
    }

    /**
     * حذف ردیف‌ها
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }

    // --- تراکنش: برای عملیات چند مرحله‌ای مثل ثبت سفارش ---

    public static function beginTransaction(): void
    {
        self::pdo()->beginTransaction();
    }

    public static function commit(): void
    {
        self::pdo()->commit();
    }

    public static function rollBack(): void
    {
        if (self::pdo()->inTransaction()) {
            self::pdo()->rollBack();
        }
    }

    /**
     * پیام خطای قابل فهم وقتی اتصال به دیتابیس برقرار نمی‌شود
     */
    private static function connectionFailed(PDOException $e): never
    {
        if (config('debug')) {
            throw $e;
        }

        error_log('DB connection failed: ' . $e->getMessage());

        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html dir="rtl" lang="fa"><meta charset="utf-8">'
           . '<title>خطا در اتصال به دیتابیس</title>'
           . '<style>body{font-family:Tahoma,sans-serif;background:#f4f6f5;color:#16211c;display:flex;'
           . 'align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}'
           . 'div{background:#fff;border:1px solid #dbe4de;border-radius:12px;padding:32px;max-width:520px;line-height:2}'
           . 'h1{margin:0 0 12px;font-size:20px}code{background:#eef3f0;padding:2px 6px;border-radius:4px;direction:ltr;'
           . 'display:inline-block}</style>'
           . '<div><h1>اتصال به دیتابیس برقرار نشد</h1>'
           . '<p>اطلاعات دیتابیس را در فایل <code>config/config.local.php</code> بررسی کنید. '
           . 'مطمئن شوید نام دیتابیس، نام کاربری و رمز عبور درست وارد شده‌اند و کاربر به دیتابیس دسترسی دارد.</p>'
           . '</div></html>';
        exit;
    }
}
