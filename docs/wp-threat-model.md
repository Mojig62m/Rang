# مدل تهدید بامرو

## دامنه و فرض‌ها
این ارزیابی برای WordPress 6.7.x و WooCommerce 9.x در محیط PHP 8.3، با فروشگاه فارسی/RTL و درگاه پرداخت ایرانی تهیه شده است. TLS، secret manager محیط اجرا، Redis و پایش لاگ پیش‌فرض‌های استقرار production هستند.

| دارایی | تهدید STRIDE | کنترل اجرایی | شواهد/وضعیت |
|---|---|---|---|
| حساب مدیر | جعل هویت، ارتقای دسترسی | nonce، capability، MFA در میزبان، rate limit ورود | پیاده‌سازی rate limit؛ MFA نیازمند سرویس است |
| سفارش و مبلغ | دستکاری، انکار | APIهای WooCommerce، کلید idempotency، ثبت ساختاری | هسته تولید |
| callback پرداخت | جعل/دستکاری | HMAC-SHA256 با `BAMERO_PAYMENT_WEBHOOK_SECRET` | هسته تولید؛ تست secret لازم |
| XML-RPC و pingback | انکار سرویس/سوءاستفاده | غیرفعال‌سازی هوک و مسدودسازی وب‌سرور | `.htaccess` و هسته تولید |
| افزونه‌ها | ارتقای دسترسی/زنجیره تأمین | نصب فقط توسط مدیر، `DISALLOW_FILE_MODS=true`، فهرست مجاز | افزونه essential |
| داده مشتری | افشای اطلاعات | عدم ثبت PII، عدم fallback secret، TLS، حداقل دسترسی | wp-config و logger |
| OTP/SMS | انکار سرویس/هزینه | nonce، محدودیت نرخ، secret محیطی | افزونه mobile-auth؛ کنترل provider باید تکمیل شود |
| SQL سفارشی | SQLi | عدم query در قالب؛ `$wpdb->prepare` در لایه سرویس | ممیزی ایستا |

## ریسک‌های باقیمانده
CSP فعلی برای سازگاری با WordPress/WooCommerce هنوز `unsafe-inline` دارد و Level 3 قطعی محسوب نمی‌شود؛ باید پس از فهرست‌برداری اسکریپت‌های افزونه‌های نهایی، nonce/hash به‌صورت محیطی فعال شود. Redis و WPScan API token نیز در sandbox حاضر در دسترس نیستند، بنابراین gate عملیاتی آن‌ها pending است.

## معیار پذیرش
هیچ high/critical حل‌نشده‌ای نباید وارد go-live شود؛ callback بدون HMAC رد می‌شود؛ secret در فایل پروژه قرار نمی‌گیرد؛ و تمام تست‌های ایستا و syntax باید سبز باشند.
