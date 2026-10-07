# قۆناغی 1: سەرچاوەی داتا و پاراستن

بەروار: 2026-10-07 · لق: `feat/finance-ledger-phase1`

## چی گۆڕا

| بەش | گۆڕانکاری |
|---|---|
| پۆرتاڵی کڕیار | login تەنها بە کۆدی تەواو؛ ژمارەی مۆبایل یان بەشێکی کۆد login ناکات. کۆدی نوێ (`ICP-...`) تەنها hash دەپارێزرێت. ناسنامە لە session، نەک public property. هەموو action ـێک ownership دەپشکنێت. rate limit: 5 هەوڵی هەڵە / 15 خولەک بۆ هەر IP. |
| پارەدان | خشتەی `payments` + `PaymentService`. `paid_amount` تەنها cache ـە. زیادەپارەدان، بڕی سفر/نەرێنی، دراوی جیاواز، وەسڵی draft/cancelled ڕەت دەکرێنەوە. «بە تەواوی درا» تەنها قەرزی ماوە تۆمار دەکات؛ دووبارە کلیک (web/Telegram) هیچ زیاد ناکات. گەڕاندنەوە تۆماری پێچەوانەیە؛ ئەسڵ ناسڕدرێتەوە. |
| وەسڵ | خانەی «بڕی دراو» لە فۆرم لابرا؛ دۆخ تەنها draft/sent/cancelled، partial/paid لە پارەدانەوە دێن. وەسڵی پارەدراو ناسڕدرێتەوە. دراوی وەسڵی پارەدراو ناگۆڕدرێت. ژمارەی وەسڵ بە retry. کۆکان بە decimal. `due_date >= issue_date`. گەڕان و فلتەر تێکەڵ نابن. |
| خەرجی | `expense_schedules` (پلان) جیا لە `expenses` (پارەی دراو). هەر VPS یەک پلان. «پارەدرا» یەک ماوە تۆمار دەکات و بەروار دەگوازێتەوە؛ دووبارە کلیک یەک تۆمار. خەرجی تۆمارکراو void دەکرێت، ناسڕدرێتەوە. پەڕەی خەرجی دوو تابی هەیە. |
| داشبۆرد | پێشبینی خەرجی تەنها لە پلانەکانەوە (VPS یەکجار). «قازانج» بوو بە «جیاوازی پێشبینی». کارتی cash: وەرگیراو، دراو، جیاوازی، قەرزی ماوە. |

## داتای هەبوو

- هیچ migration ـێک داتا ناسڕێتەوە یان ناگۆڕێت (add-only). `migrate:fresh` بەکارمەهێنە.
- `php artisan finance:backfill --dry-run` ڕاپۆرت دەدات بێ گۆڕین.
- `php artisan finance:backfill`:
  - بۆ هەر Server یەک پلانی خەرجی؛ هیچ خەرجیی مێژوویی لە `cost`/`renewal_date` دروست ناکرێت.
  - `paid_amount`ی کۆنی هەر وەسڵ دەبێتە یەک پارەدانی `legacy_import`؛ ئەگەر `paid_at` نەبێت `paid_on = null` و `needs_review`.
  - وەسڵی «دراوە» بەبێ بڕ: بڕی تەواو هاوردە دەکرێت بەڵام `needs_review`.
  - خەرجیی کۆنی «مانگانە/ساڵانە» تەنها `needs_review` دەکرێت؛ لە تابی خەرجی بەکارهێنەر دیاری دەکات.
  - duplicate VPS، subscriptionی «paid» بێ وەسڵ، دراو/بەرواری هەڵە: تەنها ڕاپۆرت.
  - کۆی وەسڵ و ژمارەکان پێش/دوا بەراورد دەکرێن؛ دووجار جێبەجێکردن هیچ ناگۆڕێت.
  - ڕاپۆرتی JSON: `storage/app/private/finance-backfill-*.json`.

## setup لە production

```bash
# 1. backup
mysqldump icode_hub > backup-$(date +%F).sql
# 2. کۆد + migration
composer install --no-dev && php artisan migrate --force
# 3. ڕاپۆرت، پاشان جێبەجێکردن
php artisan finance:backfill --dry-run
php artisan finance:backfill
# 4. cache
php artisan optimize:clear
```

پۆرتاڵ: کۆدی کۆن (`CL-XXXXXX`) کار دەکات تا ئەدمین لە «کڕیاران» → «کۆدی نوێ» کۆدێکی نوێ دەردەکات. کاتێک هەموو کڕیار کۆدی نوێیان هەبوو: `PORTAL_ALLOW_LEGACY_CODES=false`.

## rollback

```bash
php artisan migrate:rollback --step=4
```

خشتە نوێکان و ستوونە نوێکان لادەبرێن؛ `invoices.paid_amount` و خشتە کۆنەکان دەست لێنەدراون و کۆدی پێشوو کار دەکاتەوە. تێبینی: پارەدانی نوێ کە دوای migration تۆمارکراون، لە `paid_amount` ـدا دەمێنن بەڵام مێژووی وردیان لەگەڵ `payments` دەڕوات؛ پێش rollback backup بگرە.

## تاقیکردنەوە

`php artisan test` → 73 تاقیکردنەوە، هەمووی دەڕۆن (38ی کۆن + 35ی نوێ).
پێویستی بە `npm run build` هەیە بۆ تاقیکردنەوەی پەڕەکان (Vite manifest).

تاقیکردنەوەی کۆن کە گۆڕدرا: `ZeroClickTest::test_monthly_summary_totals_this_month_per_currency` — ناوی «وەرگرتن» بوو بە «نرخی نوێکردنەوەکان (پێشبینی)» چونکە ڕاپۆرت دەڵێت پێشبینی بە داهات/قازانج ناونەنرێت.

## ماوە بۆ قۆناغەکانی دواتر

- قۆناغی 2: `subscriptions.project_id`، ServicePeriod، پەڕەی پڕۆژە، ownership validationی invoice/project/contract.
- قۆناغی 3: RenewalService، date-range/currency filter و CSV.
- پشکنینی بینراوی RTL/mobile و screenshot لەم ژینگەیە نەکرا (browser نییە).
