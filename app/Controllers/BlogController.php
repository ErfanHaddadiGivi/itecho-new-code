<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Post;
use App\Models\Setting;

/**
 * مجله آیتکو (بخش عمومی سایت) — فهرست و نمایش مطلب، همراه با متادیتای سئو.
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
            'title'           => 'مجله آیتکو | ' . Setting::get('site_name', 'ایتکو'),
            'metaDescription' => 'مقاله‌ها، راهنمای خرید و اخبار دنیای گیمینگ و تکنولوژی در مجله آیتکو.',
            'posts'           => $posts,
            'paginator'       => $paginator,
            'total'           => $total,
        ], 'site');
    }

    public function show(string $slug): void
    {
        $post = Post::publishedBySlug($slug);
        if ($post === null) {
            $this->notFound('مطلب مورد نظر پیدا نشد');
        }

        Post::incrementViews((int) $post['id']);

        $siteName = (string) Setting::get('site_name', 'ایتکو');

        // عنوان و توضیح سئو: اگر مدیر مقدار سئو داده از آن، وگرنه از خود مطلب
        $seoTitle = $this->firstNonEmpty(
            (string) ($post['meta_title'] ?? ''),
            $post['title'] . ' | مجله آیتکو'
        );
        $description = $this->firstNonEmpty(
            (string) ($post['meta_description'] ?? ''),
            (string) ($post['excerpt'] ?? ''),
            mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $post['content']))), 0, 160)
        );

        $coverPath = !empty($post['cover_image']) ? url('uploads/posts/' . $post['cover_image']) : '';
        $coverAbs  = $coverPath !== '' ? $this->origin() . $coverPath : '';

        $this->view('site/blog/show', [
            'title'           => $seoTitle,
            'metaDescription' => $description,
            'ogType'          => 'article',
            'ogImage'         => $coverPath,
            'jsonLd'          => $this->articleJsonLd($post, $description, $coverAbs, $siteName),
            'post'            => $post,
            'latest'          => Post::latest(4),
        ], 'site');
    }

    // ------------------------------------------------------------------

    /** داده‌ی ساختاریافته‌ی مقاله برای گوگل (schema.org Article) */
    private function articleJsonLd(array $post, string $description, string $imageAbs, string $siteName): string
    {
        $data = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $post['title'],
            'description'   => $description,
            'datePublished' => !empty($post['published_at']) ? date('c', strtotime($post['published_at'])) : null,
            'dateModified'  => !empty($post['updated_at']) ? date('c', strtotime($post['updated_at'])) : null,
            'author'        => ['@type' => 'Organization', 'name' => $siteName],
            'publisher'     => ['@type' => 'Organization', 'name' => $siteName],
        ];
        if ($imageAbs !== '') {
            $data['image'] = $imageAbs;
        }

        return json_encode(
            array_filter($data, static fn ($v) => $v !== null),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function firstNonEmpty(string ...$values): string
    {
        foreach ($values as $v) {
            if (trim($v) !== '') {
                return trim($v);
            }
        }
        return '';
    }

    private function origin(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        return $host !== '' ? $scheme . '://' . $host : '';
    }
}
