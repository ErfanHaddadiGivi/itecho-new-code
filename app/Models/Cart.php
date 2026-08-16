<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * سبد خرید.
 *
 * طبق تصمیم تاییدشده: مهمان هم می‌تواند سبد داشته باشد و فقط هنگام
 * تسویه‌حساب باید وارد شود.
 *
 *   • کاربر واردشده → سبد با user_id شناخته می‌شود
 *   • مهمان         → سبد با یک توکن تصادفی در کوکی شناخته می‌شود
 *
 * قیمت هرگز در سبد ذخیره نمی‌شود و همیشه لحظه‌ای از محصول/Variant خوانده
 * می‌شود تا مشتری قیمت قدیمی نبیند.
 */
class Cart
{
    private const COOKIE = 'itecho_cart';
    private const COOKIE_DAYS = 30;
    private const MAX_QTY = 20;

    /**
     * شناسه سبد فعلی. اگر وجود نداشته باشد ساخته می‌شود.
     */
    public static function currentId(bool $createIfMissing = true): ?int
    {
        $userId = Auth::id();

        if ($userId !== null) {
            $cart = Database::fetch('SELECT id FROM carts WHERE user_id = ? LIMIT 1', [$userId]);

            if ($cart !== null) {
                return (int) $cart['id'];
            }

            return $createIfMissing
                ? Database::insert('carts', ['user_id' => $userId])
                : null;
        }

        // --- مهمان ---
        $token = $_COOKIE[self::COOKIE] ?? null;

        if (is_string($token) && preg_match('/^[a-f0-9]{40}$/', $token)) {
            $cart = Database::fetch('SELECT id FROM carts WHERE session_token = ? LIMIT 1', [$token]);

            if ($cart !== null) {
                return (int) $cart['id'];
            }
        }

        if (!$createIfMissing) {
            return null;
        }

        $token = bin2hex(random_bytes(20));
        self::setCookie($token);

        return Database::insert('carts', ['session_token' => $token]);
    }

    /**
     * اقلام سبد به همراه قیمت و موجودی لحظه‌ای
     */
    public static function items(): array
    {
        $cartId = self::currentId(false);

        if ($cartId === null) {
            return [];
        }

        return Database::fetchAll(
            'SELECT ci.id, ci.quantity, ci.product_id, ci.variant_id,
                    p.name, p.slug, p.main_image, p.is_active, p.has_variants,
                    COALESCE(v.price, p.price) AS unit_price,
                    COALESCE(v.stock, p.stock) AS available_stock,
                    v.title AS variant_title,
                    v.is_active AS variant_active
               FROM cart_items ci
               JOIN products p ON p.id = ci.product_id
               LEFT JOIN product_variants v ON v.id = ci.variant_id
              WHERE ci.cart_id = ?
              ORDER BY ci.id',
            [$cartId]
        );
    }

    /**
     * تعداد کل اقلام — برای نشان روی آیکون سبد
     */
    public static function count(): int
    {
        $cartId = self::currentId(false);

        if ($cartId === null) {
            return 0;
        }

        return (int) Database::fetchValue(
            'SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?', [$cartId]
        );
    }

    /**
     * جمع مبالغ سبد.
     *
     * @return array{items_total:int, count:int, problems:array}
     */
    public static function summary(?array $items = null): array
    {
        $items ??= self::items();

        $total    = 0;
        $count    = 0;
        $problems = [];

        foreach ($items as $item) {
            $total += (int) $item['unit_price'] * (int) $item['quantity'];
            $count += (int) $item['quantity'];

            // مشکلاتی که باید به مشتری اطلاع داده شود
            if ((int) $item['is_active'] === 0
                || ($item['variant_id'] !== null && (int) $item['variant_active'] === 0)) {
                $problems[$item['id']] = 'این کالا دیگر در دسترس نیست.';
            } elseif ((int) $item['available_stock'] <= 0) {
                $problems[$item['id']] = 'موجودی این کالا تمام شده است.';
            } elseif ((int) $item['quantity'] > (int) $item['available_stock']) {
                $problems[$item['id']] = 'فقط ' . fa_digits((string) (int) $item['available_stock'])
                                       . ' عدد از این کالا موجود است.';
            }
        }

        return ['items_total' => $total, 'count' => $count, 'problems' => $problems];
    }

    /**
     * افزودن کالا به سبد.
     *
     * @throws \RuntimeException با پیام فارسی
     */
    public static function add(int $productId, ?int $variantId, int $quantity = 1): void
    {
        $quantity = max(1, min(self::MAX_QTY, $quantity));

        $product = Database::fetch(
            'SELECT id, has_variants, stock, is_active FROM products WHERE id = ? LIMIT 1',
            [$productId]
        );

        if ($product === null || (int) $product['is_active'] === 0) {
            throw new \RuntimeException('این محصول در دسترس نیست.');
        }

        // اگر محصول Variant دارد، انتخاب Variant اجباری است
        if ((int) $product['has_variants'] === 1) {
            if ($variantId === null) {
                throw new \RuntimeException('لطفاً یکی از گزینه‌های محصول را انتخاب کنید.');
            }

            $variant = Database::fetch(
                'SELECT id, stock, is_active FROM product_variants WHERE id = ? AND product_id = ? LIMIT 1',
                [$variantId, $productId]
            );

            if ($variant === null || (int) $variant['is_active'] === 0) {
                throw new \RuntimeException('گزینه انتخاب‌شده معتبر نیست.');
            }

            $available = (int) $variant['stock'];
        } else {
            // محصول ساده هرگز نباید variant بگیرد
            $variantId = null;
            $available = (int) $product['stock'];
        }

        if ($available <= 0) {
            throw new \RuntimeException('موجودی این کالا تمام شده است.');
        }

        $cartId   = self::currentId();
        $existing = self::findLine($cartId, $productId, $variantId);
        $newQty   = ($existing !== null ? (int) $existing['quantity'] : 0) + $quantity;

        if ($newQty > $available) {
            throw new \RuntimeException(
                'فقط ' . fa_digits((string) $available) . ' عدد از این کالا موجود است.'
            );
        }

        $newQty = min($newQty, self::MAX_QTY);

        if ($existing !== null) {
            Database::update('cart_items', ['quantity' => $newQty], 'id = ?', [$existing['id']]);
        } else {
            Database::insert('cart_items', [
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity'   => $newQty,
            ]);
        }

        self::touch($cartId);
    }

    /**
     * تغییر تعداد یک قلم
     */
    public static function updateQuantity(int $itemId, int $quantity): void
    {
        $cartId = self::currentId(false);
        if ($cartId === null) {
            return;
        }

        if ($quantity < 1) {
            self::remove($itemId);
            return;
        }

        $item = Database::fetch(
            'SELECT ci.id, COALESCE(v.stock, p.stock) AS available
               FROM cart_items ci
               JOIN products p ON p.id = ci.product_id
               LEFT JOIN product_variants v ON v.id = ci.variant_id
              WHERE ci.id = ? AND ci.cart_id = ?
              LIMIT 1',
            [$itemId, $cartId]
        );

        if ($item === null) {
            return;
        }

        $quantity = min($quantity, self::MAX_QTY, max(1, (int) $item['available']));

        Database::update('cart_items', ['quantity' => $quantity], 'id = ?', [$itemId]);
        self::touch($cartId);
    }

    /**
     * حذف یک قلم — فقط از سبد خودِ کاربر
     */
    public static function remove(int $itemId): void
    {
        $cartId = self::currentId(false);
        if ($cartId === null) {
            return;
        }

        Database::delete('cart_items', 'id = ? AND cart_id = ?', [$itemId, $cartId]);
        self::touch($cartId);
    }

    public static function clear(): void
    {
        $cartId = self::currentId(false);
        if ($cartId !== null) {
            Database::delete('cart_items', 'cart_id = ?', [$cartId]);
        }
    }

    /**
     * ادغام سبد مهمان با سبد کاربر پس از ورود.
     * در Auth::login صدا زده می‌شود.
     */
    public static function mergeGuestCartInto(int $userId): void
    {
        $token = $_COOKIE[self::COOKIE] ?? null;

        if (!is_string($token) || !preg_match('/^[a-f0-9]{40}$/', $token)) {
            return;
        }

        $guestCart = Database::fetch('SELECT id FROM carts WHERE session_token = ? LIMIT 1', [$token]);
        if ($guestCart === null) {
            return;
        }

        $guestCartId = (int) $guestCart['id'];

        $userCart = Database::fetch('SELECT id FROM carts WHERE user_id = ? LIMIT 1', [$userId]);

        if ($userCart === null) {
            // کاربر سبدی ندارد — همان سبد مهمان به نامش می‌شود
            Database::update('carts', ['user_id' => $userId, 'session_token' => null],
                             'id = ?', [$guestCartId]);
            self::forgetCookie();
            return;
        }

        $userCartId = (int) $userCart['id'];

        // اقلام سبد مهمان به سبد کاربر منتقل می‌شوند
        foreach (Database::fetchAll('SELECT * FROM cart_items WHERE cart_id = ?', [$guestCartId]) as $item) {
            $existing = self::findLine($userCartId, (int) $item['product_id'],
                                       $item['variant_id'] !== null ? (int) $item['variant_id'] : null);

            if ($existing !== null) {
                $merged = min(self::MAX_QTY, (int) $existing['quantity'] + (int) $item['quantity']);
                Database::update('cart_items', ['quantity' => $merged], 'id = ?', [$existing['id']]);
            } else {
                Database::update('cart_items', ['cart_id' => $userCartId], 'id = ?', [$item['id']]);
            }
        }

        Database::delete('carts', 'id = ?', [$guestCartId]);
        self::forgetCookie();
    }

    // ------------------------------------------------------------------

    /**
     * پیدا کردن یک ردیف سبد.
     * چون variant_id می‌تواند NULL باشد و «NULL = NULL» در SQL نادرست است،
     * از IS NULL استفاده می‌شود.
     */
    private static function findLine(int $cartId, int $productId, ?int $variantId): ?array
    {
        if ($variantId === null) {
            return Database::fetch(
                'SELECT id, quantity FROM cart_items
                  WHERE cart_id = ? AND product_id = ? AND variant_id IS NULL LIMIT 1',
                [$cartId, $productId]
            );
        }

        return Database::fetch(
            'SELECT id, quantity FROM cart_items
              WHERE cart_id = ? AND product_id = ? AND variant_id = ? LIMIT 1',
            [$cartId, $productId, $variantId]
        );
    }

    private static function touch(int $cartId): void
    {
        Database::update('carts', ['updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$cartId]);
    }

    private static function setCookie(string $token): void
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie(self::COOKIE, $token, [
            'expires'  => time() + (self::COOKIE_DAYS * 86400),
            'path'     => base_path_uri() . '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $https,
        ]);

        // تا درخواست بعدی هم در همین اجرا قابل خواندن باشد
        $_COOKIE[self::COOKIE] = $token;
    }

    private static function forgetCookie(): void
    {
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path'    => base_path_uri() . '/',
        ]);

        unset($_COOKIE[self::COOKIE]);
    }
}
