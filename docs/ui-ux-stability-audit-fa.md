# ممیزی جامع UI/UX و پایداری رابط کاربری بامرو

**تاریخ ارزیابی:** ۱۷ سپتامبر ۲۰۲۶

**دامنه:** تم کلاسیک WordPress، قالب‌های WooCommerce، CSS، JavaScript، فونت فارسی، RTL، responsive و preview موجود

**نتیجه کلی:** `PARTIAL — نیازمند hardening قبل از Go-Live`

## خلاصه مدیریتی

رابط فعلی از نظر جهت راست‌به‌چپ، ساختار فروشگاهی، رنگ‌بندی پایه، کارت محصول، header sticky، focus-visible و وجود چند breakpoint، نقطه شروع مناسبی دارد. با این حال، «فونت فارسی راست‌چین ریسپانسیو کامل، پایدار و جامع» هنوز قابل تأیید نیست.

مهم‌ترین علت‌ها عبارت‌اند از وابستگی runtime به Google Fonts و Font Awesome، تکرار بارگذاری Vazirmatn در `@import` و `wp_enqueue_style`، نبود self-hosting و preload فونت، وجود دو سیستم token ناسازگار در CSS، وجود `float: left` در فایل RTL، نبود تست واقعی در viewportهای هدف، وجود CTA ثابت موبایل بدون بررسی تداخل با WhatsApp یا checkout، و وابستگی کامل تست‌های visual/accessibility به یک محیط WordPress واقعی.

> **نتیجه عملی:** UI برای MVP دیداری مناسب است، اما برای پایداری production باید ابتدا فونت و asset pipeline، tokenهای CSS، RTL logical properties، responsive states و تست دسترسی‌پذیری تثبیت شوند.

## امتیازبندی پیشنهادی فعلی

| حوزه | امتیاز تقریبی | وضعیت |
|---|---:|---|
| هویت بصری و hierarchy | ۷ از ۱۰ | مناسب MVP، نیازمند یکپارچه‌سازی tokenها |
| فونت فارسی و خوانایی | ۵ از ۱۰ | Vazirmatn مناسب است، اما pipeline پایدار نیست |
| RTL فنی | ۶ از ۱۰ | بخش زیادی logical است، اما استثناهای فیزیکی باقی مانده‌اند |
| responsive layout | ۵ از ۱۰ | breakpoint وجود دارد، اما matrix تست و state کامل نیست |
| دسترسی‌پذیری | ۵ از ۱۰ | focus و reduced motion پایه وجود دارد؛ axe/Lighthouse اجرا نشده |
| WooCommerce UX | ۶ از ۱۰ | مسیر اصلی وجود دارد؛ edge stateها ناقص‌اند |
| performance UI | ۴ از ۱۰ | assetهای خارجی و نبود LCP evidence ریسک اصلی‌اند |
| پایداری production | ۴ از ۱۰ | به staging و تست browser واقعی نیاز دارد |

این امتیازها اندازه‌گیری آزمایشگاهی نیستند؛ اولویت‌بندی مهندسی بر اساس ممیزی ایستا هستند و نباید جایگزین Lighthouse، axe-core یا تست دستگاه واقعی شوند.

## ۱. فونت فارسی

### وضعیت فعلی

تم از Vazirmatn استفاده می‌کند. فونت در دو مسیر بارگذاری می‌شود: در ابتدای `style.css` با `@import` و در `functions.php` با `wp_enqueue_style`. این کار درخواست و dependency تکراری ایجاد می‌کند و کنترل ترتیب بارگذاری را دشوارتر می‌سازد.

کدهای کامنت‌شده از IRANSans صحبت می‌کنند، اما implementation واقعی Vazirmatn است. این اختلاف مستندات، در نگهداری آینده باعث انتخاب اشتباه asset می‌شود.

فونت از Google Fonts و به‌صورت remote دریافت می‌شود. بنابراین پایداری به DNS، فیلترینگ، latency و دسترس‌پذیری سرویس خارجی وابسته است. فایل‌های `woff2` محلی، `font-display: swap` در یک `@font-face` خود پروژه، preload کنترل‌شده و subset فارسی/لاتین در repository وجود ندارد.

### پیامدها

در اتصال کند یا مسدود، layout ممکن است با fallback تغییر کند. این وضعیت می‌تواند باعث تغییر ارتفاع heading، جابه‌جایی CTA، تغییر CLS و شکستن کارت‌های محصول شود. همچنین بارگذاری Google Fonts و Font Awesome می‌تواند از نظر privacy و performance برای مخاطب ایرانی نامطمئن باشد.

### راهکار پیشنهادی

Vazirmatn Variable را از منبع مجاز تهیه و در `wp-content/themes/bamero/assets/fonts/` قرار دهید. فقط فرمت WOFF2 را نگه دارید و برای متن فارسی، Latin و اعداد در صورت نیاز subset جدا بسازید. سپس این الگو را در یک stylesheet محلی قرار دهید:

```css
@font-face {
  font-family: "Vazirmatn";
  src: url("../fonts/Vazirmatn[wght].woff2") format("woff2");
  font-style: normal;
  font-weight: 300 800;
  font-display: swap;
}
```

`@import` از `style.css` حذف شود. enqueue فونت خارجی نیز حذف شود. به‌جای آن stylesheet محلی با version مبتنی بر `filemtime()` enqueue شود. برای heading و body از Vazirmatn و برای RAL، SKU، VOC و اعداد فنی از IBM Plex Mono یا فونت monospace محلی استفاده شود.

در صفحات مهم، preload فقط برای یک فایل فونت اصلی و فقط زمانی انجام شود که واقعاً در بالای صفحه استفاده می‌شود. preload بیش از یک یا دو فایل فونت می‌تواند عملکرد را بدتر کند.

## ۲. راست‌چین و RTL

### نقاط مثبت

`direction: rtl`، `unicode-bidi`، `inset-inline-*`، `margin-inline-*` و `padding-inline-*` در بخش قابل‌توجهی از تم استفاده شده‌اند. focus-visible، header sticky و ساختار navigation نیز برای RTL پایه مناسبی دارند.

### نقص‌های قطعی

در `woocommerce-rtl.css`، عبارت `float: left` وجود دارد. این برخلاف RTL-first و logical-properties است و در layoutهای متفاوت می‌تواند رفتار شکننده ایجاد کند. باید با `float: inline-start` یا ترجیحاً layout flex/grid جایگزین شود.

فایل‌های `style.css` و `woocommerce.css` دو زبان token متفاوت دارند. `variables.css` متغیرهایی مانند `--primary`، `--dark` و `--gray-100` تعریف می‌کند؛ در حالی که `woocommerce.css` از نام‌هایی مانند `--container-max-width`، `--spacing-md`، `--color-surface` و `--font-size-base` استفاده می‌کند. اگر این متغیرها در فایل دیگری تعریف نشده باشند، بخش بزرگی از CSS به مقدار معتبر fallback نمی‌رسد و layout در runtime غیرقابل‌پیش‌بینی می‌شود.

در بخش‌هایی `text-align: right` وجود دارد. این خطا به‌اندازه `left/right` در spacing خطرناک نیست، اما برای RTL پایدار بهتر است `text-align: start` استفاده شود، مگر در مواردی که alignment بصری عمداً ثابت است.

### راهکار پیشنهادی

یک فایل token واحد ایجاد شود و تمام CSS به همان نام‌ها مهاجرت کند. lint ایستا باید هر `var(--x)` را با فهرست تعریف‌شده مقایسه کند و متغیر ناشناخته را fail کند. همه `float`های RTL حذف شوند. برای ترتیب visual از `flex-direction` و `order` استفاده شود. برای متن، `text-align: start` و برای inline spacing از `margin-inline` استفاده شود.

## ۳. Responsive و پایداری layout

### وضعیت فعلی

breakpointهای اصلی ۵۷۵، ۷۶۷، ۹۹۱ و ۱۲۰۰ پیکسل هستند. header موبایل، navigation کشویی، shop layout تک‌ستونه و footer چندستونه وجود دارد. استفاده از `clamp()` در hero نیز مناسب است.

### نقص‌ها

استاندارد فایل جدید viewportهای ۳۲۰، ۳۷۵، ۷۶۸، ۱۰۲۴ و ۱۴۴۰ پیکسل را الزام می‌کند، اما تست تصویری یا browser matrix برای این اندازه‌ها ثبت نشده است. breakpointهای فعلی دقیقاً با همه این نقاط منطبق نیستند؛ به‌خصوص مرزهای ۷۶۷/۷۶۸ و ۹۹۱/۱۰۲۴ باید با تست واقعی بررسی شوند.

کلاس `.single_add_to_cart_button` در موبایل fixed شده است. این دکمه ممکن است با WhatsApp floating button، cookie notice، keyboard مجازی یا دکمه‌های checkout هم‌پوشانی داشته باشد. برای آن باید `padding-block-end` امن در صفحه، safe-area برای iOS و منطق مخفی‌سازی هنگام بازبودن keyboard یا modal تعریف شود.

جدول مشخصات فنی در موبایل با `display: grid` روی `tr` تغییر شکل می‌دهد، اما هنوز به accordion card واقعی تبدیل نشده است. در جدول‌های طولانی، خوانایی و ترتیب heading باید با screen reader بررسی شود.

کارت محصول برای عنوان‌های طولانی از line clamp استفاده می‌کند، اما ارتفاع قیمت، badge، category و CTA باید در محصولات با نام فارسی بلند، اعداد فارسی، قیمت تخفیفی و نبود تصویر تست شود.

### راهکار پیشنهادی

یک ماتریس Playwright یا تست دستی تعریف شود: ۳۲۰×۶۸۰، ۳۷۵×۸۱۲، ۷۶۸×۱۰۲۴، ۱۰۲۴×۷۶۸ و ۱۴۴۰×۹۰۰. برای هر viewport مسیر خانه، فروشگاه، محصول، سبد و checkout اجرا شود. معیار قبولی شامل نبود overflow افقی، نبود overlap، قابل‌مشاهده‌بودن CTA، حفظ ترتیب RTL و عدم قطع عنوان/قیمت است.

برای موبایل از `env(safe-area-inset-bottom)` استفاده شود. CTA ثابت باید فقط در صفحه محصول فعال باشد و در checkout یا زمانی که CTA اصلی خارج از viewport نیست، غیرفعال شود.

## ۴. دسترسی‌پذیری و فارسی

### نقاط مثبت

skip link، focus-visible، `aria-label` برای بخشی از لینک‌ها، alt محصول و `prefers-reduced-motion` وجود دارد. جدول فنی جدید caption و scope دارد.

### کمبودها

هیچ نتیجه واقعی axe-core یا Lighthouse Accessibility ثبت نشده است. نسبت کنتراست رنگ‌های primary، secondary، gray و متن روی badge باید با ابزار اندازه‌گیری بررسی شود؛ چشم انسان برای PASS کافی نیست.

آیکون‌های Font Awesome به asset خارجی وابسته‌اند و برای همه موارد، نام فارسی قابل‌تشخیص یا `aria-hidden` صریح اثبات نشده است. دکمه‌های icon-only باید accessible name داشته باشند و آیکون تزئینی باید `aria-hidden="true"` شود.

فرم‌های checkout، مشاوره و search باید label صریح، خطای متصل به input با `aria-describedby`، focus به اولین خطا و پیام قابل‌فهم فارسی داشته باشند. placeholder نباید جایگزین label شود.

بزرگ‌نمایی ۲۰۰٪، high zoom و forced colors تست نشده است. فقط کاهش motion پیاده‌سازی شده و کافی نیست.

## ۵. WooCommerce UX

مسیر پایه فروش محصول وجود دارد، اما برای یک فروشگاه رنگ و چسب این stateها لازم هستند: موجودی کم، out-of-stock، محصول بدون تصویر، رنگ بدون کد معتبر، محصول با VOC بالا، لینک MSDS خراب، خطای افزودن به سبد، تغییر quantity، coupon نامعتبر، روش ارسال ناموجود و خطای درگاه.

در صفحه محصول، gallery واقعی قوطی/تیوپ و تصویر سطح اجرا وجود ندارد. ویدیوی ۳۰ ثانیه‌ای با زیرنویس فارسی و autoplay خاموش نیز ارائه نشده است. این موارد باید با asset واقعی و اجازه مالک تکمیل شوند؛ placeholder یا تصویر تزئینی نباید به‌عنوان evidence محصول صنعتی استفاده شود.

Cross-sell باید بر اساس مکمل فنی مانند تینر، بتونه، ماسک و ابزار باشد. محصولات تصادفی تجربه B2B را ضعیف می‌کنند.

## ۶. معماری CSS و پایداری فنی

تم سه لایه CSS دارد: `style.css`، `woocommerce.css` و `woocommerce-rtl.css`. این تفکیک قابل‌قبول است، اما tokenها و نام‌گذاری‌ها یکپارچه نیستند. برخی styleها با `!important` زیاد در WooCommerce override شده‌اند و در نسخه‌های بعدی WooCommerce ممکن است شکننده شوند.

`style.css` هنوز `@import` فونت دارد و فایل `woocommerce.css` حجم زیادی از قواعد عمومی و tokenهای متفاوت را نگه می‌دارد. پیشنهاد می‌شود CSS به لایه‌های زیر تقسیم شود:

```text
01-tokens.css
02-reset-and-base.css
03-layout.css
04-components.css
05-woocommerce.css
06-rtl-overrides.css
07-utilities.css
```

هر لایه باید فقط یک مسئولیت داشته باشد. RTL بهتر است در همان component با logical properties نوشته شود و فایل override صرفاً برای اختلاف‌های اجتناب‌ناپذیر باقی بماند.

## ۷. performance و پایداری asset

فونت خارجی، Font Awesome خارجی، نبود asset pipeline محلی، نبود evidence برای WebP/AVIF و نبود preload تصویر LCP ریسک اصلی‌اند. تصاویر محصول باید `width` و `height` صریح داشته باشند تا CLS کم شود. تصویر اصلی product باید preload شود، ولی تصاویر پایین صفحه باید lazy باشند.

انیمیشن hover با `transform` در desktop مناسب است، اما روی touch device باید غیرفعال یا محدود شود. `backdrop-filter` نیز باید fallback مناسب برای مرورگرهای قدیمی داشته باشد.

## ۸. بهبودهای پیشنهادی بر اساس اولویت

### P0 — قبل از staging

فونت و Font Awesome را self-host کنید. `@import` را حذف کنید. tokenهای CSS را یکی کنید. `float: left` را حذف کنید. تست PHP lint و static CSS variable lint را در CI قرار دهید. تصاویر و assetهای product را واقعی و دارای ابعاد صریح کنید.

### P1 — پیش از beta

Playwright را در پنج viewport اجرا کنید. axe-core و Lighthouse را اضافه کنید. checkout و فرم‌ها را با keyboard و screen reader تست کنید. fixed CTA موبایل را با safe-area و WhatsApp هماهنگ کنید. همه error/empty/loading stateها را طراحی کنید.

### P2 — پیش از Go-Live

LCP کمتر از ۱٫۵ ثانیه، CLS کمتر از ۰٫۰۵، TBT کمتر از ۱۵۰ میلی‌ثانیه و Accessibility حداقل ۹۵ را با اجرای واقعی ثبت کنید. ده جدول فنی را با دیتاشیت رسمی تطبیق دهید. ΔE رنگ‌های پرفروش را با نمونه فیزیکی و color-managed workflow اندازه بگیرید. MSDS و ویدیوی زیرنویس‌دار واقعی اضافه کنید.

## ۹. معیار پذیرش پیشنهادی

| معیار | حد قبولی |
|---|---|
| فونت | بدون درخواست remote برای font در production و بدون FOUT طولانی |
| RTL | صفر `left/right` در spacing و zero overflow افقی در پنج viewport |
| Accessibility | Lighthouse حداقل ۹۵ و axe بدون violation بحرانی |
| Keyboard | تمام مسیرهای خرید بدون ماوس کامل شوند |
| Responsive | بدون overlap و clipping در ۳۲۰ تا ۱۴۴۰ پیکسل |
| Performance | LCP < 1.5s، CLS < 0.05، TBT < 150ms |
| رنگ | کد RAL/NCS برای محصول و ΔE < 3 فقط با نمونه فیزیکی معتبر |
| WooCommerce | add-to-cart، cart refresh، checkout validation و خطاها در staging PASS |
| پایداری | بدون خطای console و بدون درخواست asset شکست‌خورده |

## نتیجه نهایی

تم فعلی از نظر طراحی پایه قابل استفاده است، اما ادعای «فونت فارسی راست‌چین ریسپانسیو کامل و پایدار» هنوز قابل صدور نیست. بیشترین بازده فنی از سه اصلاح حاصل می‌شود: **self-host کردن فونت و assetها، یکپارچه‌سازی tokenهای CSS، و اجرای تست واقعی browser/accessibility در پنج viewport**. پس از این سه اقدام، تکمیل gallery و محتوای فنی واقعی، و سپس performance budget باید انجام شود.

## منابع

[1]: https://www.w3.org/TR/WCAG22/ "Web Content Accessibility Guidelines 2.2"

[2]: https://www.w3.org/TR/css-writing-modes-4/ "CSS Writing Modes Level 4"

[3]: https://web.dev/articles/font-best-practices "Font best practices for web performance"

[4]: https://web.dev/articles/optimize-cls "Optimize Cumulative Layout Shift"

[5]: https://developer.woocommerce.com/docs/theming/theme-development/template-structure/ "WooCommerce Template Structure Documentation"
