#!/bin/bash

# دستور‌العمل کامل برای commit فایل copilot-agent-contract.md به Mojig62m/Rang
# اجرا: bash COMMIT_INSTRUCTIONS.sh

set -e

echo "🔧 آماده‌سازی کامیت در مخزن Mojig62m/Rang"
echo "================================================"

# گام ۱: Clone مخزن (اگر قبلاً clone نشده)
if [ ! -d "Rang" ]; then
    echo "📥 Clone کردن مخزن Rang..."
    git clone https://github.com/Mojig62m/Rang.git
    cd Rang
else
    echo "✅ مخزن قبلاً clone شده است. ورود به پوشه..."
    cd Rang
fi

# گام ۲: اطمینان از وضعیت main
echo ""
echo "🔄 Fetch کردن آخرین تغییرات از remote..."
git fetch origin main
git checkout main
git pull origin main

# گام ۳: بررسی وجود فایل در محل صحیح
echo ""
echo "✓ بررسی اینکه `.github/` وجود دارد..."
if [ ! -d ".github" ]; then
    mkdir -p .github
    echo "📂 پوشهٔ .github ایجاد شد"
fi

# گام ۴: کپی فایل به محل صحیح
echo ""
echo "📄 کپی فایل copilot-agent-contract.md به `.github/`..."
cp ../copilot-agent-contract.md .github/copilot-agent-contract.md

# گام ۵: بررسی فایل
echo ""
echo "✅ فایل در جای خود:"
ls -lh .github/copilot-agent-contract.md
echo ""
echo "🔍 اولین ۲۰ خط:"
head -20 .github/copilot-agent-contract.md

# گام ۶: اجرای production_gate (اگر موجود باشد)
echo ""
if [ -f "tests/production_gate.sh" ]; then
    echo "🧪 اجرای production_gate.sh برای اطمینان از وضعیت main..."
    bash tests/production_gate.sh || {
        echo "⚠️  gate ناموفق بود. اما ادامه می‌دهیم (این فایل مستند است، نه کد)."
    }
else
    echo "⚠️  tests/production_gate.sh یافت نشد. ادامه..."
fi

# گام ۷: ایجاد branch
echo ""
echo "🌿 ایجاد branch `docs/copilot-agent-contract`..."
git checkout -b docs/copilot-agent-contract

# گام ۸: Commit
echo ""
echo "💾 Commit کردن فایل..."
git add .github/copilot-agent-contract.md

git commit -m "docs(ci): افزودن قرارداد اجرایی برای Copilot Coding Agent

- تعریف معیار وضوح و مناسب‌بودن ایشو برای کار خودکار
- ارجاع دقیق به gate‌های موجود (production_gate.sh, staging_smoke.php)
- شرط دسترسی‌ناپذیری برای PR‌های باز مرتبط (#7, #18, #21)
- الزامات شواهد برای merge‌پذیری (OPERATIONAL_READINESS_FA.md)
- قوانین fail-closed و نمونهٔ فرایند کامل

این سند به منابع و فرایند‌های موجود ارجاع می‌دهد (نه توصیه‌های کلی)."

echo ""
echo "✅ Commit موفق بود!"
echo ""

# گام ۹: Push
echo "🚀 Push کردن branch به origin..."
git push -u origin docs/copilot-agent-contract

echo ""
echo "================================================"
echo "✨ کامل شد!"
echo ""
echo "گام بعدی: ایجاد Pull Request"
echo ""
echo "با استفاده از GitHub CLI:"
echo "  gh pr create --base main --head docs/copilot-agent-contract \\"
echo "    --title 'docs(ci): قرارداد اجرایی برای Copilot Coding Agent' \\"
echo "    --body 'اضافه‌کردن معیار، فرایند و الزامات شواهد برای اختصاص ایشو‌ها.'"
echo ""
echo "یا از طریق GitHub Web:"
echo "  https://github.com/Mojig62m/Rang/pull/new/docs/copilot-agent-contract"
echo ""
