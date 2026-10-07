<div class="space-y-6">
    @php $fmt = fn ($v, $c) => \App\Support\Money::format((float) $v, $c); @endphp

    <!-- Header -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div class="space-y-2">
            <a href="{{ route('admin.projects') }}" class="text-xs font-bold text-indigo-600">→ پڕۆژەکان</a>
            <h1 class="text-xl font-black text-slate-900">{{ $project->title }}</h1>
            <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $project->status_label }}</span>
                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $project->category_label }}</span>
                <span class="px-2 py-0.5 rounded {{ $project->is_public ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $project->is_public ? 'بڵاوکراوە لە پۆرتفۆلیۆ' : 'ناوخۆیی' }}</span>
                @if($project->archived_at)<span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700">ئەرشیف</span>@endif
            </div>
            <div class="text-xs text-slate-600 space-y-0.5">
                <div>کڕیار: <strong>{{ $project->client?->display_name ?? '— (پڕۆژەی پۆرتفۆلیۆ) —' }}</strong></div>
                <div>دەستپێک: <span class="font-mono" dir="ltr">{{ $project->start_date?->format('Y-m-d') ?? '—' }}</span> · تەسلیمکردن: <span class="font-mono" dir="ltr">{{ $project->completion_date?->format('Y-m-d') ?? '—' }}</span></div>
                @if($project->live_url)<div>URL: <a href="{{ $project->live_url }}" target="_blank" class="font-mono text-indigo-600" dir="ltr">{{ $project->live_url }}</a></div>@endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($project->client_id && ! $project->archived_at)
                <a href="{{ route('admin.invoices', ['new' => 1, 'project' => $project->id]) }}" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold">+ دروستکردنی وەسڵ</a>
            @endif
            @if($project->archived_at)
                <button wire:click="unarchive" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold">گەڕاندنەوە لە ئەرشیف</button>
            @else
                <button wire:click="archive" wire:confirm="پڕۆژەکە ئەرشیف بکرێت؟ مێژووی دارایی دەمێنێت." class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold">ئەرشیف</button>
            @endif
        </div>
    </div>

    <!-- Money per currency -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-500">نرخی دروستکردن (بڕگەی یەکجار)</div>
            <x-money-lines :totals="$buildPrice" class="text-lg font-black text-slate-900" />
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-500">کۆی وەسڵی دەرچوو</div>
            <x-money-lines :totals="$invoiced" class="text-lg font-black text-slate-900" />
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-500">وەرگیراو</div>
            <x-money-lines :totals="$received" class="text-lg font-black text-emerald-700" />
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200 space-y-1">
            <div class="text-xs font-bold text-slate-500">قەرزی ماوە</div>
            <x-money-lines :totals="$balance" class="text-lg font-black text-amber-700" />
        </div>
    </div>

    <!-- Services -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-3">
        <h3 class="font-black text-slate-900">خزمەتگوزارییەکان</h3>
        @forelse($project->subscriptions as $sub)
            <div class="border border-slate-100 rounded-xl p-3 text-xs space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <span class="font-bold text-slate-900">{{ $sub->domain_name ?: $sub->name }}</span>
                        <span class="text-slate-500">· {{ $sub->type_label }}</span>
                        @if($sub->status === 'cancelled')<span class="text-rose-600 font-bold">· هەڵوەشاوە</span>@endif
                    </div>
                    <div class="font-mono" dir="ltr">{{ $sub->start_date?->format('Y-m-d') }} → <strong class="{{ $sub->days_until_expiry < 30 ? 'text-rose-600' : '' }}">{{ $sub->expiry_date?->format('Y-m-d') }}</strong></div>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-slate-600">
                    <span>نرخی نوێکردنەوە: <strong class="font-mono" dir="ltr">{{ $sub->selling_label }}</strong> / {{ \App\Models\Server::CYCLES[$sub->billing_cycle] ?? $sub->billing_cycle }}</span>
                    <span>دۆخی پارە: <strong class="{{ $sub->is_paid ? 'text-emerald-700' : 'text-rose-600' }}">{{ $sub->is_paid ? 'دراوە' : 'نەدراوە' }}</strong></span>
                    <span>تێچوو: {{ $sub->cost_basis_label }}</span>
                    @if($sub->server)<span>VPSی میواندار: {{ $sub->server->name }}</span>@endif
                </div>
                @if($sub->periods->count() > 1)
                    <details>
                        <summary class="cursor-pointer text-slate-500">مێژووی ماوەکان ({{ $sub->periods->count() }})</summary>
                        <div class="mt-1 space-y-0.5">
                            @foreach($sub->periods->sortByDesc('starts_on') as $period)
                                <div class="font-mono {{ $period->status !== 'active' ? 'text-slate-400' : '' }}" dir="ltr">
                                    {{ $period->starts_on->format('Y-m-d') }} → {{ $period->expires_on->format('Y-m-d') }} · {{ $period->price_label }} · {{ \App\Models\ServicePeriod::ORIGINS[$period->origin] ?? $period->origin }}
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        @empty
            <p class="text-xs text-slate-400">هیچ خزمەتگوزارییەک بەم پڕۆژەیە نەبەستراوە. لە وەسڵدا بڕگەی هۆست/دۆمەین زیاد بکە و «خزمەتگوزاری دروست بکە» هەڵبژێرە، یان لە بەشی خزمەتگوزاری پڕۆژە دیاری بکە.</p>
        @endforelse
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Invoices -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-3">
            <h3 class="font-black text-slate-900">وەسڵەکان</h3>
            @forelse($invoices as $inv)
                <div class="flex items-center justify-between text-xs border-b border-slate-100 pb-2">
                    <div>
                        <a href="{{ route('admin.invoices', ['search' => $inv->invoice_number]) }}" class="font-mono font-bold text-indigo-600" dir="ltr">{{ $inv->invoice_number }}</a>
                        <span class="text-slate-500 font-mono" dir="ltr">· {{ $inv->issue_date->format('Y-m-d') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold" dir="ltr">{{ $fmt($inv->total, $inv->currency) }}</span>
                        @include('livewire.admin.partials.invoice-status', ['invoice' => $inv])
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400">هیچ وەسڵێک نییە.</p>
            @endforelse
        </div>

        <!-- Payments -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-3">
            <h3 class="font-black text-slate-900">پارەدانەکان</h3>
            @forelse($payments as $p)
                <div class="flex items-center justify-between text-xs border-b border-slate-100 pb-2">
                    <span class="font-mono" dir="ltr">{{ $p->paid_on?->format('Y-m-d') ?? 'بەروار نەزانراوە' }} · {{ $p->invoice->invoice_number }}</span>
                    <span class="font-mono font-bold {{ $p->amount < 0 ? 'text-rose-600' : 'text-emerald-700' }}" dir="ltr">{{ $fmt($p->amount, $p->currency) }}</span>
                </div>
            @empty
                <p class="text-xs text-slate-400">هیچ پارەدانێک تۆمار نەکراوە.</p>
            @endforelse
        </div>

        <!-- Contracts -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-3">
            <h3 class="font-black text-slate-900">گرێبەستەکان</h3>
            @forelse($project->contracts as $contract)
                <div class="flex items-center justify-between text-xs">
                    <span>{{ $contract->title }} <span class="font-mono text-slate-400" dir="ltr">{{ $contract->contract_number }}</span></span>
                    <span class="font-mono" dir="ltr">{{ $fmt($contract->total_amount, $contract->currency) }}</span>
                </div>
            @empty
                <p class="text-xs text-slate-400">هیچ گرێبەستێک نییە.</p>
            @endforelse
        </div>

        <!-- Direct costs -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-3">
            <h3 class="font-black text-slate-900">تێچووی ڕاستەوخۆی تۆمارکراو</h3>
            <x-money-lines :totals="$directCostTotals" class="text-base font-black text-rose-600" />
            @foreach($directCosts as $e)
                <div class="flex justify-between text-xs"><span>{{ $e->title }} · <span class="font-mono" dir="ltr">{{ $e->expense_date->format('Y-m-d') }}</span></span><span class="font-mono" dir="ltr">{{ $fmt($e->amount, $e->currency) }}</span></div>
            @endforeach
            <p class="text-[11px] text-slate-400">تەنها کڕینی تایبەت بەم پڕۆژەیە (دۆمەین، لایسەنس...). خەرجی VPSی هاوبەش لێرە دابەش ناکرێت، بۆیە ئەمە «قازانجی ڕاستەقینەی پڕۆژە» نییە.</p>
        </div>
    </div>

    @if($project->internal_notes)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 whitespace-pre-line"><strong>تێبینی ناوخۆیی:</strong> {{ $project->internal_notes }}</div>
    @endif

    <div class="bg-white rounded-2xl p-5 border border-slate-200 space-y-2">
        <h3 class="font-black text-slate-900">مێژوو</h3>
        @forelse($history as $h)
            <div class="text-xs text-slate-600"><span class="font-mono text-slate-400" dir="ltr">{{ $h->created_at->format('Y-m-d') }}</span> · {{ $h->message }}</div>
        @empty
            <p class="text-xs text-slate-400">هیچ تۆمارێک نییە.</p>
        @endforelse
    </div>
</div>
