@php
    $U = \App\Livewire\Admin\RenewalRadar::URGENCY;
    $money = fn ($v, $cur = 'USD') => \App\Models\Subscription::formatAmount((float) $v, $cur);
    $typeMeta = [
        'domain' => ['دۆمەین', 'M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        'bundle' => ['دۆمەین + هۆستینگ', 'M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        'hosting' => ['هۆستینگ', 'M5 4h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 9h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2zm2-5.5h.01M7 16.5h.01'],
        'vps' => ['VPS', 'M5 4h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 9h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2zm2-5.5h.01M7 16.5h.01'],
        'email' => ['ئیمەیڵی بزنس', 'M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm-2 2 9 6 9-6'],
        'license' => ['مۆڵەت', 'M15 7a2 2 0 0 1 2 2m4 0a6 6 0 0 1-7.7 5.7L11 17H9v2H7v2H4a1 1 0 0 1-1-1v-2.6a1 1 0 0 1 .3-.7l6-6A6 6 0 1 1 21 9z'],
        'maintenance' => ['پشتگیری', 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-6.9 6.9a2.1 2.1 0 0 1-3-3l6.9-6.9a6 6 0 0 1 7.9-7.9l-3.8 3.8z'],
    ];
    $typeOf = fn ($t) => $typeMeta[$t] ?? ['خزمەتگوزاری', 'M12 8v8m-4-4h8M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'];
    $stageText = [
        0 => 'هێشتا ئاگادار نەکراوەتەوە',
        1 => 'ئاگادارکرایەوە · چاوەڕوانی پارە',
        2 => 'پارە وەرگیرا · لای دابینکەر نوێی بکەرەوە',
    ];
    $cycleText = ['monthly' => '١ مانگ', 'quarterly' => '٣ مانگ', 'semi_annual' => '٦ مانگ', 'annual' => '١ ساڵ', 'biennial' => '٢ ساڵ'];
@endphp

<div class="max-w-6xl mx-auto space-y-4 sm:space-y-5"
     x-data="{ open: $wire.entangle('selectedId').live }"
     @keydown.escape.window="open = null">

    @if(session('quick_added'))
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-bold px-4 py-3">
            ✅ {{ session('quick_added') }} خزمەتگوزاری تۆمارکرا
        </div>
    @endif

    {{-- 1. Summary: money for the next 30 days + 90-day runway --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 sm:gap-4">

        <section class="lg:col-span-2 rounded-3xl bg-slate-900 text-white p-5 grid grid-cols-2 gap-4" aria-label="پارەی ٣٠ ڕۆژی داهاتوو">
            <div class="min-w-0">
                <div class="text-[11px] text-slate-400 font-medium">وەرگرتن لە کڕیاران · ٣٠ ڕۆژ</div>
                <x-money-lines :totals="$summary['collect']" class="font-bold text-xl sm:text-2xl mt-1" />
                <div class="text-[11px] text-slate-400 mt-0.5">{{ $summary['collect_clients'] }} کڕیار</div>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] text-slate-400 font-medium">پارەدان بە دابینکەران</div>
                <x-money-lines :totals="$summary['pay']" class="font-bold text-xl sm:text-2xl mt-1" />
                <div class="text-[11px] text-slate-400 mt-0.5">{{ $summary['pay_count'] }} نوێکردنەوە</div>
            </div>
            <div class="col-span-2 flex items-start justify-between border-t border-white/10 pt-3 text-xs">
                <span class="text-slate-300">قازانجی ئەم نوێکردنەوانە</span>
                <x-money-lines :totals="$summary['profit']" sign="+" class="text-emerald-400 text-sm font-bold text-left" />
            </div>
            @if($summary['unpaid'])
                <div class="col-span-2 -mt-1 flex items-start justify-between rounded-2xl bg-rose-500/20 px-3 py-2 text-[12px]">
                    <span class="text-rose-100 font-bold">پارەی نەدراو لای کڕیاران</span>
                    <x-money-lines :totals="$summary['unpaid']" class="text-rose-200 font-bold text-left" />
                </div>
            @endif            @if($summary['paid_not_renewed'] > 0)
                <div class="col-span-2 -mt-1 flex items-center gap-2 rounded-2xl bg-indigo-500/20 text-indigo-100 px-3 py-2 text-[12px] font-bold">
                    <span class="w-2 h-2 rounded-full bg-indigo-300 animate-pulse flex-shrink-0"></span>
                    {{ $summary['paid_not_renewed'] }} پارەیان داوە بەڵام هێشتا لای دابینکەر نوێ نەکراونەتەوە
                </div>
            @endif
        </section>

        <section class="lg:col-span-3 rounded-3xl bg-white border border-slate-200/80 px-4 pt-4 pb-3" aria-label="٩٠ ڕۆژی داهاتوو">
            <div class="flex items-baseline justify-between text-xs mb-3">
                <strong class="font-display text-[13px] text-slate-900">٩٠ ڕۆژی داهاتوو</strong>
                <span class="text-slate-400">دەست بدە لە خاڵێک</span>
            </div>
            <div class="relative h-16 mx-2">
                <div class="absolute top-7 h-2.5 rounded-full bg-orange-500/25" style="right:0;width:{{ 7 / 90 * 100 }}%"></div>
                <div class="absolute top-7 h-2.5 rounded-full bg-amber-400/25" style="right:{{ 7 / 90 * 100 }}%;width:{{ 23 / 90 * 100 }}%"></div>
                <div class="absolute top-7 h-2.5 rounded-full bg-emerald-500/20" style="right:{{ 30 / 90 * 100 }}%;width:{{ 60 / 90 * 100 }}%"></div>
                <div class="absolute right-0 top-2 bottom-4 w-0.5 rounded bg-slate-900"></div>
                <span class="absolute -top-0.5 right-1.5 text-[10px] font-bold text-slate-900">ئەمڕۆ</span>
                @foreach([30, 60, 90] as $t)
                    <span class="absolute top-11 text-[10px] text-slate-400 font-mono translate-x-1/2" style="right:{{ $t / 90 * 100 }}%">{{ $t }}</span>
                @endforeach
                @foreach($pins as $pin)
                    <button type="button" @click="open = {{ $pin['id'] }}"
                            class="absolute w-[18px] h-[18px] rounded-full border-[3px] border-white shadow translate-x-1/2 hover:scale-125 transition-transform cursor-pointer focus-visible:outline-2 focus-visible:outline-indigo-500"
                            style="right:{{ $pin['right'] }}%;top:{{ 23 - $pin['stack'] * 9 }}px;background:{{ $pin['hex'] }}"
                            title="{{ $pin['label'] }}" aria-label="{{ $pin['label'] }}"></button>
                @endforeach
            </div>
        </section>
    </div>

    {{-- 1b. Clients who have not paid (marked "unpaid" on the service) --}}
    @if($unpaid->isNotEmpty())
        <section class="space-y-2.5" aria-label="پارەی نەداوە">
            <h2 class="font-display font-extrabold text-[13px] text-slate-900 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-[3px] bg-rose-600"></span>
                پارەیان نەداوە
                <span class="font-mono font-medium text-xs text-slate-400">{{ $unpaid->count() }}</span>
            </h2>
            <div class="bg-white border border-slate-200/90 rounded-[20px] divide-y divide-slate-100">
                @foreach($unpaid as $u)
                    <div wire:key="unpaid-{{ $u->id }}" class="flex items-center gap-3 px-4 py-3">
                        <button type="button" @click="open = {{ $u->id }}" class="min-w-0 flex-1 text-start cursor-pointer">
                            <div class="text-sm font-bold text-slate-900 truncate">{{ $u->client?->business_name ?: $u->client?->name }}</div>
                            <div class="text-[11px] text-slate-500 truncate font-mono" dir="ltr" style="text-align:right">{{ $u->domain_name ?: $u->name }}</div>
                        </button>
                        <span class="font-mono text-[13px] font-bold text-rose-600" dir="ltr">{{ $u->selling_label }}</span>
                        @if($u->client?->whatsapp_number)
                            <a href="https://wa.me/{{ $u->client->whatsapp_number }}" target="_blank" rel="noopener" class="flex-shrink-0 p-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100" aria-label="واتسئاپ">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                            </a>
                        @endif
                        <button type="button" wire:click="togglePaid({{ $u->id }})" wire:loading.attr="disabled" wire:target="togglePaid({{ $u->id }})"
                                class="flex-shrink-0 rounded-xl bg-slate-900 hover:bg-slate-700 disabled:opacity-50 text-white text-xs font-bold px-3 py-2 cursor-pointer">دای</button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    {{-- 2. Type filter --}}
    <a href="{{ route('admin.quick-add') }}"
       class="fixed z-30 left-4 bottom-[calc(5.5rem+env(safe-area-inset-bottom))] lg:bottom-8 lg:left-8 w-14 h-14 rounded-[20px] bg-indigo-600 hover:bg-indigo-700 text-white grid place-items-center shadow-[0_10px_24px_-6px_rgba(79,70,229,.7)] transition"
       aria-label="زیادکردنی خێرا" title="زیادکردنی خێرا">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
    </a>
    <div class="flex gap-2 overflow-x-auto -mx-3 px-3 sm:mx-0 sm:px-0 pb-1 [scrollbar-width:none]" role="toolbar" aria-label="جۆری خزمەتگوزاری">
        @foreach(\App\Livewire\Admin\RenewalRadar::FILTERS as $key => $f)
            <button type="button" wire:click="setFilter('{{ $key }}')" aria-pressed="{{ $filter === $key ? 'true' : 'false' }}"
                    class="flex-none inline-flex items-center gap-1.5 rounded-full border px-4 py-1.5 text-[13px] font-semibold transition cursor-pointer {{ $filter === $key ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300' }}">
                {{ $f['label'] }}
                <span class="font-mono text-[11px] opacity-60">{{ $counts[$key] }}</span>
            </button>
        @endforeach
        <a href="{{ route('admin.subscriptions') }}" class="flex-none inline-flex items-center gap-1 rounded-full px-3 py-1.5 text-[13px] font-semibold text-indigo-600 hover:bg-indigo-50 transition ms-auto">
            هەموو خزمەتگوزارییەکان
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </a>
    </div>

    {{-- 3. Tickets grouped by urgency --}}
    @if($isEmpty)
        <div class="rounded-3xl bg-white border border-slate-200 p-10 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 grid place-items-center mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="font-display font-extrabold text-slate-900">هیچ شتێک لە ٩٠ ڕۆژی داهاتوودا بەسەرناچێت</p>
            <p class="text-sm text-slate-500 mt-1">کاتێک دۆمەین یان هۆستینگێک نزیک بێتەوە، لێرە و لە تێلێگرام دەردەکەوێت.</p>
        </div>
    @elseif($groups->isEmpty())
        <p class="text-sm text-slate-500 py-6 text-center">هیچ شتێک لەم جۆرە نییە.</p>
    @endif

    @foreach($groups as $key => $items)
        <section class="space-y-2.5" wire:key="group-{{ $key }}">
            <h2 class="font-display font-extrabold text-[13px] text-slate-900 flex items-center gap-2 pt-1">
                <span class="w-2.5 h-2.5 rounded-[3px] {{ $U[$key]['dot'] }}"></span>
                {{ $U[$key]['title'] }}
                <span class="font-mono font-medium text-xs text-slate-400">{{ $items->count() }}</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2.5">
                @foreach($items as $sub)
                    @php
                        $days = $sub->days_until_expiry;
                        $hex = $U[$key]['hex'];
                        $pct = $days < 0 ? 100 : max(4, 100 - $days / 90 * 100);
                        [$tLabel, $tIcon] = $typeOf($sub->type);
                    @endphp
                    <button type="button" wire:key="t-{{ $sub->id }}" @click="open = {{ $sub->id }}"
                            class="group relative grid grid-cols-[auto_minmax(0,1fr)] text-start w-full bg-white border border-slate-200/90 rounded-[20px] shadow-[0_1px_2px_rgba(15,23,42,.05),0_8px_24px_-12px_rgba(15,23,42,.12)] hover:border-slate-300 transition cursor-pointer focus-visible:outline-2 focus-visible:outline-indigo-500">
                        {{-- ticket stub with countdown ring --}}
                        <div class="relative px-3 py-3.5 grid place-items-center border-e-2 border-dashed border-slate-200">
                            <span class="absolute -top-2 -left-2 w-4 h-4 rounded-full bg-slate-100 border border-slate-200/90"></span>
                            <span class="absolute -bottom-2 -left-2 w-4 h-4 rounded-full bg-slate-100 border border-slate-200/90"></span>
                            <div class="w-[58px] h-[58px] rounded-full grid place-items-center" style="background:conic-gradient({{ $hex }} {{ $pct }}%, #f1f5f9 0)">
                                <div class="w-[46px] h-[46px] rounded-full bg-white grid place-items-center text-center leading-none">
                                    <span>
                                        <b class="font-mono text-[17px]" style="color:{{ $hex }}">{{ abs($days) }}</b>
                                        <small class="block text-[9px] text-slate-400 mt-0.5">{{ $days < 0 ? 'ڕۆژ پێش' : ($days === 0 ? 'ئەمڕۆ' : 'ڕۆژ ماوە') }}</small>
                                    </span>
                                </div>
                            </div>
                        </div>
                        {{-- ticket body --}}
                        <div class="px-3.5 py-3 min-w-0 grid gap-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tIcon }}"/></svg>
                                    {{ $tLabel }}
                                </span>
                                <span class="font-mono text-[13px] font-bold text-slate-900" dir="ltr">{{ $money($sub->selling_price, $sub->currency) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 min-w-0">
                                @if($sub->has_registry_mismatch)
                                    <span class="flex-shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-sky-50 text-sky-700" title="بەرواری تۆمارگە جیاوازە">تۆمارگە ≠</span>
                                @endif
                                <div class="font-mono font-bold text-[14px] text-slate-900 truncate text-right flex-1" dir="ltr">{{ $sub->domain_name ?: $sub->name }}</div>
                            </div>
                            <div class="text-xs text-slate-500 truncate">{{ $sub->client->business_name ?: $sub->client->name }}</div>
                            <div class="flex gap-1 mt-1" aria-hidden="true">
                                @for($i = 1; $i <= 3; $i++)
                                    <i class="flex-1 h-1 rounded-full {{ $i <= $sub->renewal_stage ? 'bg-indigo-600' : 'bg-slate-200' }}"></i>
                                @endfor
                            </div>
                            <div class="text-[11px] {{ $sub->renewal_stage === 2 ? 'text-indigo-700 font-bold' : 'text-slate-500' }}">{{ $stageText[$sub->renewal_stage] ?? '' }}</div>
                        </div>
                    </button>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- 4. Your own servers (costs, not client revenue) --}}
    @if($servers->isNotEmpty())
        <section class="space-y-2.5 pt-2">
            <h2 class="font-display font-extrabold text-[13px] text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 9h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2zm2-5.5h.01M7 16.5h.01"/></svg>
                خزمەتگوزارییەکانی خۆم
                <span class="font-mono font-medium text-xs text-slate-400" dir="ltr">{{ \App\Support\Money::formatTotals(\App\Support\Money::totals($servers, fn ($x) => $x->cost, fn ($x) => $x->currency)) }}</span>
            </h2>
            <div class="bg-white border border-slate-200/90 rounded-[20px] divide-y divide-slate-100">
                @foreach($servers as $srv)
                    @php $sk = \App\Livewire\Admin\RenewalRadar::urgencyOf($srv->days_until_renewal); @endphp
                    <div wire:key="srv-{{ $srv->id }}" class="flex items-center gap-3 px-4 py-3">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $U[$sk]['dot'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-900 truncate">{{ $srv->name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">
                                {{ $srv->provider }} · {{ $srv->renewal_status_text }}
                                @if($srv->auto_renew)<span class="ms-1 px-1.5 rounded bg-slate-100 text-slate-600 font-bold">خۆکار</span>@endif
                            </div>
                        </div>
                        <span class="font-mono text-[13px] font-bold text-slate-900" dir="ltr">{{ $money($srv->cost, $srv->currency) }}</span>
                        <button type="button" wire:click="renewServer({{ $srv->id }}, '{{ $srv->renewal_date?->toDateString() }}')" wire:confirm="پارەی {{ $srv->name }} دراوە؟ بەرواری نوێکردنەوە درێژ دەکرێتەوە."
                                class="flex-shrink-0 rounded-xl bg-slate-100 hover:bg-indigo-600 hover:text-white text-slate-700 text-xs font-bold px-3 py-2 transition cursor-pointer">دراوە</button>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- 5. Detail sheet: bottom sheet on phones, side drawer on desktop --}}
    <div x-show="open" x-transition.opacity.duration.200ms @click="open = null"
         class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-[2px]" style="display:none"></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full lg:translate-y-0 lg:-translate-x-full" x-transition:enter-end="translate-y-0 lg:translate-x-0"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 lg:translate-x-0" x-transition:leave-end="translate-y-full lg:translate-y-0 lg:-translate-x-full"
         class="fixed z-50 inset-x-0 bottom-0 max-h-[88vh] overflow-y-auto bg-white rounded-t-[28px] shadow-2xl px-5 pt-2.5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]
                lg:inset-y-0 lg:left-0 lg:right-auto lg:bottom-auto lg:w-[440px] lg:max-h-none lg:rounded-none lg:rounded-s-[28px] lg:pt-6"
         role="dialog" aria-modal="true" aria-label="وردەکاری نوێکردنەوە" style="display:none">

        <div class="w-10 h-1.5 rounded-full bg-slate-200 mx-auto mb-3 lg:hidden"></div>

        <div x-show="open != {{ $selected?->id ?? 'null' }}" class="space-y-3 animate-pulse py-2">
                <div class="h-5 w-24 bg-slate-100 rounded-lg"></div>
                <div class="h-7 w-2/3 bg-slate-100 rounded-lg"></div>
                <div class="h-32 bg-slate-100 rounded-2xl"></div>
                <div class="h-40 bg-slate-100 rounded-2xl"></div>
        </div>
        @if($selected)
            @php
                $s = $selected;
                $sDays = $s->days_until_expiry;
                $sKey = \App\Livewire\Admin\RenewalRadar::urgencyOf($sDays);
                [$sLabel, $sIcon] = $typeOf($s->type);
                $waKu = $s->whatsappUrl('ku');
                $waAr = $s->whatsappUrl('ar');
            @endphp
            <div wire:key="sheet-{{ $s->id }}" x-show="open == {{ $s->id }}" x-data="{ lang: 'ku', confirmRenew: false, copied: false }" class="space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sIcon }}"/></svg>
                        {{ $sLabel }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-lg {{ $U[$sKey]['soft'] }}">{{ $s->expiry_status_text }}</span>
                        <button type="button" @click="open = null" class="p-1.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 cursor-pointer" aria-label="داخستن">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <h3 class="font-mono font-bold text-lg text-slate-900 break-all text-right" dir="ltr">{{ $s->domain_name ?: $s->name }}</h3>

                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-[13px] bg-slate-50 rounded-2xl px-4 py-3">
                    <dt class="text-slate-500">کڕیار</dt><dd class="text-left font-semibold text-slate-800">{{ $s->client->business_name ?: $s->client->name }}</dd>
                    <dt class="text-slate-500">واتسئاپ</dt><dd class="text-left font-mono text-slate-800" dir="ltr">{{ $s->client->whatsapp_number ? '+' . $s->client->whatsapp_number : '—' }}</dd>
                    <dt class="text-slate-500">دابینکەر</dt><dd class="text-left text-slate-800">{{ $s->provider ?: '—' }}@if($s->server) · {{ $s->server->name }}@endif</dd>
                    <dt class="text-slate-500">بەسەرچوون</dt><dd class="text-left font-mono text-slate-800">{{ $s->expiry_date->format('Y-m-d') }}</dd>
                    @if(in_array($s->type, ['domain', 'bundle']) && $s->registry_checked_at)
                        <dt class="text-slate-500">تۆمارگە (RDAP)</dt>
                        <dd class="text-left font-mono {{ $s->has_registry_mismatch ? 'text-sky-700 font-bold' : 'text-slate-800' }}">{{ $s->registry_expiry_date?->format('Y-m-d') ?? 'بەردەست نییە' }}</dd>
                    @endif
                    <dt class="text-slate-500">فرۆش / تێچوو</dt><dd class="text-left font-mono text-slate-800" dir="ltr">{{ $money($s->selling_price, $s->currency) }} / {{ $money($s->cost_price, $s->currency) }}</dd>
                </dl>

                @if($s->has_registry_mismatch)
                    <div class="rounded-2xl border px-4 py-3 text-[13px] {{ $s->registry_expiry_date->lt($s->expiry_date) ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-sky-200 bg-sky-50 text-sky-900' }}">
                        <p class="font-semibold">{{ $s->registry_expiry_date->lt($s->expiry_date) ? 'ئاگاداربە: تۆمارگە دەڵێت ئەم دۆمەینە زووتر بەسەردەچێت.' : 'تۆمارگە دەڵێت ئەم دۆمەینە نوێکراوەتەوە.' }}</p>
                        <button type="button" wire:click="useRegistryDate({{ $s->id }})" class="mt-2 rounded-xl bg-white/80 hover:bg-white px-3 py-1.5 text-xs font-bold cursor-pointer">بەرواری تۆمارگە بەکاربهێنە</button>
                    </div>
                @endif

                {{-- Has the client paid for this period? --}}
                <button type="button" wire:click="togglePaid({{ $s->id }})" wire:loading.attr="disabled" wire:target="togglePaid({{ $s->id }})"
                        class="w-full flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold cursor-pointer transition {{ $s->is_paid ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <span>{{ $s->is_paid ? '✓ پارەی داوە' : '✗ پارەی نەداوە' }}</span>
                    <span class="text-xs font-medium opacity-75">{{ $s->is_paid ? 'گۆڕین بۆ نەداوە' : 'دەست لێبدە کاتێک دای' }}</span>
                </button>
                {{-- Renewal steps --}}
                <ol class="relative">
                    @foreach([1 => ['ئاگادارکرایەوە', 'نامە بۆ کڕیار نێردرا'], 2 => ['پارەی وەرگیرا', 'FIB · FastPay · کاش']] as $n => [$t, $d])
                        @php $done = $s->renewal_stage >= $n; @endphp
                        <li class="relative pb-2">
                            <span class="absolute right-[13px] top-9 bottom-0 w-0.5 {{ $done ? 'bg-indigo-600' : 'bg-slate-200' }}"></span>
                            <button type="button" wire:click="setStage({{ $s->id }}, {{ $n }})" class="w-full grid grid-cols-[28px_1fr_auto] items-center gap-3 py-1.5 text-start cursor-pointer group">
                                <span class="relative z-10 w-7 h-7 rounded-full grid place-items-center text-xs font-extrabold border-2 {{ $done ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-300 text-slate-500 group-hover:border-indigo-400' }}">
                                    @if($done)<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>@else{{ $n }}@endif
                                </span>
                                <span><span class="block text-sm font-bold text-slate-900">{{ $t }}</span><span class="block text-[11px] text-slate-500">{{ $d }}</span></span>
                                <span class="text-[11px] text-slate-400">{{ $done ? 'تەواو' : 'دەست لێبدە' }}</span>
                            </button>
                        </li>
                    @endforeach
                    <li>
                        <button type="button" @click="confirmRenew = true" x-show="!confirmRenew" class="w-full grid grid-cols-[28px_1fr_auto] items-center gap-3 py-1.5 text-start cursor-pointer group">
                            <span class="w-7 h-7 rounded-full grid place-items-center text-xs font-extrabold border-2 bg-white border-slate-300 text-slate-500 group-hover:border-indigo-400">3</span>
                            <span><span class="block text-sm font-bold text-slate-900">لای دابینکەر نوێکرایەوە</span><span class="block text-[11px] text-slate-500">بەروار {{ $cycleText[$s->billing_cycle] ?? '١ ساڵ' }} درێژ دەبێتەوە</span></span>
                            <span class="text-[11px] text-slate-400">دەست لێبدە</span>
                        </button>
                        <div x-show="confirmRenew" x-transition class="rounded-2xl border border-indigo-200 bg-indigo-50 p-3 space-y-2" style="display:none">
                            <p class="text-[13px] text-indigo-900 font-semibold">لە {{ $s->provider ?: 'دابینکەر' }} نوێت کردەوە؟ بەرواری بەسەرچوون {{ $cycleText[$s->billing_cycle] ?? '١ ساڵ' }} درێژ دەکرێتەوە.</p>
                            <div class="flex gap-2">
                                <button type="button" wire:click="renew({{ $s->id }})" class="flex-1 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 cursor-pointer">بەڵێ، نوێکرایەوە</button>
                                <button type="button" @click="confirmRenew = false" class="rounded-xl bg-white text-slate-600 text-sm font-bold px-4 py-2.5 cursor-pointer">نەخێر</button>
                            </div>
                        </div>
                    </li>
                </ol>

                {{-- WhatsApp message --}}
                <div>
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1.5">
                        <span>نامەی ئامادە بۆ واتسئاپ</span>
                        <span class="inline-flex gap-1">
                            <button type="button" @click="lang = 'ku'" :class="lang === 'ku' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200'" class="border rounded-lg px-2 py-0.5 text-[11px] cursor-pointer">کوردی</button>
                            <button type="button" @click="lang = 'ar'" :class="lang === 'ar' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200'" class="border rounded-lg px-2 py-0.5 text-[11px] cursor-pointer">عربي</button>
                        </span>
                    </div>
                    <div x-ref="msgKu" x-show="lang === 'ku'" class="rounded-[18px] rounded-bl-md bg-emerald-50 text-slate-800 px-4 py-3 text-[13px] whitespace-pre-line leading-7">{{ $s->whatsappMessage('ku') }}</div>
                    <div x-ref="msgAr" x-show="lang === 'ar'" lang="ar" class="rounded-[18px] rounded-bl-md bg-emerald-50 text-slate-800 px-4 py-3 text-[13px] whitespace-pre-line leading-7" style="display:none">{{ $s->whatsappMessage('ar') }}</div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    @if($waKu)
                        <a :href="lang === 'ku' ? @js($waKu) : @js($waAr)" target="_blank" rel="noopener"
                           @click="$wire.markNotified({{ $s->id }}, lang)"
                           class="rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-bold py-3 flex items-center justify-center gap-2 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            ناردن بە واتسئاپ
                        </a>
                    @else
                        <a href="{{ route('admin.clients') }}" class="rounded-2xl bg-slate-100 text-slate-500 text-[13px] font-bold py-3 flex items-center justify-center text-center px-2">ژمارەی واتسئاپ نییە، زیادی بکە</a>
                    @endif
                    <button type="button"
                            @click="navigator.clipboard.writeText((lang === 'ku' ? $refs.msgKu : $refs.msgAr).innerText).then(() => { copied = true; setTimeout(() => copied = false, 1600) })"
                            class="rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-sm font-bold py-3 cursor-pointer transition">
                        <span x-show="!copied">کۆپیکردنی نامە</span><span x-show="copied" style="display:none">کۆپی کرا ✓</span>
                    </button>
                    <button type="button" wire:click="createInvoice({{ $s->id }})" wire:loading.attr="disabled" class="rounded-2xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold py-3 cursor-pointer transition">دروستکردنی وەسڵ</button>
                    <button type="button" wire:click="cancel({{ $s->id }})" wire:confirm="«{{ $s->name }}» لە لیستی نوێکردنەوە لابەرێت؟ (کڕیار نایەوێت)" class="rounded-2xl bg-white border border-slate-200 hover:border-rose-200 hover:text-rose-600 text-slate-500 text-sm font-bold py-3 cursor-pointer transition">کڕیار نایەوێت</button>
                </div>

                @if($history->isNotEmpty())
                    <div class="pt-1">
                        <div class="text-xs text-slate-500 mb-2">دوایین چالاکییەکان</div>
                        <ul class="space-y-1.5">
                            @foreach($history as $h)
                                <li class="flex items-center justify-between gap-3 text-[12px]">
                                    <span class="flex items-center gap-2 min-w-0">
                                        <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $h->channel === 'whatsapp' ? 'bg-emerald-500' : ($h->channel === 'telegram' ? 'bg-sky-500' : 'bg-slate-400') }}"></span>
                                        <span class="truncate text-slate-700">{{ match($h->type) { 'subscription_renewal' => $h->channel === 'whatsapp' ? 'نامەی واتسئاپ نێردرا' : 'ئاگاداری تێلێگرام', 'renewal_paid' => 'پارە وەرگیرا', 'subscription_renewed' => $h->message, default => $h->message } }}</span>
                                    </span>
                                    <span class="font-mono text-slate-400 flex-shrink-0" dir="ltr">{{ optional($h->sent_at ?? $h->created_at)->format('m-d H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
