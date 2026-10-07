# ڕاپۆرتی وردی ڕێکخستنەوەی iCode Hub بۆ Claude Code

بەروار: 2026-10-07

ئەم بەڵگەنامەیە داواکاری جێبەجێکردنە بۆ کۆدی ئەم ڕیپۆزیتۆرییە. پێش دەستکاری، کۆد و migration و تاقیکردنەوەکان بخوێنەوە. ئەوەی لێرە وەک پێشنیاری schema هاتووە ئاراستەی دیزاینە؛ لەگەڵ داتای هەبوو بگونجێنە و هەمان چەمک دووبارە دروست مەکە.

## 1. مۆدێلی ڕاستەقینەی کار

خاوەنی سیستەم VPSێک دەکڕێت و چەند وێبسایت و سیستەمی کڕیاران لەسەری دادەنێت. بۆ هەر کڕیارێک هۆستێکی سەربەخۆ ناکڕێت.

بۆیە:

- پارەی VPS خەرجی هاوبەشی کۆمپانیایە؛ تەنها یەکجار لە خەرجی هەژمار دەکرێت.
- پارەی هۆست کە لە کڕیار وەردەگیرێت، نرخی خزمەتگوزارییە؛ نابێت بە نرخی کڕینی VPS تێکەڵ بکرێت.
- بەستنەوەی خزمەتگوزاری کڕیار بە VPS، بۆ زانینی شوێنی میوانداریکردنە. ئەم بەستنەوەیە خەرجی نوێ دروست ناکات.
- بەشکردنی خەرجی VPS بەسەر پڕۆژەکاندا، ئەگەر هەبێت، تەنها شیکاری ناوخۆییە. نابێت دیسان زیاد بکرێتە کۆی خەرجی کۆمپانیا.
- خەرجی ڕاستەوخۆی دۆمەین یان لایسەنسێکی تایبەت بە کڕیارێک دەتوانێت جیا تۆمار بکرێت.
- خانەی `cost_price = 0` نابێت بە واتای «قازانجی هۆست 100%» لێک بدرێتەوە. دەکرێت واتای «خەرجی هاوبەشە» یان «تێچوو نەزانراوە» بێت.

ئامانج: بەڕێوەبردنی پڕۆژە، خزمەتگوزاری، وەسڵ، قست، پارەدان، خەرجی و نوێکردنەوە بە یەک زنجیرەی ڕوون، بەبێ دووبارە هەژمارکردن.

## 2. دۆخی کۆدی ئێستا و تێبینییەکان

ئەم خاڵانە لە خوێندنەوەی کۆدی ئێستا دەرکەوتوون؛ پێش جێبەجێکردن دیسان پشتڕاستیان بکەرەوە.

| شوێن | دۆخی ئێستا | کاری پێویست |
|---|---|---|
| `app/Models/Server.php` | VPS و خزمەتگوزارییەکانی خۆمان؛ نرخ، billing cycle، renewal date، subscriptions | وەک ژێرخان بمێنێتەوە؛ خەرجییەکە بە تۆماری خەرجی یەکبخرێت |
| `app/Models/Expense.php` | amount، expense_date، billing_cycle | خەرجی ڕاستەقینە لە پلانی خەرجی دووبارەبووەوە جیا بکرێتەوە |
| `app/Models/Subscription.php` | client_id، server_id، cost_price، selling_price، start/expiry، paid flags | بە پڕۆژە و ماوەی خزمەتگوزاری و بڕگەی وەسڵ ببەسترێتەوە |
| `app/Models/Project.php` | زۆرتر پۆرتفۆلیۆ؛ client/contracts/invoices هەیە | پەڕەی کارگێڕی و دارایی پڕۆژە زیاد بکرێت |
| `app/Livewire/Admin/InvoicesManager.php` | وەسڵ و بڕگەکان هەیە؛ project_id/contract_id لە component هەن | بەستنی پڕۆژە/گرێبەست لە UI ڕوون و سنووردار بێت |
| `app/Models/Invoice.php` | paid_amount و status دەکرێت ڕاستەوخۆ بگۆڕدرێن؛ markPaid مێژووی پارەدان نییە | پارەدان ببێتە سەرچاوەی بڕی دراو |
| `app/Livewire/Admin/Dashboard.php` | annual selling لە subscriptions، annual cost لە servers و expenses | cash، invoiced totals، و پێشبینی ساڵانە جیا نیشان بدرێن |
| `app/Services/QuickRenewal.php` | ماوە annual و start = expiry - 1 year دادەنێت | ماوەی مانگ/ساڵ و دوو ساڵ بە ڕوونی دیاری بکرێت |
| `app/Livewire/Public/ClientPortal.php` | login بە access code یان بەشێک لە ژمارەی مۆبایل؛ viewInvoice/viewContract بە IDی گشتی | ناساندنی کڕیار و سنووردارکردنی دەستگەیشتن پێش هەر فراوانکردنێک چاک بکرێت |

گۆڕانکاری پێشووی ئەم سێشنە:

- خانەکانی `billing_cycle`, `start_date`, `expiry_date` بۆ invoice_items زیادکراون لە migrationی `2026_10_07_120000_add_service_periods_to_invoice_items.php`.
- لە بڕگەی وەسڵدا quantity دەتوانێت ژمارەی ساڵ/مانگ بێت و expiry خۆکار هەژمار بکرێت.
- ServersManager لە پەڕەی ExpensesManager نیشان دەدرێت و لینکە جیاوازەکە لە navigation لابراوە.
- ئەمە یەکخستنی بینراویە؛ هێشتا دوو سەرچاوەی نرخ هەن و یەک ledgerی خەرجی دروست نەکراوە.
- تاقیکردنەوەی `InvoiceServicePeriodsTest.php` زیادکراوە. لەم ژینگەیە `vendor/autoload.php` نەبوو؛ تاقیکردنەوەکان و migration جێبەجێ نەکراون. syntaxی PHPی فایلە پشکنراوەکان دروست بوو.

دەستکارییە هەبووەکان بپارێزە. migrationی پێشوو مەسڕەوە و داتای بەکارهێنەر reset مەکە.

## 3. زنجیرەی داتا و سەرچاوەی ڕاستی

زنجیرەی سەرەکی:

`Client → Project → Subscription/Service → ServicePeriod → InvoiceItem → Invoice → Payment`

ژێرخان و خەرجی:

`Server/VPS → ExpenseSchedule → ExpenseEntry`

بەستنی میوانداریکردن:

`Subscription/Service → Server/VPS`

پڕۆژە دەتوانێت چەند خزمەتگوزاری هەبێت: هۆست، دۆمەین، ئیمەیڵ، پشتگیری. هەر خزمەتگوزارییەک ماوەی خۆی هەیە. ماوەی هۆست نابێت ناچار بە ماوەی دۆمەین یەکسان بێت.

سەرچاوەی هەر ژمارەیەک:

- نرخی پێشنیاری نوێکردنەوە: خزمەتگوزاری.
- نرخی ڕەسمی بۆ ماوەیەکی فرۆشراو: بڕگەی وەسڵی ئەو ماوەیە؛ snapshot بمێنێتەوە.
- دەستپێک/بەسەرچوونی ماوەی ڕاستەقینە: ServicePeriod.
- بڕی وەرگیراو: Payment records.
- خەرجی ڕاستەقینە: ExpenseEntry records.
- نرخ و کاتی داهاتووی خەرجی: ExpenseSchedule؛ پارەدانی ڕاستەقینە نییە.

دوو ڕێگای دەستکاری سەربەخۆ بۆ هەمان نرخی ڕەسمی یان هەمان بەرواری ماوە مەهێڵەوە.

## 4. ڕێکخستنی خەرجی و VPS

### 4.1. یەک پەڕەی خەرجی، دوو چەمکی ڕوون

پەڕەی «خەرجییەکان» دوو تاب هەبێت:

1. «خەرجی تۆمارکراو»: ئەو پارەیەی بەڕاستی دراوە، بە بەروار و شێوازی پارەدان.
2. «خەرجی دووبارەبووەوە»: VPS، AI، ئینتەرنێت و هتد؛ نرخ و ماوە و کاتی پارەدانی داهاتوو.

VPS لە هەمان بەش بەڕێوە ببرێت. زانیاری فنی وەک provider، specs، IP و پڕۆژەکانی سەر VPS لە کارت یان پەڕەی وردەکاری ببینرێت. هەر پرۆسەیەکی زیادکردنی VPS و پلانی خەرجی لە transactionدا بێت.

### 4.2. پێشنیاری schema

`expense_schedules`:

- id، title، category، vendor، currency، amount_per_cycle.
- cycle_unit = month/year؛ cycle_count ژمارەی تەواوی ئەرێنی.
- starts_on، next_due_on، ends_on nullable، status، auto_renew.
- server_id nullable؛ بۆ خەرجی VPS.
- legacy_source و legacy_id بۆ گواستنەوە، بە unique constraintی گونجاو.

`expenses` وەک ledgerی خەرجی ڕاستەقینە بپارێزە و فراوانی بکە:

- expense_schedule_id nullable، server_id nullable، project_id nullable.
- amount، currency، expense_date، payment_method، vendor، reference، notes.
- period_start/period_end nullable بۆ ئەو ماوەیەی پارەکە بۆی دراوە.
- status بۆ ڕەتکردنەوە/هەڵوەشاندنەوە؛ بە بڕ وەک تۆمارێکی نوێ ڕاستکردنەوە بپارێزە.

خەرجی یەکجارە پێویستی بە schedule نییە. schedule بە تەنها نابێت expense entry دروست بکات تا پارەدان تۆمار نەکرێت. ئەگەر تۆمارکردنی خۆکار هەڵبژێردرا، idempotency و ڕوونی «پارەدانی پشتڕاستکراو» پێویستە.

### 4.3. خەرجی VPS یەکجار هەژمار بکرێت

لە ledgerی نوێدا هەژمارکردنی cash expense تەنها لە expenses بێت. `Server.cost` هەمان پارە دیسان زیاد نەکات. لە پێشبینی ساڵانەدا نرخ لە schedule وەربگیرێت، و `Server.cost` ئەگەر بۆ backward compatibility ماوە، mirrored یان deprecated بێت، نەک سەرچاوەیەکی سەربەخۆ.

ڕێڕەوی «پارەی VPS درا» دەبێت:

1. بڕ و currency و بەروار و ماوە نیشان بدات.
2. ExpenseEntryێک دروست بکات.
3. next_due_onی schedule بە یەک ماوە بگوازێتەوە.
4. دووبارە کلیککردن هەمان خەرجی دیسان دروست نەکات.
5. بەسەرچوونی زۆر ماوە بە خۆکار وەک پارەدانی چەند ماوەی ڕابردوو تۆمار نەکات؛ بەکارهێنەر ئەو ماوەیەی پارەی داوە دیاری بکات.

## 5. تێچووی هاوبەش و شیکاری قازانج

بۆ خزمەتگوزاری خانەی `cost_basis` زیاد بکە:

- shared_infrastructure: هۆستی سەر VPSی خۆمان.
- direct_purchase: کڕینی تایبەت، وەک دۆمەینی کڕیار.
- unknown: تێچوو دیار نییە.

بۆ shared_infrastructure خانەی «نرخی کڕینی هۆست» داوا مەکە. لە جێگای ئەوە «VPSی میواندار» هەڵبژێردرێت. direct cost بۆ کڕینی تایبەت هەبێت و بە expense entry ببەسترێتەوە تا دووبارە هەژمار نەکرێت.

لە قۆناغی یەکەمدا قازانجی هاوبەشی کۆمپانیا و تێچووی ڕاستەوخۆی پڕۆژە پیشان بدە. قازانجی تەواوی هەر پڕۆژە بەبێ دابەشکردنی VPS بە ناوی «قازانجی ڕاستەقینە» پیشان مەدە.

دابەشکردنی VPS هەڵبژاردەیی و قۆناغی دواتر بێت:

- بەپێی weightی دەستی، یان یەکسان بۆ پڕۆژە چالاکەکان.
- تەنها لە ماوەیەکی دیاریکراو؛ کاتی چالاکبوون و گواستنەوەی پڕۆژە بپارێزە.
- ئەنجامەکە «تێچووی دابەشکراوی خەمڵێنراو» ناوبنێ.
- allocation expense entryی نوێ نییە.
- مجموع allocationەکان لە خەرجی VPSی ئەو ماوەیە زیاتر نەبێت؛ بەشی بەکارنەهاتوو و rounding remainder دیار بێت.

نموونە: VPS = 20 USD/مانگ و 5 پڕۆژەی یەکسان؛ خەرجی کۆمپانیا 20 USDە. دابەشکردنی شیکاری 4 USD بۆ هەر پڕۆژەیە، بەڵام کۆی خەرجی 40 USD نابێت.

## 6. پڕۆژە ببێتە ناوەندی کار

پەڕەی وردەکاری پڕۆژە زیاد بکە، بە ئەم ناوەڕۆکانە:

- کڕیار، ناو، جۆر، دۆخی کار، ڕۆژی دەستپێکی کار و ڕۆژی تەسلیمکردن.
- نرخی دروستکردنی وێبسایت/سیستەم، وەسڵەکانی و پارەدانەکانی.
- خزمەتگوزارییەکان بە جیا: هۆست، دۆمەین، ئیمەیڵ، پشتگیری.
- VPSی میواندار و URL، بۆ ئەدمین.
- دەستپێک، بەسەرچوون، نرخی نوێکردنەوە، دۆخی پارەدان بۆ هەر خزمەتگوزاری.
- قەرزی ماوە بەپێی currency؛ دۆلار و دینار جیا.
- مێژووی نوێکردنەوە، گواستنەوەی VPS، و پارەدان.
- گرێبەست و تێبینی ناوخۆیی.

`subscriptions.project_id` زیاد بکە. ناساندنی پڕۆژە تەنها لە پڕۆژەکانی هەمان کڕیار بێت. invoice و contract و service دەبێت هەمان clientیان هەبێت؛ تەنها exists validation بەس نییە.

ئەو پڕۆژانەی کڕیاریان نییە بۆ پۆرتفۆلیۆ دەتوانن بمێنن. پڕۆژەی پۆرتفۆلیۆ و دۆخی دارایی تێکەڵ مەکە. خانەی is_featured/publication لە دۆخی کار جیا بێت. پڕۆژەی ناوخۆیی بە default لە پۆرتفۆلیۆ بڵاونەکرێتەوە.

پڕۆژەی وەسڵدار بە hard delete مەسڕەوە؛ archive بکرێت و مێژووی دارایی بپارێزرێت.

## 7. وەسڵ و ماوەی خزمەتگوزاری

### 7.1. ئەزموونی دروستکردن

لە پڕۆژەدا «دروستکردنی وەسڵ» هەبێت و کڕیار/پڕۆژە خۆکار پڕ بکرێنەوە. بڕگەکانی ئاسایی:

| بڕگە | شێوازی نرخ | نموونە |
|---|---|---|
| دروستکردنی سیستەم | یەکجار | 500 USD |
| هۆست | نرخی ساڵێک × ژمارەی ساڵ | 100 × 2 = 200 USD |
| دۆمەین | نرخی هەر ماوەیەک | بە ماوەی خۆی |
| پشتگیری | مانگانە/ساڵانە | بە ماوەی خۆی |

وەسڵی نموونە = 700 USD؛ due_dateی پارەدان لە expiry_dateی هۆست جیاوازە.

### 7.2. ماوە و quantity جیا بکرێنەوە

بۆ بڕگەی یەکجارە quantity ژمارەی دانەیە. بۆ recurring service ماوە خانەی ڕوونی هەبێت:

- billing_unit = month/year.
- duration_count = ژمارەی تەواوی ئەرێنی.
- price_per_unit = نرخی مانگێک یان ساڵێک.
- period_start و period_end.
- subscription_id/service_period_id بۆ بەستنی بڕگە بە خزمەتگوزاری.

بە پێی کۆدی هەبوو دەتوانیت لە قۆناغی سەرەتادا quantity بەکاربهێنیت، بەڵام UI بە ڕوونی «ژمارەی ساڵ» یان «ژمارەی مانگ» بنووسێت. quantityی بڕگەی یەکجارە نەگۆڕدرێت بە ساڵ.

ژمارەی ماوە و نرخی ماوە وەک snapshot لە invoice item بپارێزە؛ گۆڕینی نرخی نوێکردنەوە نابێت وەسڵی کۆن بگۆڕێت.

### 7.3. بەروارەکان

- ماوە بە شێوەی `[start_date, expiry_date)` دیاری بکە؛ expiry ڕۆژی پێویستی نوێکردنەوەیە.
- نموونە: 2026-10-07 + دوو ساڵ = 2028-10-07.
- پەراوێزی مانگ و ساڵی کبیسە بە no-overflow چارەسەر بکە.
- expiry > start؛ duration > 0؛ recurring duration ژمارەی تەواو بێت.
- بەرواری خۆکار لە backend حساب بکرێت، نە تەنها Livewire hook. payloadی دەستکاری کراو و importیش هەمان ڕێسا هەبێت.
- ئەگەر بەرواری دەستی ڕێگەپێدرا، custom period و هۆکار دیار بێت؛ duration/price و بەروار بە بێ ئاگادارکردنەوە دژ بە یەک نەبن.
- due_date >= issue_date؛ بۆ وەسڵی کۆنی هاوردەکراو exceptionی ڕوون هەبێت.

### 7.4. ژمارە، چاپ و پاراستن

- invoice_number unique بێت و ژمارە لە کاتی save بە transaction/sequence و retry دروست بکرێت؛ دوو ئەدمین هەمان ژمارە دروست نەکەن.
- فۆرم و چاپ و پۆرتاڵ هەمان total/period نیشان بدەن.
- currencyی USD/IQD بە ڕوونی دیار بێت؛ `$`ی ثابت لە خانەکانی دینار نەبێت.
- subtotal، discount، tax، total لە backend بە decimal یان minor units هەژمار بکرێن؛ floatی خام بۆ کۆکردنەوەی پارە بەکارمەهێنە.
- paid/sent invoice دەستکاریی نرخ و currencyی سنووردار بێت؛ ڕاستکردنەوە بە مێژوو و credit/voidی ڕوون بێت.
- وەسڵی paid hard delete نەکرێت.
- زانیاری کڕیار و نرخ لە وەسڵی دەرچوو بپارێزە تا گۆڕینی ناوی کڕیار چاپی کۆن بە نهێنی نەگۆڕێت.

## 8. پارەدان و قست

`payments` دروست بکە:

- invoice_id، amount، currency، paid_on، method، reference، notes، recorded_by.
- idempotency_key بۆ درخواستە دووبارەکان.
- reversal/refund linkage یان تۆماری پێچەوانەی ڕوون؛ ئەسڵی پارەدان مەسڕەوە.
- imported/legacy marker بۆ داتای کۆن.

لە قۆناغی یەکەمدا currencyی payment دەبێت currencyی invoice بێت. پارەدان بە currencyی جیاواز تا ڕێسای conversion و exchange-rate snapshot تەواو نەکراوە ڕێگەپێمەدە.

`paid_amount` ئەگەر بمێنێت cached sumی payments بێت و تەنها serviceی پارەدان نوێی بکاتەوە. فۆرمی وەسڵ ڕاستەوخۆ بڕی دراو دەستکاری نەکات.

دۆخەکان:

- draft: هێشتا ڕەسمی نییە.
- issued/sent: وەسڵ دەرچووە و پارەی نەدراوە.
- partial: net payments > 0 و < total.
- paid: net payments = total.
- overdue: ماوەی پارەدان تێپەڕیوە و balance > 0؛ دەتوانێت derived badge بێت.
- cancelled/void: بە هۆکار و مێژوو؛ لە قەرز و billing totals لاببرێت.

زیادەپارەدان لە قۆناغی یەکەم ڕەت بکەرەوە بە messageی ڕوون. بڕی payment دەبێت > 0. پارەدان و حسابی balance لە transaction و row lockدا بێت تا دوو پارەدانی هاوکات overpay دروست نەکەن.

«بە تەواوی پارەی دا» لە web و Telegram و radar دەبێت هەمان PaymentService بانگ بکات و تەنها remaining balance تۆمار بکات. دووبارە کلیککردن پارەدانێکی تر نەکات.

قست (`invoice_installments`) ئەگەر پێویست بێت:

- invoice_id، amount، due_on، sequence.
- کۆی قستەکان = total؛ پارەدانەکان بە ڕێسایەکی دیاریکراو لەسەر قستەکان دابەش بکرێن.
- قست پلانی پارەدانە، payment نییە و داهاتی نوێ دروست ناکات.
- هۆستی دوو ساڵ دەتوانێت دوو قستی ساڵانە یان یەک پارەدان هەبێت؛ expiryی هۆست بە قست ناگۆڕێت.

## 9. مێژووی ماوە و نوێکردنەوە

`service_periods` پێشنیار دەکرێت:

- subscription_id، starts_on، expires_on، status، renewed_from_id nullable.
- billing unit/count، نرخی ڕێککەوتوو و currency.
- origin/idempotency key بۆ initial/renewal/import.
- invoice item بە ماوەکە ببەسترێتەوە؛ وەسڵ تەنها یەک subscription_idی headerی هەبێت بەس نییە، چونکە چەند خزمەتگوزاری تێدایە.

پرۆسەی نوێکردنەوە:

1. ماوەی ئێستا و نرخی نوێ و دەستپێکی ماوەی نوێ نیشان بدە.
2. بۆ خزمەتگوزارییەکی هێشتا چالاک، ماوەی نوێ لە expiryی ئێستا دەست پێ بکات.
3. بۆ هۆستی بەسەرچوو، بەکارهێنەر دیاری بکات لە expiryی کۆن یان ئەمڕۆ دەست پێ دەکات؛ defaultی ڕوون لە settings بێت.
4. دۆمەین پێویستی بە بەرواری دابینکەر هەیە؛ registry expiry بە نهێنی بەسەر ماوەی ڕێککەوتنی کڕیاردا مەنووسە.
5. ماوەی نوێ و بڕگەی وەسڵ دروست بکە؛ ماوەی کۆن بپارێزە.
6. وەرگرتنی پارە و فعالکردنی خزمەتگوزاری دوو کردار بن. سیستەم ڕێگە بدات بە نوێکردنەوە بە قەرز بە هۆکار، بەڵام paid خۆکار نەکات.
7. چەند کلیک یان callbackی Telegram هەمان renewal دووبارە نەکات.

نرخی billing cycleی biennialی کۆن بە نرخی تەواوی دوو ساڵ تفسیر بکرێت؛ لە گواستنەوەدا بە هەڵە دیسان × 2 نەکرێت. annual × quantity=2ی invoice itemی نوێش شێوازێکی جیاوازە؛ ئەم دووانە بە ڕوونی normalize بکرێن.

ئەگەر دۆمەین و هۆست لە bundleدان بەڵام بەروارەکانیان جیاوازن، بۆ دوو خزمەتگوزاری جیا دابەشیان بکە؛ نرخی package دەتوانێت بمێنێت بە allocationی ڕوونی نرخی فرۆشتن.

## 10. داشبۆرد و ڕاپۆرتەکان

هەر ڕاپۆرتێک date range و currencyی دیار بێت. دۆلار و دینار بە یەک ژمارە کۆمەکەرەوە.

کارتە پێویستەکان:

1. پارەی وەرگیراو لە ماوەی هەڵبژێردراو: net payments، بە paid_on.
2. خەرجی دراو لە هەمان ماوە: net expense entries، بە expense_date.
3. جیاوازی cash = وەرگیراو - خەرجی دراو. ئەمە بە «قازانجی تەواوی پڕۆژە» ناونەنێ.
4. کۆی وەسڵی دەرچوو، بە issue_date، بەبێ draft/void.
5. قەرزی ماوە و قەرزی دواکەوتوو، بە currency.
6. پێشبینی ساڵانەی خزمەتگوزاری چالاک: بە billing cycle، بە ناوی forecast.
7. پلانی خەرجییە دووبارەکان و ئەو پارەیەی لە 30 ڕۆژدا دەبێت بدرێت.
8. خزمەتگوزاری کڕیاران کە لە 30/60/90 ڕۆژدا بەسەردەچن.

داهاتی پێشبینی subscriptions و totalی invoices و payments سێ بینینی هەمان کارن؛ کۆمەکەرەوە بە ناوی «کۆی داهات».

بۆ هۆستی 200 USD بۆ دوو ساڵ: ئەگەر هەمووی ئەمڕۆ درا، cash receipt = 200. annualized service forecast = 100/ساڵ. ئەم دوو ژمارەیە لە دوو کارتی جیا نیشان بدرێن. revenue recognitionی ورد و سیستەمی ژمێریاری تەواو لە scopeی ئەم قۆناغەدا نییە.

کۆی خەرجی تۆمارکراوی مانگانە مەکە بە ×12؛ ئەگەر 12 expense entryی مانگانە هەیە، ledger تەنها کۆی ئەو 12 تۆمارەیە. annualization تەنها بۆ schedule/forecastە.

## 11. ئاگادارکردنەوە و Telegram

دوو جۆری ئاگادارکردنەوە جیا بێت:

- «خزمەتگوزاری بەسەردەچێت»: بەپێی ServicePeriod.
- «پارەدان دواکەوتووە»: بەپێی invoice balance یان قست.

paid بە واتای renewed نییە؛ renewedیش بە واتای paid نییە. ئەم خاڵە لە web و radar و Telegram یەکسان بێت.

- 30/7/1 ڕۆژ پێش بەسەرچوون بە setting بێت.
- reminder بە service period و جۆر و بەروار deduplicate بکرێت، نە تەنها subscription ID، تا ماوەی نوێ یادەوەری خۆی هەبێت.
- ناردنی notification دوای commit بێت؛ شکست لە ناردن نابێت payment/renewal هەڵبوەشێنێتەوە.
- callback authentication و authorizationی هەبوو بپارێزە و IDsی کۆن بە سازگاری بگوازەوە.
- ناردنی خۆکار بۆ کڕیار بە settingی چالاککراو بێت؛ reminderی ناوخۆیی و ناردنی کڕیار جیا بن.
- بەسەرچوونی record نابێت خۆکار خزمەتگوزاری لە VPS ڕابگرێت. deployment/suspension لە scopeی ئەم کارە نییە.

## 12. پۆرتاڵی کڕیار: چاکسازی پێش فراوانکردن

کۆدی ئێستا `orWhere('phone', 'like', "%{$code}%")` بەکار دەهێنێت. بەشێک لە ژمارە نابێت ڕێگای login بێت. ئەمە ڕیسکێکی ڕاستەوخۆی کۆدی هەبووە و پێویستە لە قۆناغی یەکەم چاک بکرێت.

- login بە tokenێکی درێژ و ناتوانرێت پێشبینی بکرێت، یان authenticationی گونجاو.
- tokenی نوێ بە hash بپارێزە، revocation/rotation و rate limiting هەبێت.
- ناسنامەی کڕیار لە sessionی پارێزراوە وەربگیرێت؛ بە public Livewire property تەنها پشت مەبەستە.
- viewInvoice و viewContract لە `$authenticatedClient->invoices()` و `contracts()` query بکەن؛ IDی کڕیارێکی تر ڕەت بکرێتەوە.
- هەر actionێک authentication/ownership دوبارە بپشکنێت، لەوانە submitTicket.
- پۆرتاڵ تەنها نرخی فرۆشتن، وەسڵ، پارەدان، ماوە و بەسەرچوون نیشان بدات.
- خەرجی VPS، IP، credential notes، direct cost، allocation و تێبینی ناوخۆیی بۆ کڕیار نیشان نەدرێن.

## 13. ڕێکخستنی UI

navigationی پێشنیارکراو:

- داشبۆرد.
- کڕیاران.
- پڕۆژەکان.
- خزمەتگوزاری و نوێکردنەوە.
- وەسڵ و پارەدان.
- خەرجییەکان؛ ژێرخان/VPS لە ناو ئەم بەشە.
- گرێبەستەکان.

RTLی کوردی و mobile بپارێزە. formی درێژ بە هەنگاوە ڕوونەکان: کڕیار/پڕۆژە → بڕگەکان → ماوە → پارەدان → پوختە.

ڕێڕەوی خێرا: زیادکردنی کڕیار، پڕۆژە، نرخی دروستکردن، هۆست، VPS، start و duration، وەسڵ. لە کۆتاییدا پوختەی ڕوون نیشان بدە پێش save.

QuickAddی هەبوو و parsingی domain بپارێزە، بەڵام missing/ambiguous dates بە هەڵبژاردنی بەکارهێنەر چارەسەر بکە. «دوو ساڵ» بە defaultی annualی یەک ساڵ نەگۆڕێت.

## 14. گواستنەوەی داتا

پێش گواستنەوە dry-run command بنووسە کە counts، linkە ونەکان، currencyی ناسازگار و داتای گومانلێکراو ڕاپۆرت بکات. backupی داتا پێش migrationی گۆڕینی واتا پێویستە.

ڕێسا سەرەکییەکان:

- add-only migrations و backfillی idempotent؛ `migrate:fresh` لە داتای بەکارهێنەر بەکارمەهێنە.
- Server records بۆ infrastructure بپارێزە و هەرێک scheduleی uniqueی خۆی هەبێت.
- `Server.cost` و renewal_date بە تەنها بەڵگەی پارەدانی ڕابردوو نیین؛ لێیان historical expense دروست مەکە.
- expensesی کۆن billing_cycleیان هەیە بەڵام دڵنیایی نییە scheduleن یان پارەدانی ڕاستەقینە. بە گومان auto-convert مەکە؛ needs_review و mappingی ڕوون هەبێت.
- paid_amountی هەبووی invoice دەتوانێت ببێتە opening/imported payment، بە sourceی ڕوون. ئەگەر paid_at نییە بەروارێکی ساختە وەک ئەمڕۆ مەدەنێ؛ بەکارهێنەر پشتڕاست بکاتەوە یان unknown historical date جیا نیشان بدرێت.
- subscriptionی is_paid=true بەڵام invoice/paymentی نییە بەڵگەی بڕ و بەرواری cash نییە؛ cashی ساختە دروست مەکە.
- بۆ subscriptionی هەبوو ماوەی ئێستا import بکە؛ نرخی وەسڵی کۆن کە ناسازگارە لە quarantine/reviewدا بپارێزە.
- project_id تەنها کاتێک backfill بکە کە mapping دڵنیایە؛ بە ناوی نزیک بە خۆکار مەیبەستەوە.
- دووبارەبودنی VPS expense بە title/amountی هاوشێوە بە تەنها مەسڕەوە؛ duplicate candidate ڕاپۆرت بکە.
- ژمارەی وەسڵ، مجموع بڕەکان و historical recordsی ناسراو پێش/دوای backfill compare بکە.
- گۆڕینی پۆرتاڵ بە loginی کۆن ناکۆکی هەیە؛ ڕێڕەوی rotationی کۆدی نوێ بە ڕوونی دابین بکە.

## 15. پلانی جێبەجێکردن

### قۆناغی 1: سەرچاوەی داتا و پاراستن

1. کۆد/تاقیکردنەوە/داتای هەبوو audit بکە و baseline بگرە.
2. پۆرتاڵ ownership/login چاک بکە.
3. payments و PaymentService و balance/statusی یەکگرتوو زیاد بکە.
4. expense schedule لە expense entry جیا بکە و VPS costی دووبارە لاببە.
5. migrationی reviewable و dry-run و tests تەواو بکە.

### قۆناغی 2: پڕۆژە، خزمەتگوزاری و وەسڵ

1. project/service links و ownership validation.
2. ServicePeriod و نرخی snapshot و invoice-item linkage.
3. پەڕەی وردەکاری پڕۆژە و ڕێڕەوی دروستکردنی وەسڵ.
4. ڕێسای duration/date و monthly/annual/biennial normalization.
5. چاپ و پۆرتاڵی وەسڵ یەکسان بکە.

### قۆناغی 3: نوێکردنەوە و داشبۆرد

1. هەموو entry pointەکان بە هەمان RenewalService/PaymentService ببەستەوە.
2. مێژووی ماوە و reminders و idempotency.
3. cash/invoiced/debt/forecast metricsی جیا.
4. date/currency filters و exportی ڕاپۆرتی CSV.

### قۆناغی 4: باشترکردنی هەڵبژاردەیی

1. قست و installment reminders.
2. VPS allocationی خەمڵێنراو و hosting assignment history.
3. ناوەندی reconciliation بۆ داتای کۆن.

قۆناغەکان بە جیا deliver بکە؛ لە هەر قۆناغێکدا سیستەم بەکاربهێنراو بمێنێت. upgradeی Laravel/Livewire، گۆڕینی hosting/deployment و بازنووسینی تەواوی ئەپ لە scopeدا نییە.

## 16. پێوەرەکانی قبوڵکردن و تاقیکردنەوە

### A. VPSی هاوبەش

- VPS = 20 USD/مانگ، 5 پڕۆژەی سەرەوە، هەرێک 100 USD/ساڵ هۆست.
- forecastی فرۆشتن = 500 USD/ساڵ؛ forecastی خەرجی VPS = 240 USD/ساڵ.
- ئەگەر تەنها ئەم مانگە 20 USD دراوە cash expenseی ئەم مانگە 20ە، نە 100 و نە 240.
- هیچ direct hosting purchase بۆ پڕۆژەکان خۆکار دروست نابێت.
- ژمارەی پڕۆژەکان کۆی VPS expense ناگۆڕێت.

### B. وەسڵی دروستکردن + هۆستی دوو ساڵ

- development = 500؛ hosting = annual 100 × 2؛ total = 700 USD.
- start = 2026-10-07؛ expiry = 2028-10-07.
- due_dateی وەسڵ جیاوازە و expiry ناگۆڕێت.
- پارەدانی 300 و 400: دوو تۆمار، paid=700، balance=0، status=paid.
- forecastی هۆست 100/ساڵ؛ cashی وەرگیراو لە ماوەکەدا 700، نە forecast + invoice + payments.

### C. دۆمەین و نوێکردنەوە

- دۆمەین و هۆست بە دوو expiryی جیا reminderی خۆیان هەیە.
- نوێکردنەوە ماوەی کۆن و وەسڵی کۆن دەپارێزێت.
- duplicate click/callback تەنها یەک renewal/invoice/payment دروست دەکات.
- renewal بە قەرز paid نابێت؛ paymentی renewal بە تەنها expiry ناگۆڕێت.
- late hosting/domain بە ڕێسای دیاریکراو و بەرواری ڕوون کار دەکەن.

### D. پارە و currency

- USD و IQD کۆناکرێنەوە.
- IQD input/print prefixی دۆلار نییە.
- paymentی currencyی ناسازگار ڕەت دەکرێتەوە.
- negative/zero payment و overpayment ڕەت دەکرێنەوە.
- دوو paymentی هاوکات balanceی نەرێنی دروست ناکەن.
- rounding و discount و totalی بچووک بە decimal دروستن.
- cancelled invoice لە debt لادەبرێت؛ refund تۆماری مێژوویی هەیە.

### E. بەروار و backend validation

- مانگی 31 ڕۆژە و February و leap-year و دوو ساڵە تاقی بکرێنەوە.
- expiry پێش start ڕەت دەکرێتەوە.
- recurring durationی کەرتی/نەرێنی ڕەت دەکرێتەوە.
- direct backend save/import هەمان ڕێسای UI جێبەجێ دەکات.
- monthly schedule forecast ×12؛ 12 expense entry تەنها summed، نە summed ×12.

### F. ناسنامە و پاراستن

- client A بە IDی invoice/contract/serviceی client B دەستگەیشتنی نییە.
- بەشێکی ژمارەی مۆبایل login ناکات.
- Livewire property manipulation client identity ناگۆڕێت.
- unauthenticated ticket/payment/view ڕەت دەکرێتەوە.
- cost/IP/credentialsی ناوخۆیی لە پۆرتاڵدا نییە.
- پڕۆژە/گرێبەستی کڕیارێکی تر لە invoice ڕەت دەکرێتەوە.

### G. regression و گواستنەوە

- هەموو testsی هەبووی PaymentsTest، RenewalsTest، ZeroClickTest، LoginSecurityTest و MoneyTest بپشکنە.
- پێوەرە کۆنەکانی dashboard کە forecastیان بە profit ناوبردووە، بە specificationی نوێ بگۆڕە؛ testی شکست خواردوو بەبێ هۆکار مەسڕەوە.
- backfill دووجار جێبەجێکردن counts/totals ناگۆڕێت.
- ژمارەی وەسڵە کۆنەکان و totals دەپارێزرێن.
- search predicateە orWhereەکان گروپ بکە تا status/category/currency filters بە گەڕان تێک نەچن.
- RTL، mobile، form، چاپ و پۆرتاڵ بە بینین بپشکنە.

## 17. ئەوەی Claude Code دەبێت تەسلیم بکات

1. کۆدی جێبەجێکراو، بە migrationی پارێزراو و سازگاری داتای هەبوو.
2. dry-run/backfill command و ڕاپۆرتی recordsی پێویست بە پشکنینی دەستی.
3. servicesی ناوەندی بۆ Invoice، Payment، Renewal و Expense؛ هەمان ڕێسا لە هەموو UI/botەکان.
4. تاقیکردنەوەی feature/integration بۆ نموونەکانی سەرەوە.
5. ڕاپۆرتی ئەو testsەی ڕۆیشتوون و ئەوانەی نەڕۆیشتوون، بە هۆکار.
6. ڕێنمایی setup/migration/rollbackی ڕوون، بەبێ resetی داتای بەکارهێنەر.
7. پوختەی گۆڕانکارییەکانی UI و screenshotی پەڕە سەرەکییە نوێکان ئەگەر ژینگە ڕێگە بدات.

لە جێبەجێکردندا VPSی هاوبەش بنەمای سەرەکی بێت. نرخی هۆستی کڕیار بە نرخی کڕینی هۆستێکی جیاواز لێک مەدەرەوە. ئەگەر زانیارییەکی داتای کۆن ناسەرەوە، بە ڕاپۆرتی needs_review بیپارێزە؛ بڕ یان بەرواری ساختە دروست مەکە.
