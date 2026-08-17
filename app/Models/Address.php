<?php

namespace App\Models;

use App\Core\Database;

/**
 * دفترچه آدرس کاربر.
 *
 * هر کاربر می‌تواند چند آدرس داشته باشد و یکی از آن‌ها «پیش‌فرض» است
 * که هنگام تسویه‌حساب به‌صورت خودکار انتخاب می‌شود.
 */
class Address extends Model
{
    protected static string $table = 'user_addresses';

    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC',
            [$userId]
        );
    }

    /**
     * آدرس پیش‌فرض کاربر (یا آخرین آدرس اگر پیش‌فرضی تعیین نشده)
     */
    public static function defaultFor(int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1',
            [$userId]
        );
    }

    /**
     * خواندن آدرس با بررسی مالکیت — کاربر نباید بتواند آدرس دیگری را ببیند
     */
    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1',
            [$id, $userId]
        );
    }

    public static function countFor(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM user_addresses WHERE user_id = ?', [$userId]
        );
    }

    /**
     * ثبت آدرس جدید. اولین آدرس هر کاربر خودکار پیش‌فرض می‌شود.
     */
    public static function add(int $userId, array $data, bool $makeDefault = false): int
    {
        $isFirst = self::countFor($userId) === 0;
        $default = $isFirst || $makeDefault;

        if ($default) {
            self::clearDefault($userId);
        }

        $data['user_id']    = $userId;
        $data['is_default'] = $default ? 1 : 0;

        return Database::insert('user_addresses', $data);
    }

    public static function edit(int $id, int $userId, array $data, bool $makeDefault = false): void
    {
        if (self::findForUser($id, $userId) === null) {
            return;
        }

        if ($makeDefault) {
            self::clearDefault($userId);
            $data['is_default'] = 1;
        }

        Database::update('user_addresses', $data, 'id = ? AND user_id = ?', [$id, $userId]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        if (self::findForUser($id, $userId) === null) {
            return;
        }

        self::clearDefault($userId);
        Database::update('user_addresses', ['is_default' => 1], 'id = ? AND user_id = ?', [$id, $userId]);
    }

    /**
     * حذف آدرس. اگر آدرس پیش‌فرض حذف شود، آدرس بعدی پیش‌فرض می‌شود
     * تا کاربر همیشه یک آدرس پیش‌فرض داشته باشد.
     */
    public static function remove(int $id, int $userId): void
    {
        $address = self::findForUser($id, $userId);

        if ($address === null) {
            return;
        }

        Database::delete('user_addresses', 'id = ? AND user_id = ?', [$id, $userId]);

        if ((int) $address['is_default'] === 1) {
            $next = Database::fetch(
                'SELECT id FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1',
                [$userId]
            );

            if ($next !== null) {
                Database::update('user_addresses', ['is_default' => 1], 'id = ?', [$next['id']]);
            }
        }
    }

    private static function clearDefault(int $userId): void
    {
        Database::update('user_addresses', ['is_default' => 0], 'user_id = ?', [$userId]);
    }
}
