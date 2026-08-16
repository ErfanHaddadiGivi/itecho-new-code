<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

/**
 * داشبورد پنل مدیریت — خلاصه وضعیت فروشگاه در یک نگاه.
 */
class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $stats = [
            'products'        => (int) Database::fetchValue('SELECT COUNT(*) FROM products'),
            'active_products' => (int) Database::fetchValue('SELECT COUNT(*) FROM products WHERE is_active = 1'),
            'categories'      => (int) Database::fetchValue('SELECT COUNT(*) FROM categories'),
            'brands'          => (int) Database::fetchValue('SELECT COUNT(*) FROM brands'),
            'customers'       => (int) Database::fetchValue("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
            'orders'          => (int) Database::fetchValue('SELECT COUNT(*) FROM orders'),
        ];

        // کارهایی که منتظر رسیدگی ادمین هستند
        $todo = [
            'awaiting_shipping_cost' => (int) Database::fetchValue(
                "SELECT COUNT(*) FROM orders WHERE shipping_state = 'awaiting_cost'"
            ),
            'pending_reviews' => (int) Database::fetchValue(
                "SELECT COUNT(*) FROM reviews WHERE status = 'pending'"
            ),
            'new_orders' => (int) Database::fetchValue(
                "SELECT COUNT(*) FROM orders WHERE status = 'paid'"
            ),
            'unread_messages' => (int) Database::fetchValue(
                'SELECT COUNT(*) FROM contact_messages WHERE is_read = 0'
            ),
        ];

        // فروش تاییدشده (فقط سفارش‌هایی که واقعاً پرداخت شده‌اند)
        $revenue = (int) Database::fetchValue(
            "SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE payment_status = 'paid'"
        );

        $recentOrders = Database::fetchAll(
            "SELECT o.id, o.order_number, o.status, o.grand_total, o.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer
               FROM orders o
               JOIN users u ON u.id = o.user_id
              ORDER BY o.created_at DESC
              LIMIT 8"
        );

        // کالاهای رو به اتمام — برای یادآوری سفارش مجدد
        $lowStock = Database::fetchAll(
            'SELECT id, name, stock FROM products
              WHERE is_active = 1 AND stock <= 3
              ORDER BY stock, name
              LIMIT 8'
        );

        $this->view('admin/dashboard', [
            'title'        => 'داشبورد',
            'stats'        => $stats,
            'todo'         => $todo,
            'revenue'      => $revenue,
            'recentOrders' => $recentOrders,
            'lowStock'     => $lowStock,
        ], 'admin');
    }
}
