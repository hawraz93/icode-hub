<div class="space-y-6" x-data="{ viewMode: 'annual' }">
    
    <!-- 1. Minimalist Top Header & Period Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg md:text-xl font-black text-slate-900">پوختەی دارایی و کارگێڕی کۆمپانیای iCode</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">چاودێری ڕاستەوخۆی داهاتی کڕیاران بەرامبەر خەرجییەکانی کۆمپانیا</p>
        </div>

        <!-- Period Toggle Switcher -->
        <div class="inline-flex bg-slate-100 p-1 rounded-xl border border-slate-200/60 self-start sm:self-auto">
            <button @click="viewMode = 'annual'" 
                    :class="viewMode === 'annual' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 font-bold hover:text-slate-900'"
                    class="px-3 py-1.5 text-xs rounded-lg transition cursor-pointer">
                📅 ساڵانە
            </button>
            <button @click="viewMode = 'monthly'" 
                    :class="viewMode === 'monthly' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 font-bold hover:text-slate-900'"
                    class="px-3 py-1.5 text-xs rounded-lg transition cursor-pointer">
                🗓️ مانگانە
            </button>
        </div>
    </div>

    <!-- 2. Clean 3-Card Financial Overview (100% Accurate & Transparent) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        
        <!-- 1. NET PROFIT (HERO CARD) -->
        <div class="sm:col-span-2 lg:col-span-1 bg-linear-to-br from-emerald-600 to-teal-700 text-white rounded-2xl md:rounded-3xl p-5 shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs font-extrabold text-emerald-100">
                <span x-text="viewMode === 'annual' ? 'قازانجی خاوێنی ساڵانە (NET)' : 'قازانجی خاوێنی مانگانە (NET)'">قازانجی خاوێنی ساڵانە</span>
                <span class="px-2 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-black font-mono">{{ $profitMargin }}% قازانج</span>
            </div>

            <!-- Annual View -->
            <div x-show="viewMode === 'annual'">
                <div class="text-3xl md:text-4xl font-black font-mono tracking-tight" dir="ltr">
                    +${{ number_format($annualNetProfitUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-emerald-100 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($annualNetProfitIqd) }} د.ع
                </div>
            </div>

            <!-- Monthly View -->
            <div x-show="viewMode === 'monthly'" style="display: none;">
                <div class="text-3xl md:text-4xl font-black font-mono tracking-tight" dir="ltr">
                    +${{ number_format($monthlyNetProfitUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-emerald-100 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($monthlyNetProfitIqd) }} د.ع
                </div>
            </div>

            <p class="text-[11px] text-emerald-100/90 pt-2 border-t border-emerald-500/40">
                قازانجی پوخت: داهات (${{ number_format($totalAnnualRevenueUsd, 2) }}) - خەرجی (${{ number_format($totalAnnualCostsUsd, 2) }})
            </p>
        </div>

        <!-- 2. REVENUE CARD -->
        <div class="bg-white rounded-2xl md:rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                <span x-text="viewMode === 'annual' ? 'کۆی داهاتی ساڵانە (لە کڕیاران)' : 'داهاتی مانگانە (MRR)'">کۆی داهاتی ساڵانە</span>
                <span class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">📈</span>
            </div>

            <!-- Annual View -->
            <div x-show="viewMode === 'annual'">
                <div class="text-2xl md:text-3xl font-black font-mono text-slate-900" dir="ltr">
                    ${{ number_format($totalAnnualRevenueUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-indigo-600 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($totalAnnualRevenueIqd) }} د.ع
                </div>
            </div>

            <!-- Monthly View -->
            <div x-show="viewMode === 'monthly'" style="display: none;">
                <div class="text-2xl md:text-3xl font-black font-mono text-slate-900" dir="ltr">
                    ${{ number_format($monthlyRevenueUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-indigo-600 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($monthlyRevenueIqd) }} د.ع
                </div>
            </div>

            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                {{ $totalActiveSubscriptionsCount }} خزمەتگوزاری فرۆشراو بە کڕیاران
            </p>
        </div>

        <!-- 3. TOTAL EXPENSES CARD -->
        <div class="bg-white rounded-2xl md:rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                <span x-text="viewMode === 'annual' ? 'کۆی خەرجی ساڵانەی iCode' : 'تێچوو و خەرجی مانگانە'">کۆی خەرجی ساڵانەی iCode</span>
                <span class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs">📉</span>
            </div>

            <!-- Annual View -->
            <div x-show="viewMode === 'annual'">
                <div class="text-2xl md:text-3xl font-black font-mono text-slate-900" dir="ltr">
                    ${{ number_format($totalAnnualCostsUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-rose-600 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($totalAnnualCostsIqd) }} د.ع
                </div>
            </div>

            <!-- Monthly View -->
            <div x-show="viewMode === 'monthly'" style="display: none;">
                <div class="text-2xl md:text-3xl font-black font-mono text-slate-900" dir="ltr">
                    ${{ number_format($monthlyCostsUsd, 2) }}
                </div>
                <div class="text-xs font-bold text-rose-600 mt-1 font-mono" dir="ltr">
                    ≈ {{ number_format($monthlyCostsIqd) }} د.ع
                </div>
            </div>

            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                سێرڤەری Contabo ($76.32) + دۆمەین ($27.18)
            </p>
        </div>

    </div>

    <!-- 3. Performance Chart -->
    <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm md:text-base font-black text-slate-900">بەراوردی دارایی بە درێژایی ساڵ</h3>
                <p class="text-xs text-slate-400 font-medium">داهاتی کڕیاران، خەرجییەکانی iCode و قازانجی پوخت</p>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold self-start sm:self-auto">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span> داهات</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> تێچوو</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> قازانج</span>
            </div>
        </div>

        <div class="h-56 md:h-64 relative">
            <canvas id="financialComparisonChart"></canvas>
        </div>
    </div>

    <!-- 4. Two Distinct Boxes: Incoming Client Revenue vs Outgoing Company Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- 🟢 BOX 1: INCOMING MONEY FROM CLIENTS (داهاتی نوێکردنەوە لە کڕیاران) -->
        <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-sm md:text-base font-black text-slate-900">داهاتی نوێکردنەوە لە کڕیاران (پارەیەک بۆت دێت)</h3>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">ئەو کڕیارانەی کاتی نوێکردنەوەیان نزیکە بۆ ناردنی وەسڵ</p>
                </div>
                <a href="{{ route('admin.subscriptions') }}" class="text-xs font-bold text-indigo-600 hover:underline">بینینی هەمووی ←</a>
            </div>

            <div class="space-y-2.5">
                @forelse($expiringSubscriptions as $sub)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-100 hover:bg-emerald-50 transition flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black {{ $sub->days_until_expiry <= 7 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $sub->expiry_status_text }}
                                </span>
                                <h4 class="font-extrabold text-slate-900 text-xs truncate">{{ $sub->name }}</h4>
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
                                <span>کڕیار: <strong class="text-slate-800">{{ $sub->client->name }}</strong></span>
                                <span>•</span>
                                <span class="font-mono text-emerald-700 font-black" dir="ltr">+${{ number_format($sub->selling_price, 2) }}</span>
                                <span>•</span>
                                <span class="font-mono text-slate-400">{{ $sub->expiry_date->format('Y-m-d') }}</span>
                            </div>
                        </div>

                        <!-- WhatsApp Reminder Button -->
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $sub->client->phone ?? '') }}?text={{ urlencode('سڵاو بەڕێز ' . $sub->client->name . '، کاتی نوێکردنەوەی ساڵانەی (' . $sub->name . ') لە بەرواری (' . $sub->expiry_date->format('Y-m-d') . ') نزیکە بە بڕی $' . $sub->selling_price . '. تکایە بۆ نوێکردنەوە پەیوەندیمان پێوە بکەن. - iCode Group') }}" 
                           target="_blank" 
                           class="p-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition shadow-xs flex-shrink-0 flex items-center gap-1 text-xs font-bold" 
                           title="واتسئاپ">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>واتسئاپ</span>
                        </a>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">
                        هیچ خزمەتگوزارییەک لە ٣٠ ڕۆژی داهاتوودا بەسەرناچێت. 👍
                    </div>
                @endforelse
            </div>
        </div>

        <!-- 🔴 BOX 2: OUTGOING COMPANY EXPENSES (هەموو مەسرووفاتی iCode لە یەک شوێن) -->
        <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        <h3 class="text-sm md:text-base font-black text-slate-900">خەرجییەکانی کۆمپانیای iCode (مەسرووفاتی تۆ)</h3>
                    </div>
                    <p class="text-xs text-slate-400 font-medium">سێرڤەر، دۆمەینی کۆمپانیا و مەسرووفاتی تۆمارکراو</p>
                </div>
                <a href="{{ route('admin.expenses') }}" class="text-xs font-bold text-indigo-600 hover:underline">بەڕێوەبردنی خەرجییەکان ←</a>
            </div>

            <div class="space-y-2.5">
                @forelse($allExpenses as $exp)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800">
                                    {{ $exp->billing_cycle === 'monthly' ? 'مانگانە' : 'ساڵانە' }}
                                </span>
                                <h4 class="font-extrabold text-slate-900 text-xs">{{ $exp->title }}</h4>
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-2">
                                <span>دابینکەر: <strong>{{ $exp->vendor ?: 'کۆمپانیا' }}</strong></span>
                                <span>•</span>
                                <span class="font-mono text-slate-400">{{ $exp->expense_date->format('Y-m-d') }}</span>
                            </div>
                        </div>

                        <div class="text-left font-mono font-black text-rose-600 text-sm" dir="ltr">
                            -${{ number_format($exp->amount, 2) }}
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">
                        هیچ خەرجییەک تۆمار نەکراوە.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('financialComparisonChart');
        if (!ctx) return;

        const months = @json($months);
        const revenues = @json($chartRevenue);
        const costs = @json($chartCosts);
        const profits = @json($chartProfit);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'داهات ($)',
                        data: revenues,
                        backgroundColor: '#4f46e5',
                        borderRadius: 4,
                    },
                    {
                        label: 'تێچوو ($)',
                        data: costs,
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    },
                    {
                        label: 'قازانج ($)',
                        data: profits,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.5)' },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
