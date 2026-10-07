<div class="space-y-6">

    <!-- Tabs -->
    <div class="flex gap-2 bg-white p-1.5 rounded-2xl border border-slate-200/80 shadow-xs w-full sm:w-fit">
        <button wire:click="$set('tab', 'recorded')" class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-black transition {{ $tab === 'recorded' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            خەرجی تۆمارکراو
            @if($reviewCount)<span class="ms-1 px-1.5 rounded-full bg-amber-400 text-amber-950 text-[10px]">{{ $reviewCount }}</span>@endif
        </button>
        <button wire:click="$set('tab', 'recurring')" class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-black transition {{ $tab === 'recurring' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            خەرجی دووبارە و VPS
        </button>
    </div>

    @if($tab === 'recorded')
        <!-- ================= Ledger: money actually paid ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs space-y-1">
                <span class="text-xs font-bold text-slate-500">دراو لەم مانگەدا</span>
                <div class="text-xl font-black text-slate-900 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($paidThisMonth) }}</div>
            </div>
            <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs space-y-1">
                <span class="text-xs font-bold text-slate-500">دراو لەم ساڵەدا</span>
                <div class="text-xl font-black text-slate-900 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($paidThisYear) }}</div>
            </div>
            <div class="bg-linear-to-br from-indigo-600 to-indigo-800 rounded-2xl p-4 md:p-5 text-white shadow-md shadow-indigo-200 flex items-center justify-between">
                <div>
                    <h3 class="font-black text-sm">خەرجی یەکجار</h3>
                    <p class="text-xs text-indigo-100">پارەیەک کە ئێستا دراوە</p>
                </div>
                <button wire:click="openCreateModal" class="px-3.5 py-2 rounded-xl bg-white text-indigo-900 font-black text-xs hover:bg-indigo-50 transition">+ زیادکردن</button>
            </div>
        </div>

        <div class="bg-indigo-50 p-4 rounded-xl text-xs text-indigo-900 leading-relaxed">
            ئەم لیستە تەنها ئەو پارەیەیە کە بەڕاستی دراوە؛ هەر تۆمارێک یەکجار لە بەرواری خۆیدا هەژمار دەکرێت. پلانە دووبارەکان (VPS، AI، ئینتەرنێت) لە تابی «خەرجی دووبارە» دان و هەتا «پارەدرا» دانەگریت خەرجی دروست ناکەن.
        </div>

        @if($reviewCount)
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl text-xs text-amber-900">
                {{ $reviewCount }} خەرجی کۆن «مانگانە/ساڵانە» نووسراون و ڕوون نییە پارەدانی یەکجار بوون یان پلان. لە فلتەری «پێویستی بە پشکنین» یەکە یەکە دیاریان بکە.
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان..." icon="magnifying-glass" />
            <select wire:model.live="categoryFilter" class="rounded-xl border-slate-300 text-xs font-semibold">
                <option value="all">هەموو جۆرەکان</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="currencyFilter" class="rounded-xl border-slate-300 text-xs font-semibold">
                <option value="all">هەموو دراوەکان</option>
                <option value="USD">USD</option>
                <option value="IQD">IQD</option>
            </select>
            <select wire:model.live="statusFilter" class="rounded-xl border-slate-300 text-xs font-semibold">
                <option value="posted">تۆمارکراو</option>
                <option value="review">پێویستی بە پشکنین</option>
                <option value="void">هەڵوەشاوە</option>
                <option value="all">هەمووی</option>
            </select>
        </div>

        <!-- Ledger list (cards on mobile, rows on desktop) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs divide-y divide-slate-100">
            @forelse($expenses as $exp)
                <div class="p-4 flex flex-col md:flex-row md:items-center gap-3 {{ $exp->is_void ? 'opacity-60' : '' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-extrabold text-slate-900 {{ $exp->is_void ? 'line-through' : '' }}">{{ $exp->title }}</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold border {{ $exp->category_color }}">{{ $exp->category_icon }} {{ $exp->category_label }}</span>
                            @if($exp->expense_schedule_id)<span class="text-[11px] text-indigo-600 font-bold">↻ {{ $exp->schedule?->title }}</span>@endif
                            @if($exp->needs_review)<span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold">پێویستی بە پشکنین</span>@endif
                            @if($exp->is_void)<span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-bold">هەڵوەشاوە: {{ $exp->void_reason }}</span>@endif
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1 space-x-2 space-x-reverse">
                            <span class="font-mono" dir="ltr">{{ $exp->expense_date->format('Y-m-d') }}</span>
                            <span>· {{ $exp->payment_method_label }}</span>
                            @if($exp->vendor)<span>· {{ $exp->vendor }}</span>@endif
                            @if($exp->period_start && $exp->period_end)<span>· ماوە <span dir="ltr">{{ $exp->period_start->format('Y-m-d') }} → {{ $exp->period_end->format('Y-m-d') }}</span></span>@endif
                            @if($exp->project)<span>· پڕۆژە: {{ $exp->project->title }}</span>@endif
                        </div>
                        @if($exp->needs_review)
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="text-[11px] text-amber-800">نووسراوە «{{ ['monthly' => 'مانگانە', 'annual' => 'ساڵانە', 'biennial' => 'دوو ساڵە'][$exp->billing_cycle] ?? $exp->billing_cycle }}». ئەمە چی بوو؟</span>
                                <button wire:click="resolveLegacy({{ $exp->id }}, false)" class="text-[11px] px-2 py-1 rounded-lg bg-slate-100 font-bold">تەنها ئەم پارەدانە</button>
                                <button wire:click="resolveLegacy({{ $exp->id }}, true)" class="text-[11px] px-2 py-1 rounded-lg bg-indigo-100 text-indigo-800 font-bold">پارەدان + دروستکردنی پلانی دووبارە</button>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center justify-between md:justify-end gap-3">
                        <span class="font-black font-mono text-slate-900" dir="ltr">{{ \App\Support\Money::format((float) $exp->amount, $exp->currency) }}</span>
                        @unless($exp->is_void)
                            <button wire:click="edit({{ $exp->id }})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg text-xs font-bold">دەستکاری</button>
                            <button wire:click="askVoid({{ $exp->id }})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg text-xs font-bold">هەڵوەشاندنەوە</button>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-slate-400 text-sm">هیچ خەرجییەک نییە.</div>
            @endforelse
        </div>
        <div>{{ $expenses->links() }}</div>

        @if($voidingId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4">
                <div class="bg-white rounded-2xl p-6 max-w-md w-full space-y-4">
                    <h3 class="font-black">هەڵوەشاندنەوەی خەرجی</h3>
                    <p class="text-xs text-slate-600">تۆمارەکە ناسڕدرێتەوە؛ وەک هەڵوەشاوە دەمێنێتەوە و لە کۆکان دەرده‌چێت. ئەگەر بڕ یان بەروار هەڵە بوو، دواتر تۆمارێکی دروست زیاد بکە.</p>
                    <x-input label="هۆکار *" wire:model="voidReason" />
                    <div class="flex justify-between">
                        <button wire:click="confirmVoid" class="px-4 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold">هەڵوەشاندنەوە</button>
                        <button wire:click="$set('voidingId', null)" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold">پاشگەزبوونەوە</button>
                    </div>
                </div>
            </div>
        @endif
    @else
        <!-- ================= Recurring plans (forecast only) ================= -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs space-y-1">
                <span class="text-xs font-bold text-slate-500">پێشبینی ساڵانەی پلانە چالاکەکان</span>
                <div class="text-xl font-black text-slate-900 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($annualForecast) }}</div>
                <p class="text-[11px] text-slate-400">پێشبینییە، پارەی دراو نییە.</p>
            </div>
            <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs space-y-1">
                <span class="text-xs font-bold text-slate-500">دەبێت لە ٣٠ ڕۆژدا بدرێت</span>
                <div class="text-xl font-black text-amber-700 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($due30) }}</div>
            </div>
            <div class="bg-linear-to-br from-indigo-600 to-indigo-800 rounded-2xl p-4 md:p-5 text-white shadow-md shadow-indigo-200 flex items-center justify-between">
                <div>
                    <h3 class="font-black text-sm">پلانی نوێ</h3>
                    <p class="text-xs text-indigo-100">AI، ئینتەرنێت، بەرنامە...</p>
                </div>
                <button wire:click="openScheduleModal" class="px-3.5 py-2 rounded-xl bg-white text-indigo-900 font-black text-xs hover:bg-indigo-50 transition">+ پلان</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($schedules as $s)
                <div class="bg-white rounded-2xl p-4 border {{ $s->status === 'active' && $s->days_until_due < 0 ? 'border-rose-300' : 'border-slate-200' }} shadow-xs space-y-3 {{ $s->status !== 'active' ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-extrabold text-slate-900">{{ $s->server_id ? '🖥️' : '↻' }} {{ $s->title }}</div>
                            <div class="text-[11px] text-slate-500">{{ $categories[$s->category] ?? $s->category }}@if($s->vendor) · {{ $s->vendor }}@endif</div>
                        </div>
                        <div class="text-end">
                            <div class="font-black font-mono" dir="ltr">{{ \App\Support\Money::format((float) $s->amount_per_cycle, $s->currency) }}</div>
                            <div class="text-[11px] text-slate-500">{{ $s->cycle_label }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <span class="{{ $s->days_until_due < 0 ? 'text-rose-600 font-bold' : ($s->days_until_due <= 7 ? 'text-amber-700 font-bold' : 'text-slate-600') }}">
                            کاتی پارەدانی داهاتوو: <span class="font-mono" dir="ltr">{{ $s->next_due_on->format('Y-m-d') }}</span>
                            ({{ $s->days_until_due < 0 ? abs($s->days_until_due) . ' ڕۆژ دواکەوتووە' : $s->days_until_due . ' ڕۆژ ماوە' }})
                        </span>
                        <div class="flex gap-2">
                            @if($s->status !== 'ended')
                                <button wire:click="openPay({{ $s->id }})" class="px-3 py-1.5 rounded-xl bg-emerald-600 text-white font-bold">پارەدرا</button>
                            @endif
                            @unless($s->server_id)
                                <button wire:click="openScheduleModal({{ $s->id }})" class="px-3 py-1.5 rounded-xl bg-slate-100 font-bold">دەستکاری</button>
                            @endunless
                        </div>
                    </div>
                    @if($s->server)
                        <details class="text-xs text-slate-600">
                            <summary class="cursor-pointer font-bold">زانیاری VPS و {{ $s->server->subscriptions->count() }} خزمەتگوزاری لەسەری</summary>
                            <div class="mt-2 space-y-1">
                                @if($s->server->provider)<div>دابینکەر: {{ $s->server->provider }}</div>@endif
                                @if($s->server->specs)<div>تایبەتمەندی: {{ $s->server->specs }}</div>@endif
                                @if($s->server->ip_address)<div>IP: <span class="font-mono" dir="ltr">{{ $s->server->ip_address }}</span></div>@endif
                                @foreach($s->server->subscriptions as $hosted)
                                    <div>• {{ $hosted->domain_name ?: $hosted->name }} — {{ $hosted->client?->display_name }}</div>
                                @endforeach
                                <p class="text-slate-400">بەستنەوەی خزمەتگوزاری بە VPS تەنها شوێنی میوانداری نیشان دەدات؛ خەرجی نوێ دروست ناکات.</p>
                            </div>
                        </details>
                    @endif
                </div>
            @empty
                <div class="col-span-full p-10 text-center text-slate-400 text-sm bg-white rounded-2xl border border-slate-200">هیچ پلانێکی خەرجی نییە.</div>
            @endforelse
        </div>

        <details class="bg-white p-4 rounded-xl border border-slate-200">
            <summary class="font-bold cursor-pointer">زیادکردن و دەستکاریی VPS، سێرڤەر و خزمەتگوزارییەکانی خۆمان</summary>
            <div class="mt-4">
                <livewire:admin.servers-manager />
            </div>
        </details>
    @endif

    <!-- One-off expense modal -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریی خەرجی' : 'تۆمارکردنی خەرجیی یەکجار' }}" wire:model="showModal" max-width="2xl">
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="ناونیشانی خەرجی *" placeholder="بەنزین، کڕینی دۆمەین..." wire:model="title" />
                <x-native-select label="جۆر *" wire:model="category">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input type="number" step="any" min="0" inputmode="decimal" label="بڕ *" wire:model="amount" :disabled="(bool) $editingId" />
                <x-native-select label="دراو *" wire:model="currency" :disabled="(bool) $editingId">
                    <option value="USD">USD</option>
                    <option value="IQD">IQD</option>
                </x-native-select>
                <x-input type="date" label="بەرواری پارەدان *" wire:model="expense_date" :disabled="(bool) $editingId" />
            </div>
            @if($editingId)
                <p class="text-[11px] text-slate-500">بڕ، دراو و بەرواری خەرجییەکی تۆمارکراو ناگۆڕدرێن؛ ئەگەر هەڵە بوو، هەڵیبوەشێنەوە و تۆمارێکی نوێ زیاد بکە.</p>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-native-select label="شێوازی پارەدان *" wire:model="payment_method">
                    @foreach($methods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
                <x-input label="کۆمپانیای وەرگر" placeholder="OpenAI, AsiaCell..." wire:model="vendor" />
                <x-input label="ژمارەی وەسڵ / سەرچاوە" wire:model="reference" dir="ltr" />
            </div>
            <x-native-select label="پڕۆژە (ئەگەر خەرجیی ڕاستەوخۆی پڕۆژەیەکە)" wire:model="project_id">
                <option value="">— خەرجی گشتی کۆمپانیا —</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->title }}</option>
                @endforeach
            </x-native-select>
            <x-textarea label="تێبینی" wire:model="notes" />
        </div>
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردن' }}" wire:click="save" spinner="save" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

    <!-- Recurring plan modal -->
    <x-modal-card title="{{ $scheduleId ? 'دەستکاریی پلان' : 'پلانی خەرجیی دووبارە' }}" wire:model="showScheduleModal" max-width="2xl">
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-input label="ناونیشان *" placeholder="ChatGPT Plus، ئینتەرنێتی ئۆفیس..." wire:model="s_title" />
                <x-native-select label="جۆر *" wire:model="s_category">
                    @foreach($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <x-input type="number" step="any" min="0" inputmode="decimal" label="نرخی هەر ماوەیەک *" wire:model="s_amount" />
                <x-native-select label="دراو *" wire:model="s_currency">
                    <option value="USD">USD</option>
                    <option value="IQD">IQD</option>
                </x-native-select>
                <x-input type="number" min="1" step="1" label="هەر چەند *" wire:model="s_cycle_count" />
                <x-native-select label="یەکە *" wire:model="s_cycle_unit">
                    @foreach($cycleUnits as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-input type="date" label="کاتی پارەدانی داهاتوو *" wire:model="s_next_due_on" />
                <x-native-select label="شێوازی پارەدان" wire:model="s_payment_method">
                    @foreach($methods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
                <x-native-select label="دۆخ" wire:model="s_status">
                    <option value="active">چالاک</option>
                    <option value="paused">ڕاگیراو</option>
                    <option value="ended">کۆتایی هات</option>
                </x-native-select>
            </div>
            <x-input label="کۆمپانیا" wire:model="s_vendor" />
            <x-toggle label="خۆکار نوێ دەبێتەوە (لای دابینکەر)" wire:model="s_auto_renew" />
            <x-textarea label="تێبینی" wire:model="s_notes" />
            <p class="text-[11px] text-slate-500">پلان پارەدان نییە. کاتێک پارەکەت دا، «پارەدرا» دابگرە تا خەرجییەکی تۆمارکراو دروست ببێت.</p>
        </div>
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="پاشەکەوتکردن" wire:click="saveSchedule" spinner="saveSchedule" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

    <!-- Pay a plan -->
    <x-modal-card title="پارەدانی پلان" wire:model="showPayModal" max-width="lg">
        @if($payingSchedule)
            <div class="space-y-4">
                <div class="text-xs bg-slate-50 rounded-xl p-3 space-y-1">
                    <div class="font-bold">{{ $payingSchedule->title }} · {{ $payingSchedule->amount_label }}</div>
                    <div>ئەم پارەیە بۆ ماوەی: <span class="font-mono font-bold" dir="ltr">{{ $pay_period_start }} → {{ $payPeriodEnd ?? '—' }}</span></div>
                    @if($payingSchedule->days_until_due < 0)
                        <div class="text-rose-700">کاتی پارەدان تێپەڕیوە. تەنها یەک ماوە تۆمار دەکرێت؛ ئەگەر چەند ماوەت داوە، هەر یەکەیان جیا تۆمار بکە.</div>
                    @endif
                </div>
                <x-input type="date" label="دەستپێکی ئەو ماوەیەی پارەی بۆ دراوە *" wire:model.live="pay_period_start" />
                <x-input type="number" step="any" min="0" inputmode="decimal" label="بڕ *" prefix="{{ $payingSchedule->currency }}" wire:model="pay_amount" />
                <x-input type="date" label="بەرواری پارەدان *" wire:model="pay_date" />
                <x-native-select label="شێواز *" wire:model="pay_method">
                    @foreach($methods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
                <x-input label="ژمارەی وەسڵ / سەرچاوە" wire:model="pay_reference" dir="ltr" />
                <x-textarea label="تێبینی" wire:model="pay_notes" />
            </div>
        @endif
        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button positive label="تۆمارکردنی پارەدان" wire:click="confirmPay" spinner="confirmPay" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
