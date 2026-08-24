<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Post;

/**
 * بلاگ / مطالب گیمینگ (بخش عمومی سایت).
 */
class BlogController extends Controller
{
    private const PER_PAGE = 9;

    public function index(): void
    {
        $total     = Post::publishedCount();
        $paginator = new Paginator($total, self::PER_PAGE, $this->intInput('page', 1));
        $posts     = Post::published($paginator->limit(), $paginator->offset());

        $this->view('site/blog/index', [
            'title'     => 'مطالب گیمینگ | ایتکو',
            'posts'     => $posts,
            'paginator' => $paginator,
            'total'     => $total,
        ], 'site');
    }

    public function show(string $slug): void
    {
        $post = Post::publishedBySlug($slug);
        if ($post === null) {
            $this->notFound('مطلب مورد نظر پیدا نشد');
        }

        Post::incrementViews((int) $post['id']);

        $this->view('site/blog/show', [
            'title'  => $post['title'] . ' | مطالب ایتکو',
            'post'   => $post,
            'latest' => Post::latest(4),
        ], 'site');
    }
}
