# نقد فرانت UI/UX بامرو و Action اصلاحی

## نتیجه

فرانت از نظر visual direction، RTL، فونت فارسی و اتصال کاتالوگ به WooCommerce مسیر خوبی دارد، اما پیش از این نقد، چند ناهماهنگی مهم بین promise محصول «موبایل‌محور و بدون ایمیل» و surfaceهای frontend باقی مانده بود. مهم‌ترین مورد، نمایش ورودی email در مشاوره و contact form و امکان ارسال فرم به صف پیامک بدون mobile معتبر بود.

## فهرست مشکلات

| شدت | مشکل | اثر | وضعیت |
|---|---|---|---|
| Critical | فرم Contact هنوز در شاخهٔ قبلی ورودی email و مسیر Contact Form 7 داشت | نقض مستقیم سیاست mobile-only و احتمال برگشت به mail transport | اصلاح شد |
| Critical | فرم مشاورهٔ رنگ ورودی email اختیاری داشت | کاربر تصور می‌کرد email بخشی از قرارداد ارتباطی است | اصلاح شد |
| High | contact form در UI شماره موبایل را اجباری نشان نمی‌داد و handler نیز فقط name/message را validate می‌کرد | payload بدون mobile وارد outbox می‌شد و worker بعداً شکست می‌خورد | اصلاح شد |
| High | validation سرور و validation مرورگر برای موبایل یک قرارداد واحد نداشتند | خطای دیرهنگام و تجربهٔ نامطمئن در شماره‌های فارسی/ایرانی | normalize سمت سرور اضافه شد |
| High | شبکه‌های اجتماعی header شامل لینک‌های عمومی `instagram.com/` و `t.me/` بودند | حس placeholder و خروجی غیرواقعی در production | باقی‌مانده؛ باید با URL معتبر برند یا حذف کامل جایگزین شود |
| Medium | بعضی routeهای fallback مثل `/gallery/` و `/dealers/` ممکن است بدون page واقعی بمانند | dead-end و کاهش اعتماد | نیازمند بررسی اطلاعات واقعی کسب‌وکار |
| Medium | CSS جدید در چند خط بسیار فشرده و در کنار CSS legacy قرار گرفته است | نگهداری، code review و تشخیص conflict دشوارتر می‌شود | باقی‌مانده؛ refactor جداگانه پیشنهاد می‌شود |
| Medium | منوی موبایل از transform فیزیکی `translateX` استفاده می‌کند | در بعضی حالت‌های RTL/LTR و zoom ممکن است حرکت از سمت نادرست یا focus خارج viewport رخ دهد | نیازمند تست browser و اصلاح focus trap |
| Medium | JS در چند مسیر به `location.reload()` و jQuery وابسته است | تجربهٔ کندتر، وابستگی بیشتر و افت progressive enhancement | نیازمند بازطراحی incremental |
| Medium | hero به‌جای asset محصول/برند از CSS art استفاده می‌کند | در برخی بازارها حس campaign واقعی کمتر و حس generic template بیشتر | asset برند واقعی لازم است |
| Low | fallback empty state متن فنی «provisioning اجرا نشده» دارد | برای مشتری نهایی مناسب نیست و وضعیت داخلی را آشکار می‌کند | باید به متن تجاری/پشتیبانی تبدیل شود |
| Low | معیارهای عملیاتی مانند screenshot، Lighthouse، axe و keyboard در runtime قابل‌اثبات نشده‌اند | کیفیت بصری و accessibility هنوز partially verified است | نیازمند staging/browser واقعی |

## Action انجام‌شده: Mobile-only Contact Surface Hardening

### دامنه

فقط surfaceهای تماس و مشاوره اصلاح شدند تا با قرارداد پیامک و mobile-only هم‌راستا شوند.

### تغییرات

- حذف کامل ورودی email از `contact.php`.
- حذف fallback نمایش Contact Form 7 از contact surface؛ فرم native امن و قابل‌کنترل مسیر اصلی شد.
- اجباری‌شدن شماره موبایل با `required`، `inputmode="tel"` و `autocomplete="tel"`.
- اضافه‌شدن help text شفاف دربارهٔ پاسخ‌گویی پیامکی.
- حذف ورودی email از shortcode مشاورهٔ رنگ در `functions.php`.
- normalize و validation سمت سرور برای شمارهٔ تماس و مشاوره.
- جلوگیری از enqueue شدن درخواست بدون mobile معتبر.
- افزودن style خوانا برای help text، فرم و focus سطح تماس.

## راستی‌آزمایی Action

| آزمون | نتیجه |
|---|---|
| PHP lint برای `contact.php` | PASS |
| PHP lint برای `functions.php` | PASS |
| جست‌وجوی `c-email`، `mailto:`، Contact Form 7 و email input در contact | PASS؛ موردی یافت نشد |
| وجود `required` و `autocomplete="tel"` در contact | PASS |
| وجود normalize و queue در handlerها | PASS |
| اجرای browser واقعی و axe/Lighthouse | هنوز اجرا نشده؛ Docker و staging browser در این sandbox موجود نیست |

## پیشنهادهای مرحلهٔ بعد

۱. لینک‌های اجتماعی و routeهای fallback را با دادهٔ واقعی کسب‌وکار جایگزین یا حذف کنید.

۲. منوی موبایل را با focus trap، Escape، restore focus و logical transform بازنویسی کنید.

۳. CSS را به فایل‌های component-level با tokenهای semantic تفکیک و duplicateهای legacy را حذف کنید.

۴. در staging واقعی، ماتریس viewport، keyboard، zoom 200٪، screen reader، axe و Lighthouse اجرا شود.

۵. برای hero و artwork از assetهای برند واقعی با ابعاد ثابت و preload کنترل‌شده استفاده شود.

## وضعیت نهایی

Action فعلی **کامل و local-verified** است. کل فرانت هنوز برای تأیید نهایی production به browser/staging واقعی، accessibility audit و تأیید URLهای تجاری نیاز دارد.
