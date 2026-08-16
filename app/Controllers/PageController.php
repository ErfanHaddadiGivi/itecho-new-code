<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * صفحات ثابت: قوانین و مقررات، حریم خصوصی، درباره ما، تماس با ما.
 * این صفحات برای دریافت نماد اعتماد الکترونیکی (اینماد) لازم هستند.
 */
class PageController extends Controller
{
    public function show(string $slug): void
    {
        $page = Database::fetch(
            'SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1',
            [$slug]
        );

        if ($page === null) {
            $this->notFound('صفحه مورد نظر پیدا نشد');
        }

        $this->view('site/page', [
            'title' => $page['title'],
            'page'  => $page,
        ], 'site');
    }
}
