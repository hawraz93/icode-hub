<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@icode.com'],
            [
                'name' => 'Hawraz Khaled (iCode)',
                'email' => 'admin@icode.com',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Clients
        $clientPharma = Client::create([
            'name' => 'د. ئاری عوسمان',
            'business_name' => 'کۆمپانیای سمارت فارما بۆ دەرمان',
            'email' => 'info@smartpharma.krd',
            'phone' => '0750-445-1234',
            'whatsapp' => '0750-445-1234',
            'address' => 'شەقامی ٦٠ مەتری، نزیک نەخۆشخانەی فریاکەوتن',
            'city' => 'هەولێر',
            'notes' => 'کڕیاری سیستەمی Enterprise Pharmacy POS و سێرڤەری تایبەت',
            'status' => 'active',
            'portal_access_code' => 'CL-SP-8821',
        ]);

        $clientLab = Client::create([
            'name' => 'د. بژار ڕەفیق',
            'business_name' => 'تاقیگەی ڕازی بۆ شیکاری پزیشکی',
            'email' => 'contact@razilab.com',
            'phone' => '0770-155-9876',
            'whatsapp' => '0770-155-9876',
            'address' => 'شەقامی سالم، تەنیشت سەنتەری پزیشکی',
            'city' => 'سلێمانی',
            'notes' => 'سیستەمی تاقیگە و ئەپڵیکەیشنی دیسکتۆپ + وێبسایت',
            'status' => 'active',
            'portal_access_code' => 'CL-RL-3319',
        ]);

        $clientUni = Client::create([
            'name' => 'پ.د. کامەران ئەحمەد',
            'business_name' => 'زانکۆی نێودەوڵەتی نوێ',
            'email' => 'it@new-uni.edu.krd',
            'phone' => '0750-789-3210',
            'whatsapp' => '0750-789-3210',
            'address' => 'ڕێگای کەرکووک، کەمپەسی سەرەکی',
            'city' => 'هەولێر',
            'notes' => 'سیستەمی ئەستۆپاکی (Student Clearance) و دابەشکردنی هۆڵی تاقیکردنەوە',
            'status' => 'active',
            'portal_access_code' => 'CL-NU-5542',
        ]);

        $clientFMCG = Client::create([
            'name' => 'کاک دیار سالار',
            'business_name' => 'کۆمپانیای لوتکە بۆ بازرگانی و دابەشکردن (FMCG)',
            'email' => 'sales@apex-fmcg.com',
            'phone' => '0750-312-7744',
            'whatsapp' => '0750-312-7744',
            'address' => 'ناوچەی پیشەسازی باکوور',
            'city' => 'دهۆک',
            'notes' => 'سیستەمی کۆگا و ئۆتۆمبێلی فرۆش و ژمێریاری',
            'status' => 'active',
            'portal_access_code' => 'CL-AF-7701',
        ]);

        $clientFarm = Client::create([
            'name' => 'کاک هێمن قادر',
            'business_name' => 'پرۆژەی پەلەوەری و هێلکەی بەختەوەر (HappyEgg)',
            'email' => 'hemn@happyegg.krd',
            'phone' => '0770-888-2121',
            'whatsapp' => '0770-888-2121',
            'address' => 'ناحیەی بەستۆڕە',
            'city' => 'هەولێر',
            'notes' => 'سیستەمی بەڕێوەبردنی هۆڵی پەلەوەر و بەرهەمی هێلکە و فرۆش',
            'status' => 'active',
            'portal_access_code' => 'CL-HE-9012',
        ]);

        // 3. Infrastructure / Servers (Hawraz's VPS expenses)
        $serverHetzner = Server::create([
            'name' => 'Hetzner CPX31 (Core Enterprise VPS)',
            'provider' => 'Hetzner Cloud',
            'ip_address' => '65.108.72.19',
            'location' => 'Germany (Falkenstein)',
            'specs' => '4 vCPU AMD, 8 GB RAM, 160 GB NVMe SSD',
            'cost' => 18.50,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'purchase_date' => Carbon::now()->subMonths(8),
            'renewal_date' => Carbon::now()->addDays(5), // Expiring soon!
            'status' => 'active',
            'auto_renew' => true,
            'notes' => 'سێرڤەری سەرەکی دەرمانخانەکان و تاقیگەی ڕازی',
        ]);

        $serverContabo = Server::create([
            'name' => 'Contabo Cloud VPS L (University & Heavy Apps)',
            'provider' => 'Contabo',
            'ip_address' => '144.91.88.204',
            'location' => 'Germany',
            'specs' => '8 vCPU Cores, 30 GB RAM, 800 GB SSD',
            'cost' => 32.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'purchase_date' => Carbon::now()->subMonths(14),
            'renewal_date' => Carbon::now()->addDays(18),
            'status' => 'active',
            'auto_renew' => true,
            'notes' => 'سێرڤەری سیستەمی زانکۆ و داتابەیسی پڕۆژە گەورەکان',
        ]);

        $serverWeb = Server::create([
            'name' => 'DigitalOcean Web Host (Client Websites)',
            'provider' => 'DigitalOcean',
            'ip_address' => '159.65.120.84',
            'location' => 'Frankfurt FRA1',
            'specs' => '2 vCPU, 4 GB RAM, 80 GB SSD',
            'cost' => 24.00,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'purchase_date' => Carbon::now()->subMonths(5),
            'renewal_date' => Carbon::now()->addDays(2), // Very urgent!
            'status' => 'active',
            'auto_renew' => false,
            'notes' => 'هۆستینگی وێبسایتەکانی پۆرتال و لاندینگ پەیجەکان',
        ]);

        // 4. Subscriptions (Domains, Hosting, Emails, Licenses)
        Subscription::create([
            'client_id' => $clientPharma->id,
            'server_id' => $serverHetzner->id,
            'name' => 'دۆمەینی فەرمی smartpharma.krd',
            'type' => 'domain',
            'domain_name' => 'smartpharma.krd',
            'provider' => 'KRG Domain Registry / NIC.KRD',
            'cost_price' => 35.00,
            'selling_price' => 75.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(11)->subDays(25),
            'expiry_date' => Carbon::now()->addDays(5), // 5 days left!
            'auto_renew' => false,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'نوێکردنەوەی ساڵانەی دۆمەینی .krd',
        ]);

        Subscription::create([
            'client_id' => $clientPharma->id,
            'server_id' => $serverHetzner->id,
            'name' => 'هۆستینگی سێرڤەری Cloud POS & Database',
            'type' => 'hosting',
            'domain_name' => 'pos.smartpharma.krd',
            'provider' => 'iCode Cloud (Hetzner)',
            'cost_price' => 60.00,
            'selling_price' => 240.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(11)->subDays(25),
            'expiry_date' => Carbon::now()->addDays(5),
            'auto_renew' => false,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'سێرڤەری کلاود پۆس و پاشەکەوتی ڕۆژانە (Daily Backup)',
        ]);

        Subscription::create([
            'client_id' => $clientLab->id,
            'server_id' => $serverHetzner->id,
            'name' => 'دۆمەینی ڕازی لاباتۆری razilab.com',
            'type' => 'domain',
            'domain_name' => 'razilab.com',
            'provider' => 'Namecheap',
            'cost_price' => 14.00,
            'selling_price' => 45.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(11)->subDays(16),
            'expiry_date' => Carbon::now()->addDays(14),
            'auto_renew' => true,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'بەستراوەتەوە بە Cloudflare DNS',
        ]);

        Subscription::create([
            'client_id' => $clientLab->id,
            'server_id' => $serverHetzner->id,
            'name' => 'ئیمەیڵی فەرمی ٥ ئەکاونت (Google Workspace)',
            'type' => 'email',
            'domain_name' => 'razilab.com',
            'provider' => 'Google Workspace',
            'cost_price' => 36.00,
            'selling_price' => 120.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(11)->subDays(16),
            'expiry_date' => Carbon::now()->addDays(14),
            'auto_renew' => true,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'ئیمەیڵی کارمەندان و بەڕێوەبەر',
        ]);

        Subscription::create([
            'client_id' => $clientUni->id,
            'server_id' => $serverContabo->id,
            'name' => 'سێرڤەری پرۆژەی Student Clearance & ExamSeat',
            'type' => 'vps',
            'domain_name' => 'exams.new-uni.edu.krd',
            'provider' => 'iCode High-Perf Contabo Node',
            'cost_price' => 180.00,
            'selling_price' => 650.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(8),
            'expiry_date' => Carbon::now()->addMonths(4),
            'auto_renew' => false,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'سێرڤەری دەستنیشانکراو بۆ کاتی تاقیکردنەوەی خوێندکاران',
        ]);

        Subscription::create([
            'client_id' => $clientFMCG->id,
            'server_id' => $serverWeb->id,
            'name' => 'مۆڵەتنامە و پشتگیری ساڵانەی FMCG Distribution',
            'type' => 'license',
            'domain_name' => 'fmcg.apex.com',
            'provider' => 'iCode Software',
            'cost_price' => 0.00,
            'selling_price' => 450.00,
            'currency' => 'USD',
            'billing_cycle' => 'annual',
            'start_date' => Carbon::now()->subMonths(10),
            'expiry_date' => Carbon::now()->addMonths(2),
            'auto_renew' => false,
            'status' => 'active',
            'reminder_days_before' => 30,
            'notes' => 'پشتگیری و چاکسازی و نوێکردنەوەی سیستەمەکە',
        ]);

        // 5. Projects (Showcase Portfolio - Trilingual: EN, KU, AR)
        $projPharma = Project::create([
            'client_id' => $clientPharma->id,
            'title' => json_encode([
                'en' => 'Enterprise Pharmacy POS & Multi-Branch Inventory',
                'ku' => 'سیستەمی پیشەسازی و بەڕێوەبردنی دەرمانخانە (Enterprise Pharmacy POS)',
                'ar' => 'نظام إدارة الصيدليات المتقدم والمبيعات (Enterprise Pharmacy POS)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'enterprise-pharmacy-pos',
            'category' => 'pos',
            'client_name' => 'Smart Pharma Co.',
            'summary' => json_encode([
                'en' => 'An enterprise desktop & cloud software for pharmacy POS, expiry tracking, wholesale debt, and multi-branch inventory.',
                'ku' => 'سیستەمێکی پێشکەوتووی دیسکتۆپ و کلاود بۆ بەڕێوەبردنی فرۆش، کۆگا، مێژووی بەسەرچوونی دەرمان و فرەلق.',
                'ar' => 'نظام سحابي وسطح مكتب متقدم لإدارة مبيعات الصيدليات، تتبع تواريخ انتهاء الصلاحية والمخزون متعدد الفروع.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Engineered specifically for pharmaceutical standards, supporting barcode scanners, QA compliance, insurance, and wholesale invoicing.',
                'ku' => 'سیستەمی Enterprise Pharmacy بە تەواوی بۆ پێداویستی بازاڕی دەرمان لە هەرێمی کوردستان دیزاین کراوە؛ پشتگیری بارکۆد، پۆلێنی زانستی دەرمان و دەزگای کۆنترۆڵی جۆری دەکات.',
                'ar' => 'مصمم خصيصاً لتلبية متطلبات قطاع الأدوية والصيدليات؛ يدعم قارئات الباركود، التصنيف العلمي، وإدارة الديون والموردين.',
            ], JSON_UNESCAPED_UNICODE),
            'case_study' => json_encode([
                'en' => 'Smart Pharma reduced expired medication waste by 85% within 3 weeks of deployment.',
                'ku' => 'کۆمپانیای سمارت فارما بە جێبەجێکردنی سیستەمەکە زیانی دەرمانی بەسەرچووی بە ڕێژەی ٨٥٪ کەمکردەوە.',
                'ar' => 'خفضت شركة سمارت فارما خسائر الأدوية منتهية الصلاحية بنسبة 85٪ خلال 3 أسابيع.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'Automated early expiry alert engine',
                    'Ultra-fast barcode POS and thermal receipt printing',
                    'Customer credit and wholesale vendor accounting',
                    'Packet & strip level granular profit analysis',
                    'Full offline sync during internet disruptions'
                ],
                'ku' => [
                    'ئاگاداری ئۆتۆماتیکی پێش بەسەرچوونی دەرمان',
                    'خێرایی لە بارکۆد سکانین و چاپکردنی خێرای وەسڵ',
                    'بەڕێوەبردنی قەرزی کڕیاران و کۆمپانیاکانی هاوردە',
                    'ڕاپۆرتی پوختی قازانج لەسەر ئاستی پاکەت و حەب',
                    'پشتگیری ئۆفلاین لە کاتی بڕانی ئینتەرنێت'
                ],
                'ar' => [
                    'تنبيهات آلية مبكرة لتواريخ انتهاء الصلاحية',
                    'سرعة فائقة في مسح الباركود وطباعة الفواتير',
                    'إدارة ديون العملاء وحسابات الموردين',
                    'تقارير أرباح دقيقة على مستوى العلبة والشريط',
                    'دعم كامل للعمل دون اتصال بالإنترنت (Offline Sync)'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel 12', 'Livewire 3', 'Tailwind CSS', 'MySQL', 'Electron / Desktop POS'],
            'demo_url' => 'https://demo.icode-hub.com/pharmacy',
            'live_url' => 'https://smartpharma.krd',
            'github_url' => 'https://github.com/hawraz93/Enterprise-Pharmacy-POS-',
            'status' => 'completed',
            'is_featured' => true,
            'order_index' => 1,
            'completion_date' => Carbon::now()->subMonths(6),
        ]);

        $projLab = Project::create([
            'client_id' => $clientLab->id,
            'title' => json_encode([
                'en' => 'Medical Laboratory & Results Portal (Med-Lab Pro LIS)',
                'ku' => 'سیستەمی تاقیگەی پزیشکی و ئەنجامی نەخۆش (Med-Lab Pro LIS)',
                'ar' => 'نظام إدارة المختبرات الطبية والنتائج (Med-Lab Pro LIS)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'med-lab-desktop-system',
            'category' => 'medical',
            'client_name' => 'Razi Medical Laboratory',
            'summary' => json_encode([
                'en' => 'Complete clinical laboratory information system with desktop analyzers interfacing and online QR patient results.',
                'ku' => 'سیستەمی بەڕێوەبردنی تاقیگەی شیکاری پزیشکی بە دیسکتۆپ و پۆرتالی سەر وێب بۆ وەرگرتنەوەی ئەنجامی تاقیکردنەوە بە بارکۆد.',
                'ar' => 'نظام متكامل لإدارة المختبرات والتحاليل الطبية مع بوابة إلكترونية لاستلام نتائج المرضى عبر رمز QR.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Covers full lab workflow from sample reception and analyzer data capture to SMS alerts and bilingual PDF reports.',
                'ku' => 'تەواوی قۆناغەکانی تاقیگە لە وەرگرتنی نموونە تا پشکنین، ئەنجامی ئامێرەکان، و داگرتنی ڕاپۆرت بە QR Code دەگرێتەوە.',
                'ar' => 'يغطي دورة العمل في المختبر من استلام العينات وربط الأجهزة الطبية إلى إرسال النتائج للمرضى وطباعة التقارير.',
            ], JSON_UNESCAPED_UNICODE),
            'case_study' => json_encode([
                'en' => 'Patient result turnaround time reduced from 4 hours to instantaneous online retrieval.',
                'ku' => 'ڕێژەی چاوەڕوانی نەخۆش بۆ وەرگرتنەوەی ئەنجام لە ٤ کاتژمێرەوە بۆ خولەکێک لە ڕێگەی ئۆنلاین کەمکرایەوە.',
                'ar' => 'تم تقليص وقت استلام نتائج المرضى من 4 ساعات إلى الاستلام الفوري عبر الإنترنت.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'CBC, Biochemistry, Hormone standard panel templates',
                    'Direct patient online portal via QR Code scan',
                    'Multilingual lab report PDF export (English & Kurdish)',
                    'Doctor referral commissions and finance tracking'
                ],
                'ku' => [
                    'پشکنینەکانی CBC, Biochemistry, Hormone بە فۆرماتی ستاندارد',
                    'بەستنەوە بە پەڕەی ئۆنلاینی نەخۆش بە سکانی QR Code',
                    'داگرتنی ڕاپۆرتی تاقیگە بە زمانی کوردی و ئینگلیزی',
                    'بەڕێوەبردنی دارایی و پشکنینی پزیشکانی هاوبەش'
                ],
                'ar' => [
                    'فحوصات CBC والكيمياء الحيوية والهرمونات بنماذج قياسية',
                    'بوابة إلكترونية للمرضى عبر مسح رمز QR',
                    'طباعة تقارير التحاليل باللغتين الإنجليزية والكردية/العربية',
                    'إدارة العمولات المالية للأطباء والمراكز المحيلة'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel', 'Livewire', 'WireUI', 'DomPDF', 'Tailwind CSS', 'MySQL'],
            'demo_url' => 'https://demo.icode-hub.com/lab',
            'live_url' => 'https://razilab.com',
            'github_url' => 'https://github.com/hawraz93/med-lab-desktop',
            'status' => 'completed',
            'is_featured' => true,
            'order_index' => 2,
            'completion_date' => Carbon::now()->subMonths(4),
        ]);

        $projUni = Project::create([
            'client_id' => $clientUni->id,
            'title' => json_encode([
                'en' => 'University Student Clearance & Exam Seating Engine (ExamSeat Pro)',
                'ku' => 'سیستەمی ئەستۆپاکی خوێندکاران و دابەشکردنی هۆڵەکانی تاقیکردنەوە (Student Clearance & ExamSeat)',
                'ar' => 'نظام براءة الذمة الجامعية وتوزيع قاعات الامتحانات (ExamSeat Pro)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'student-clearance-examseat',
            'category' => 'education',
            'client_name' => 'New International University',
            'summary' => json_encode([
                'en' => 'Automated online student clearance across 8 faculties & anti-cheat algorithm for exam hall seating allocation.',
                'ku' => 'سیستەمێکی تەواو ئەلیکترۆنی بۆ تێپەڕاندنی ئەستۆپاکی خوێندکاران و دابەشکردنی هۆڵی تاقیکردنەوە.',
                'ar' => 'نظام إلكتروني متكامل لإنجاز براءة ذمة الطلبة وتوزيع مقاعد الامتحانات آلياً لمنع الغش.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Eliminated paper bottlenecks. Students request clearance with one click, verified through secure multi-department digital approvals.',
                'ku' => 'ئەم پڕۆژەیە کاغەز و ڕۆتینی زانکۆی نەهێشت و بە شێوازی Digital Approval ئەستۆپاکی پەسەند دەکات.',
                'ar' => 'ألغى الروتين الورقي في الجامعة؛ يقوم الطالب بطلب براءة الذمة إلكترونياً وتتم الموافقة عبر 8 أقسام رقمياً.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'Multi-department digital signature workflow',
                    'Anti-cheat automated seat matrix by exam index',
                    'Official verifiable PDF clearance certificate',
                    'Dean and Registrar General administration dashboards'
                ],
                'ku' => [
                    'سیستەمی واژۆی ئەلیکترۆنی لە نێوان ٨ بەشی کارگێڕی',
                    'دابەشکردنی ئۆتۆماتیکی کورسی تاقیکردنەوە بە ژمارەی ئەزموونی',
                    'پێدانی پسوولەی پەسەندکراوی فەرمی بە کۆدی دڵنیایی',
                    'داشبۆردی ڕاگری کۆلێژ و بەڕێوەبەرایەتی تۆماری گشتی'
                ],
                'ar' => [
                    'سير عمل التوقيع والموافقات الإلكترونية بين 8 أقسام إدارية',
                    'توزيع ذكي وتلقائي لمقاعد الامتحانات برقم الجلوس',
                    'إصدار وثيقة براءة الذمة الرسمية مع رمز الأمان',
                    'لوحة تحكم لعمداء الكليات والتسجيل العام'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel 12', 'Livewire 3', 'Tailwind CSS', 'WireUI RTL', 'PostgreSQL'],
            'demo_url' => 'https://demo.icode-hub.com/clearance',
            'live_url' => 'https://new-uni.edu.krd',
            'github_url' => 'https://github.com/hawraz93/student-clearance-system',
            'status' => 'completed',
            'is_featured' => true,
            'order_index' => 3,
            'completion_date' => Carbon::now()->subMonths(2),
        ]);

        $projFinance = Project::create([
            'client_id' => null,
            'title' => json_encode([
                'en' => 'Enterprise Financial Accounting & Wealth Management (ExpenseFlow 2026)',
                'ku' => 'سیستەمی پێشکەوتووی دارایی و چاودێری سامان (ExpenseFlow & Finance 2026)',
                'ar' => 'النظام المالي والمحاسبي المتقدم (ExpenseFlow & Finance 2026)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'finance-expenseflow-system',
            'category' => 'finance',
            'client_name' => 'I-CODE Core Suite',
            'summary' => json_encode([
                'en' => 'Multi-currency accounting ERP dashboard for revenue, cashflow, employee payroll, and net profit analytics.',
                'ku' => 'داشبۆردی ژمێریاری گشتگیر بۆ بەڕێوەبردنی داهات، خەرجی، قەرز، مووچەی کارمەندان و قازانجی پوخت بە فرەدراو.',
                'ar' => 'لوحة تحكم محاسبية متكاملة لإدارة الإيرادات، المصروفات، رواتب الموظفين، وصافي الأرباح بعملات متعددة.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Full financial ERP managing multi-currency treasury (USD, IQD), petty cash, balance sheets, and real-time ledger generation.',
                'ku' => 'پلاتفۆرمێکی تەواوی ERP بۆ بەڕێوەبردنی سندووقەکانی دراو، خەرجییە گشتییەکان، و دەرکردنی باڵانس شیت.',
                'ar' => 'منصة متكاملة لإدارة الصناديق المالية (بالدولار والدينار العراقي)، السلف، واستخراج الميزانية العمومية والتقارير.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'Multi-currency treasury ($ USD & IQD Dinar) with live exchange rates',
                    'Interactive revenue & cashflow charts powered by Chart.js',
                    'Instant Excel and PDF financial statements export',
                    'Detailed categorization across departments and projects'
                ],
                'ku' => [
                    'فرەدراو ($ دۆلار و IQD دیناری عێراقی) بە بەهای ڕۆژ',
                    'چارتی داینامیکی داهات و خەرجی بە Chart.js',
                    'دابەزاندنی ڕاپۆڕتی دارایی بە Excel و PDF',
                    'پۆلێنبەندی وردی جۆری خەرجییەکان و بەشەکان'
                ],
                'ar' => [
                    'دعم متعدد العملات (الدولار والدينار العراقي) بأسعار الصرف اليومية',
                    'رسوم بيانية تفاعلية للإيرادات والمصروفات عبر Chart.js',
                    'تصدير القوائم المالية الفورية إلى Excel وPDF',
                    'تصنيف دقيق ومفصل لكافة بنود الصرف والأقسام'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel 13', 'Livewire 3', 'Tailwind 4', 'WireUI', 'ChartJS', 'MySQL'],
            'demo_url' => 'https://demo.icode-hub.com/finance',
            'github_url' => 'https://github.com/hawraz93/finance-system',
            'status' => 'completed',
            'is_featured' => true,
            'order_index' => 4,
            'completion_date' => Carbon::now()->subMonth(),
        ]);

        $projFMCG = Project::create([
            'client_id' => $clientFMCG->id,
            'title' => json_encode([
                'en' => 'FMCG Distribution & Van-Sales ERP',
                'ku' => 'سیستەمی بازرگانی و دابەشکردنی FMCG',
                'ar' => 'نظام توزيع السلع الاستهلاكية ومبيعات المناديب (FMCG)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'fmcg-distribution-system',
            'category' => 'commercial',
            'client_name' => 'Lutka FMCG Group',
            'summary' => json_encode([
                'en' => 'Van-sales fleet logistics, central warehouse inventory, and automated market credit invoicing.',
                'ku' => 'سیستەمی بەڕێوەبردنی کاروانەکانی دابەشکردنی کاڵا، ڤان سەیڵز، و کۆگای سەرەکی.',
                'ar' => 'إدارة أسطول مناديب المبيعات (Van-Sales)، مستودعات البضائع، ومبيعات الآجل للأسواق.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Connects field salesmen via tablets to real-time central warehouse inventory and GPS routes.',
                'ku' => 'بەستنەوەی مەندووبەکانی دابەشکردن بە تابلێت و پسوولەی گەڕۆک بە سیستەمی کۆگای ناوەندی.',
                'ar' => 'ربط المناديب في الميدان عبر الأجهزة اللوحية بالمخزون المركزي وتتبع مسارات التوزيع.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'Central warehouse and van sub-inventory syncing',
                    'Mobile POS invoicing with thermal Bluetooth printing',
                    'Market credit accounting and settlement schedules'
                ],
                'ku' => [
                    'بەڕێوەبردنی کۆگای سەرەکی و کۆگای ئۆتۆمبێلەکان',
                    'وەسڵی فرۆشی مۆبایل و شوێنپێهەڵگرتنی GPS',
                    'ژمێریاری مارکێت و دوکانەکان بە سیستەمی قەرز'
                ],
                'ar' => [
                    'مزامنة بضائع المستودع المركزي وسيارات التوزيع',
                    'طباعة فواتير البيع المحمولة عبر البلوتوث',
                    'إدارة ديون ومستحقات الماركتات والمتاجر'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel 12', 'Livewire', 'WireUI', 'Tailwind CSS', 'MySQL'],
            'demo_url' => 'https://demo.icode-hub.com/fmcg',
            'github_url' => 'https://github.com/hawraz93/fmcg-system',
            'status' => 'completed',
            'is_featured' => false,
            'order_index' => 5,
            'completion_date' => Carbon::now()->subMonths(5),
        ]);

        $projFarm = Project::create([
            'client_id' => $clientFarm->id,
            'title' => json_encode([
                'en' => 'Poultry Farm & Egg Incubation Management (HappyEgg)',
                'ku' => 'سیستەمی بەڕێوەبردنی پەلەوەر و بەرهەمهێنانی هێلکە (HappyEgg)',
                'ar' => 'نظام إدارة مزارع الدواجن وتفقيس البيض (HappyEgg)',
            ], JSON_UNESCAPED_UNICODE),
            'slug' => 'happyegg-poultry-system',
            'category' => 'commercial',
            'client_name' => 'HappyEgg Agri Project',
            'summary' => json_encode([
                'en' => 'Agricultural system tracking incubation cycles, batch feed consumption, mortality rates, and wholesale cartons dispatch.',
                'ku' => 'سیستەمی چاودێری قۆناغەکانی هێلکەدانان، هەڵهێنان (Incubation)، ئالیک و تەندروستی پەلەوەر.',
                'ar' => 'نظام زراعي متكامل لتتبع فترات الحضانة والتفقيس، استهلاك الأعلاف، ومبيعات كراتين البيض.',
            ], JSON_UNESCAPED_UNICODE),
            'description' => json_encode([
                'en' => 'Daily farm operations ERP controlling feed costs, vaccinations, egg grades, and corporate invoices.',
                'ku' => 'چاودێری ڕۆژانەی تێچووی دانەوێڵە، ڤاکسین، بەرهەمی کارتۆنی هێلکە و دابەشکردنی بەسەر کۆمپانیاکاندا.',
                'ar' => 'مراقبة يومية لتكاليف الأعلاف، اللقاحات البيطرية، إنتاج البيض وإصدار الفواتير للشركات.',
            ], JSON_UNESCAPED_UNICODE),
            'features' => json_encode([
                'en' => [
                    'Daily egg production metrics per hall and batch',
                    'Mortality rate tracking and veterinarian logs',
                    'Wholesale carton dispatching and billing'
                ],
                'ku' => [
                    'تۆماری ڕۆژانەی بەرهەمهێنان لەسەر ئاستی هۆڵەکان',
                    'ڕێژەی مرداربوون و تەندروستی پەلەوەر',
                    'فرۆشی عموم و بەستنەوە بە پسوولەی فەرمی'
                ],
                'ar' => [
                    'تسجيل الإنتاج اليومي للبيض على مستوى القاعات والوجبات',
                    'تتبع نسب النفوق وسجلات المتابعة البيطرية',
                    'إدارة المبيعات بالجملة والفواتير الرسمية'
                ]
            ], JSON_UNESCAPED_UNICODE),
            'tech_stack' => ['Laravel', 'Livewire', 'Tailwind', 'MySQL'],
            'demo_url' => 'https://demo.icode-hub.com/happyegg',
            'github_url' => 'https://github.com/hawraz93/HappyEgg',
            'status' => 'completed',
            'is_featured' => false,
            'order_index' => 6,
            'completion_date' => Carbon::now()->subMonths(7),
        ]);

        // 6. Contracts
        $contractPharma = Contract::create([
            'contract_number' => 'ICODE-CNT-2026-001',
            'client_id' => $clientPharma->id,
            'project_id' => $projPharma->id,
            'title' => 'عەقدی پەرەپێدان و دابینکردنی سیستەمی Enterprise Pharmacy POS',
            'terms' => "١. لایەنی یەکەم (iCode Group) پابەند دەبێت بە دابینکردنی سیستەمی فرۆش و کۆگا بۆ لقی سەرەکی.\n٢. دابینکردنی سێرڤەری کلاود و پاشەکەوتی ڕۆژانەی داتا بۆ ماوەی یەک ساڵ لەسەر ئەستۆی لایەنی یەکەم دەبێت.\n٣. لایەنی دووەم پابەند دەبێت بە پێدانی گوژمەی ساڵانەی هۆستینگ و پشتگیری لە کاتی دیاریکراودا.",
            'total_amount' => 1800.00,
            'currency' => 'USD',
            'start_date' => Carbon::now()->subMonths(11)->subDays(25),
            'end_date' => Carbon::now()->addDays(5),
            'status' => 'active',
            'signed_by_client' => true,
            'client_signature_name' => 'د. ئاری عوسمان',
            'signed_at' => Carbon::now()->subMonths(11)->subDays(24),
            'notes' => 'عەقدی ساڵی یەکەم بە سەرکەوتوویی جێبەجێکراوە و لە بەرواری نوێکردنەوەدایە.',
        ]);

        $contractUni = Contract::create([
            'contract_number' => 'ICODE-CNT-2026-002',
            'client_id' => $clientUni->id,
            'project_id' => $projUni->id,
            'title' => 'عەقدی پەرەپێدانی سیستەمی ئەستۆپاکی خوێندکاران و هۆڵەکانی تاقیکردنەوە',
            'terms' => "١. دروستکردن و جێبەجێکردنی سیستەمی ئەستۆپاکی بۆ هەموو بەشەکانی زانکۆ.\n٢. ڕاهێنانی ستافی تۆمار و بەشەکان بۆ ماوەی دوو هەفتە.\n٣. دابینکردنی لایسنس و سێرڤەری تایبەت بۆ ماوەی یەک ساڵی ئەکادیمی.",
            'total_amount' => 3500.00,
            'currency' => 'USD',
            'start_date' => Carbon::now()->subMonths(8),
            'end_date' => Carbon::now()->addMonths(4),
            'status' => 'active',
            'signed_by_client' => true,
            'client_signature_name' => 'پ.د. کامەران ئەحمەد',
            'signed_at' => Carbon::now()->subMonths(8),
            'notes' => 'عەقدی ئەکادیمی ٢٠٢٥-٢٠٢٦',
        ]);

        // 7. Invoices & Items
        $inv1 = Invoice::create([
            'invoice_number' => 'ICODE-INV-2026-001',
            'client_id' => $clientPharma->id,
            'contract_id' => $contractPharma->id,
            'project_id' => $projPharma->id,
            'issue_date' => Carbon::now()->subMonths(11)->subDays(25),
            'due_date' => Carbon::now()->subMonths(11)->subDays(10),
            'subtotal' => 1800.00,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => 1800.00,
            'paid_amount' => 1800.00,
            'currency' => 'USD',
            'status' => 'paid',
            'payment_method' => 'FastPay / کاش',
            'paid_at' => Carbon::now()->subMonths(11)->subDays(20),
            'notes' => 'پێشەکی و کۆی پڕۆژەی دەرمانخانەی لقی سەرەکی',
            'terms' => 'وەسڵی فەرمی دراوە لەگەڵ وەرگرتنی کلیل و کۆدی سیستەم.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'پەرەپێدان و دابینکردنی سیستەمی Enterprise Pharmacy POS',
            'quantity' => 1,
            'unit_price' => 1500.00,
            'total_price' => 1500.00,
            'service_type' => 'development',
        ]);

        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'دۆمەینی .krd و سێرڤەری کلاودی خێرا بۆ ساڵی یەکەم',
            'quantity' => 1,
            'unit_price' => 300.00,
            'total_price' => 300.00,
            'service_type' => 'hosting',
        ]);

        // Renewal Invoice (Pending / Overdue)
        $invRenewal = Invoice::create([
            'invoice_number' => 'ICODE-INV-2026-015',
            'client_id' => $clientPharma->id,
            'contract_id' => $contractPharma->id,
            'project_id' => $projPharma->id,
            'issue_date' => Carbon::now()->subDays(10),
            'due_date' => Carbon::now()->addDays(5),
            'subtotal' => 315.00,
            'discount' => 15.00,
            'tax' => 0.00,
            'total' => 300.00,
            'paid_amount' => 0.00,
            'currency' => 'USD',
            'status' => 'sent',
            'payment_method' => 'FIB Bank / FastPay',
            'notes' => 'وەسڵی نوێکردنەوەی ساڵانەی هۆستینگ و دۆمەینی smartpharma.krd',
            'terms' => 'تکایە پێش بەسەرچوونی بەرواری دیاریکراو گوژمەکە بدەن بۆ ئەوەی خزمەتگوزاری ڕانەوەستێت.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invRenewal->id,
            'description' => 'نوێکردنەوەی ساڵانەی دۆمەینی smartpharma.krd (2026-2027)',
            'quantity' => 1,
            'unit_price' => 75.00,
            'total_price' => 75.00,
            'service_type' => 'domain',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invRenewal->id,
            'description' => 'نوێکردنەوەی سێرڤەری کلاود POS و پاراستنی داتا و پاشەکەوت',
            'quantity' => 1,
            'unit_price' => 240.00,
            'total_price' => 240.00,
            'service_type' => 'hosting',
        ]);

        $invLab = Invoice::create([
            'invoice_number' => 'ICODE-INV-2026-004',
            'client_id' => $clientLab->id,
            'project_id' => $projLab->id,
            'issue_date' => Carbon::now()->subMonths(4),
            'due_date' => Carbon::now()->subMonths(3),
            'subtotal' => 2200.00,
            'discount' => 200.00,
            'tax' => 0.00,
            'total' => 2000.00,
            'paid_amount' => 2000.00,
            'currency' => 'USD',
            'status' => 'paid',
            'payment_method' => 'FIB / کاش',
            'paid_at' => Carbon::now()->subMonths(4)->addDays(5),
            'notes' => 'وەسڵی پڕۆژەی تاقیگەی ڕازی و سیستەمی ئەنجامی ئۆنلاین',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invLab->id,
            'description' => 'پڕۆگرامی تاقیگە Med-Lab Pro و پۆرتالی ئەنجامی نەخۆش',
            'quantity' => 1,
            'unit_price' => 1800.00,
            'total_price' => 1800.00,
            'service_type' => 'development',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invLab->id,
            'description' => 'دۆمەینی razilab.com و ٥ ئیمەیڵی بزنس بۆ ساڵی یەکەم',
            'quantity' => 1,
            'unit_price' => 200.00,
            'total_price' => 200.00,
            'service_type' => 'hosting',
        ]);
    }
}
