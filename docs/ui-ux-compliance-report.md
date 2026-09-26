# گزارش انطباق UI/UX تخصصی بامرو

**مرجع:** `pasted_content.txt` با مشخصات `BAMERO_PAINT_ADHESIVE_UI_UX_V1_2026`

**تاریخ اجرا:** ۱۷ سپتامبر ۲۰۲۶

**نتیجه:** `PARTIAL / STAGING REQUIRED`

## جمع‌بندی

الزامات قابل پیاده‌سازی در سطح تم و افزونه اجرا شد. پروژه اکنون توکن‌های طراحی صنعتی، رنگ‌های برند تعریف‌شده، typography پایه Vazirmatn، فضای ۸ پیکسلی، container با حداکثر عرض ۱۴۴۰ پیکسل، داده‌های فنی محصول، کدهای RAL/NCS، تب‌های فنی، راهنمای اجرا، تب ایمنی، ماشین‌حساب پوشش، CTA ثابت موبایل و reduced-motion دارد.

الزامات وابسته به داده واقعی، آزمایشگاه، مرورگر، Lighthouse، axe، دیتاشیت رسمی، نمونه فیزیکی رنگ، ویدیو، MSDS، درگاه یا staging در این sandbox قابل اثبات نیستند و عمداً به‌عنوان PASS گزارش نشده‌اند.

## ماتریس اجرای الزامات

| حوزه | الزام | وضعیت | شواهد |
|---|---|---|---|
| رنگ | Primary `#0057B8`، secondary `#F4A300` و neutral/surface | PASS کدی | `css/variables.css` |
| رنگ محصول | استفاده از رنگ تزئینی ممنوع | PASS کدی | `content-product.php` اکنون کد hex معتبر محصول را مصرف می‌کند و fallback خنثی دارد |
| RAL/NCS | نمایش کد استاندارد رنگ | PARTIAL | ۱۰ محصول seed کد RAL/NCS دارند؛ تطبیق با نمونه فیزیکی انجام نشده است |
| typography | Vazirmatn و technical monospace | PASS پایه | tokens و stylesheet |
| grid | container 1440 و spacing unit 8px | PASS کدی | `variables.css` |
| iconography | SVG بهینه و aria-label فارسی | PARTIAL | الگوی دسترسی در کارت و header وجود دارد؛ audit کامل همه آیکون‌ها pending است |
| مشخصات فنی | resin، drying، coverage، VOC، density | PASS کدی/داده seed | فیلدهای پنل محصول، metadata و tab مشخصات فنی |
| راهنمای اجرا | آماده‌سازی، ابزار و شرایط محیطی | PASS پایه | tab راهنمای اجرا |
| ایمنی | خلاصه نگهداری و لینک MSDS | PARTIAL | tab ایمنی وجود دارد؛ فایل MSDS واقعی باید توسط مالک ارائه شود |
| ماشین‌حساب | مساحت به مقدار با ضریب ۱۰٪ | PASS پیاده‌سازی | `bamero_coverage_calculator` و `main.js` |
| جستجو | فیلتر دامنه‌ای و reverse color search | NOT IMPLEMENTED | نیازمند feature و زیرساخت upload/search است |
| navigation | mega menu و sticky reduced header | PARTIAL | header sticky موجود است؛ mega menu اختصاصی کامل نیست |
| mobile | CTA ثابت، touch target و menu | PARTIAL | CTA ثابت موبایل اضافه شد؛ audit واقعی touch target و hamburger pending است |
| accessibility | contrast، alt، keyboard، reduced motion، zoom 200٪ | PARTIAL | reduced motion، focus-visible و alt/aria پایه وجود دارد؛ Lighthouse/axe اجرا نشده است |
| performance UI | CLS، preload، critical CSS، font-display | PARTIAL | lazy loading و ابعاد برخی تصاویر موجود است؛ Lighthouse و LCP واقعی pending است |
| validation gates | ΔE، audit دیتاشیت، سناریوی موبایل، axe و Lighthouse | NOT VERIFIED | نیازمند نمونه فیزیکی و runtime staging |

## جزئیات پیاده‌سازی

در پنل و seed محصول، metadataهای زیر اضافه شده‌اند: کد RAL/NCS، پایه رزین، زمان خشک‌شدن، پوشش‌دهی بر حسب مترمربع بر لیتر، VOC، دانسیته، سطح مناسب و لینک MSDS. داده seed برای هر ۱۰ محصول دارای مقدار مشخص است و با SKU idempotent ایجاد می‌شود.

در صفحه محصول سه تب فنی اضافه شده است. تب «مشخصات فنی» داده‌ها را به‌صورت جدول semantic با `caption` و `scope="row"` نمایش می‌دهد. تب «راهنمای اجرا» حداقل راهنمای آماده‌سازی سطح و ابزار را ارائه می‌کند. تب «ایمنی و نگهداری» هشدار پایه و لینک MSDS را نمایش می‌دهد.

ماشین‌حساب پوشش مقدار سطح و پوشش‌دهی محصول را می‌گیرد و مقدار تقریبی را با فرمول زیر نمایش می‌دهد:

```text
مقدار مورد نیاز = (مساحت / پوشش‌دهی) × ۱٫۱۰
```

اگر پوشش‌دهی در محصول ثبت نشده باشد، سیستم مقدار ساختگی تولید نمی‌کند و وضعیت «ثبت نشده است» را نمایش می‌دهد.

در کارت محصول، gradient تصادفی حذف شده است. در صورت وجود کد hex معتبر، رنگ همان مقدار نمایش داده می‌شود. در غیر این صورت سطح خنثی نشان داده می‌شود و `aria-label` شامل نام محصول و RAL/NCS یا پیام نبود کد است.

## گیت‌های قابل اجرا

```text
PHP lint تمام فایل‌ها: PASS
Static checks: PASS
Seed products: 10
Seed users: 10
Audit JSON: PASS
Archive checksum: PASS
```

## گیت‌های نیازمند staging

تست ۳۲۰، ۳۷۵، ۷۶۸، ۱۰۲۴ و ۱۴۴۰ پیکسل، Lighthouse Accessibility حداقل ۹۵، axe بدون violation، LCP کمتر از ۱٫۵ ثانیه، TTI کمتر از ۲ ثانیه، TBT کمتر از ۱۵۰ میلی‌ثانیه، مقایسه ΔE کمتر از ۳ با نمونه فیزیکی، تطبیق ۱۰ جدول با دیتاشیت رسمی و تکمیل سناریوی خرید موبایل زیر ۹۰ ثانیه در این محیط اجرا نشده‌اند.

## محدودیت و تصمیم انتشار

این تغییرات برای ادامه توسعه و تست staging آماده‌اند. اعلام PASS نهایی UI بدون تست مرورگر واقعی، داده آزمایشگاه، نمونه رنگ فیزیکی، MSDS رسمی و audit دسترسی‌پذیری، با اصل evidence-based production readiness مغایر است.

## منابع

[1]: https://www.w3.org/TR/WCAG22/ "Web Content Accessibility Guidelines 2.2"

[2]: https://www.w3.org/TR/css-color-4/ "CSS Color Module Level 4"

[3]: https://developer.woocommerce.com/docs/theming/theme-development/template-structure/ "WooCommerce Template Structure Documentation"

[4]: https://web.dev/articles/lcp "Largest Contentful Paint"
