<div class="space-y-6" x-data="{ viewMode: 'annual' }">

    <!-- 1. Header & period -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 md:p-5 rounded-2xl md:rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-lg md:text-xl font-black text-slate-900">پوختەی دارایی iCode</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">دۆلار و دینار هەریەکە بە جیا، بێ گۆڕینەوە</p>
        </div>
        <div class="inline-flex bg-slate-100 p-1 rounded-xl border border-slate-200/60 self-start sm:self-auto">
            <button @click="viewMode = 'annual'" :class="viewMode === 'annual' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 font-bold hover:text-slate-900'" class="px-3 py-1.5 text-xs rounded-lg transition cursor-pointer">ساڵانە</button>
            <button @click="viewMode = 'monthly'" :class="viewMode === 'monthly' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 font-bold hover:text-slate-900'" class="px-3 py-1.5 text-xs rounded-lg transition cursor-pointer">مانگانە</button>
        </div>
    </div>

    <!-- 2. Profit / income / expenses per currency -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="sm:col-span-2 lg:col-span-1 bg-linear-to-br from-emerald-600 to-teal-700 text-white rounded-2xl md:rounded-3xl p-5 shadow-sm space-y-2">
            <div class="text-xs font-extrabold text-emerald-100" x-text="viewMode === 'annual' ? 'قازانجی ساڵانە' : 'قازانجی مانگانە'">قازانجی ساڵانە</div>
            <x-money-lines x-show="viewMode === 'annual'" :totals="$annualProfit" class="text-2xl md:text-3xl font-black" />
            <x-money-lines x-show="viewMode === 'monthly'" style="display:none" :totals="$monthlyProfit" class="text-2xl md:text-3xl font-black" />
            <p class="text-[11px] text-emerald-100/90 pt-2 border-t border-emerald-500/40">داهات - خەرجی، بۆ هەر دراوێک بە جیا</p>
        </div>

        <div class="bg-white rounded-2xl md:rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500" x-text="viewMode === 'annual' ? 'داهات لە کڕیاران (ساڵانە)' : 'داهات لە کڕیاران (مانگانە)'">داهات لە کڕیاران</div>
            <x-money-lines x-show="viewMode === 'annual'" :totals="$annualRevenue" class="text-xl md:text-2xl font-black text-slate-900" />
            <x-money-lines x-show="viewMode === 'monthly'" style="display:none" :totals="$monthlyRevenue" class="text-xl md:text-2xl font-black text-slate-900" />
            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100">{{ $activeCount }} خزمەتگوزاری چالاک · {{ $clientCount }} کڕیار</p>
        </div>

        <a href="{{ route('admin.servers') }}" class="block bg-white rounded-2xl md:rounded-3xl p-5 border border-slate-200/80 shadow-xs space-y-2 hover:border-rose-200 transition">
            <div class="text-xs font-bold text-slate-500" x-text="viewMode === 'annual' ? 'خەرجی (ساڵانە)' : 'خەرجی (مانگانە)'">خەرجی</div>
            <x-money-lines x-show="viewMode === 'annual'" :totals="$annualCosts" class="text-xl md:text-2xl font-black text-rose-600" />
            <x-money-lines x-show="viewMode === 'monthly'" style="display:none" :totals="$monthlyCosts" class="text-xl md:text-2xl font-black text-rose-600" />
            <p class="text-[11px] text-slate-400 pt-2 border-t border-slate-100">{{ $ownServices->count() }} خزمەتگوزاری خۆم + خەرجییەکان</p>
        </a>

        <a href="{{ route('admin.subscriptions') }}?statusFilter=unpaid" class="block rounded-2xl md:rounded-3xl p-5 border shadow-xs space-y-2 transition {{ $unpaid->isNotEmpty() ? 'bg-rose-50 border-rose-200 hover:border-rose-300' : 'bg-white border-slate-200/80' }}">
            <div class="text-xs font-bold {{ $unpaid->isNotEmpty() ? 'text-rose-700' : 'text-slate-500' }}">پارەیان نەداوە · {{ $unpaid->count() }}</div>
            <x-money-lines :totals="$unpaidTotals" class="text-xl md:text-2xl font-black {{ $unpaid->isNotEmpty() ? 'text-rose-600' : 'text-slate-400' }}" />
            <p class="text-[11px] {{ $unpaid->isNotEmpty() ? 'text-rose-600' : 'text-slate-400' }} pt-2 border-t {{ $unpaid->isNotEmpty() ? 'border-rose-100' : 'border-slate-100' }}">کڕیارانی پارە نەداو</p>
        </a>
    </div>

    <!-- 3. Unpaid clients -->
    @if($unpaid->isNotEmpty())
        <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-rose-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm md:text-base font-black text-slate-900 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> پارەیان نەداوە</h3>
                <a href="{{ route('admin.renewals') }}" class="text-xs font-bold text-indigo-600 hover:underline">ڕادار ←</a>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($unpaid as $u)
                    <a href="{{ route('admin.renewals', ['open' => $u->id]) }}" class="flex items-center justify-between gap-3 py-2.5 hover:bg-slate-50 rounded-lg px-1">
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-slate-900 truncate">{{ $u->client?->business_name ?: $u->client?->name }}</span>
                            <span class="block text-[11px] text-slate-500 font-mono truncate" dir="ltr" style="text-align:right">{{ $u->domain_name ?: $u->name }}</span>
                        </span>
                        <span class="font-mono font-black text-rose-600 text-sm flex-shrink-0" dir="ltr">{{ $u->selling_label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 4. Upcoming renewals vs your own costs -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm md:text-base font-black text-slate-900 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> نوێکردنەوەی کڕیاران · ٣٠ ڕۆژ</h3>
                    <p class="text-xs text-slate-400 font-medium">پارەیەک کە بۆت دێت</p>
                </div>
                <a href="{{ route('admin.renewals') }}" class="text-xs font-bold text-indigo-600 hover:underline">ڕادار ←</a>
            </div>
            <div class="space-y-2.5">
                @forelse($expiringSubscriptions as $sub)
                    <div class="p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-100 flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black {{ $sub->days_until_expiry <= 7 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">{{ $sub->expiry_status_text }}</span>
                                <h4 class="font-extrabold text-slate-900 text-xs truncate">{{ $sub->domain_name ?: $sub->name }}</h4>
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
                                <span>{{ $sub->client?->business_name ?: $sub->client?->name }}</span>
                                <span>•</span>
                                <span class="font-mono text-emerald-700 font-black" dir="ltr">{{ $sub->selling_label }}</span>
                            </div>
                        </div>
                        <a href="{{ $sub->whatsappUrl() ?? route('admin.clients') }}" target="_blank" class="p-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition flex-shrink-0 text-xs font-bold">واتسئاپ</a>
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-400 text-xs">هیچ خزمەتگوزارییەک لە ٣٠ ڕۆژی داهاتوودا بەسەرناچێت.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl md:rounded-3xl p-4 md:p-6 border border-slate-200/80 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm md:text-base font-black text-slate-900 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> خەرجییەکانی خۆت</h3>
                    <p class="text-xs text-slate-400 font-medium">سێرڤەر، دۆمەین و خەرجییە تۆمارکراوەکان</p>
                </div>
                <a href="{{ route('admin.servers') }}" class="text-xs font-bold text-indigo-600 hover:underline">بەڕێوەبردن ←</a>
            </div>
            <div class="space-y-2.5">
                @foreach($ownServices as $own)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800">{{ $own->kind_label }}</span>
                                <h4 class="font-extrabold text-slate-900 text-xs truncate">{{ $own->name }}</h4>
                            </div>
                            <div class="text-xs text-slate-500">{{ $own->renewal_status_text }}</div>
                        </div>
                        <div class="text-left font-mono font-black text-rose-600 text-sm flex-shrink-0" dir="ltr">-{{ $own->cost_label }}</div>
                    </div>
                @endforeach
                @foreach($expenses as $exp)
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-slate-200 text-slate-700">{{ ['monthly' => 'مانگانە', 'annual' => 'ساڵانە'][$exp->billing_cycle] ?? 'یەکجار' }}</span>
                                <h4 class="font-extrabold text-slate-900 text-xs truncate">{{ $exp->title }}</h4>
                            </div>
                            <div class="text-xs text-slate-500">{{ $exp->vendor ?: '—' }} · <span class="font-mono">{{ $exp->expense_date?->format('Y-m-d') }}</span></div>
                        </div>
                        <div class="text-left font-mono font-black text-rose-600 text-sm flex-shrink-0" dir="ltr">-{{ \App\Support\Money::format((float) $exp->amount, $exp->currency) }}</div>
                    </div>
                @endforeach
                @if($ownServices->isEmpty() && $expenses->isEmpty())
                    <div class="py-6 text-center text-slate-400 text-xs">هیچ خەرجییەک تۆمار نەکراوە.</div>
                @endif
            </div>
        </div>
    </div>
</div>
