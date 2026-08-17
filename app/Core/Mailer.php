<?php

namespace App\Core;

use App\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * ارسال ایمیل با Gmail SMTP از طریق PHPMailer.
 *
 * ⚠️ قانون مهم: ارسال ایمیل هرگز نباید جریان اصلی سایت را متوقف کند.
 * اگر ایمیل ارسال نشد (اینترنت قطع بود، رمز اشتباه بود و ...)، خطا در
 * فایل لاگ ثبت می‌شود ولی سفارش مشتری ثبت‌شده باقی می‌ماند.
 *
 * تنظیمات SMTP از جدول settings خوانده می‌شود، پس مدیر می‌تواند بدون
 * دست زدن به کد، آن‌ها را از پنل عوض کند.
 */
class Mailer
{
    private static bool $loaded = false;

    /**
     * ارسال ایمیل.
     *
     * @return bool true اگر ارسال موفق بود
     */
    public static function send(string $to, string $subject, string $htmlBody, string $toName = ''): bool
    {
        $host     = (string) Setting::get('smtp_host', '');
        $username = (string) Setting::get('smtp_username', '');
        $password = (string) Setting::get('smtp_password', '');

        if ($host === '' || $username === '' || $password === '') {
            // در حالت توسعه، به‌جای ارسال، ایمیل در پوشه logs/mail ذخیره می‌شود
            // تا بتوان بدون تنظیم Gmail، محتوای ایمیل‌ها را بررسی کرد.
            if (config('debug')) {
                return self::saveForPreview($to, $subject, $htmlBody);
            }

            error_log('Mailer: تنظیمات SMTP کامل نیست، ایمیل ارسال نشد. گیرنده: ' . $to);
            return false;
        }

        self::loadLibrary();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->Port       = (int) Setting::get('smtp_port', 587);
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';
            $mail->Timeout    = 20;

            $mail->SMTPSecure = Setting::get('smtp_secure', 'tls') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $fromEmail = (string) Setting::get('smtp_from_email', $username);
            $fromName  = (string) Setting::get('smtp_from_name', Setting::get('site_name', 'ایتکو'));

            $mail->setFrom($fromEmail !== '' ? $fromEmail : $username, $fromName);
            $mail->addAddress($to, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            // نسخه متنی برای ایمیل‌خوان‌هایی که HTML نمایش نمی‌دهند
            $mail->AltBody = trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8'));

            $mail->send();
            return true;
        } catch (MailException | \Throwable $e) {
            error_log('Mailer: ارسال ناموفق به ' . $to . ' — ' . $mail->ErrorInfo . ' | ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ارسال ایمیل با استفاده از یک قالب در app/Views/emails
     */
    public static function sendTemplate(
        string $to,
        string $subject,
        string $template,
        array $data = [],
        string $toName = ''
    ): bool {
        $data['subject'] = $subject;

        $body = View::capture('emails/layout', [
            'subject' => $subject,
            'content' => View::capture('emails/' . $template, $data),
        ]);

        return self::send($to, $subject, $body, $toName);
    }

    /**
     * ذخیره ایمیل روی دیسک به‌جای ارسال — فقط در حالت توسعه.
     *
     * وقتی هنوز اطلاعات Gmail را وارد نکرده‌اید، ایمیل‌ها در پوشه
     * logs/mail ذخیره می‌شوند و می‌توانید آن‌ها را با مرورگر باز کنید
     * تا ببینید مشتری دقیقاً چه چیزی دریافت می‌کند.
     */
    private static function saveForPreview(string $to, string $subject, string $htmlBody): bool
    {
        $dir = ROOT_PATH . '/logs/mail';

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $file = $dir . '/' . date('Ymd-His') . '-' . substr(md5($to . $subject . microtime()), 0, 6) . '.html';

        $header = '<!-- گیرنده: ' . $to . " | موضوع: " . $subject . " -->\n";

        return file_put_contents($file, $header . $htmlBody) !== false;
    }

    /**
     * بارگذاری فایل‌های PHPMailer (نسخه بدون Composer)
     */
    private static function loadLibrary(): void
    {
        if (self::$loaded) {
            return;
        }

        $dir = ROOT_PATH . '/libs/PHPMailer';

        require_once $dir . '/Exception.php';
        require_once $dir . '/PHPMailer.php';
        require_once $dir . '/SMTP.php';

        self::$loaded = true;
    }
}
