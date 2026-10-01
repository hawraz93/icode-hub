<div class="space-y-6">
    
    <!-- Top Summary Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        
        <!-- Total USD Expenses -->
        <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">خەرجییەکان (دۆلار USD)</span>
                <div class="text-xl md:text-2xl font-black text-slate-900 font-mono" dir="ltr">
                    {{ \App\Support\Money::format((float) $totalUsd, 'USD') }}
                </div>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg md:text-xl font-bold">
                💵
            </div>
        </div>

        <!-- Total IQD Expenses -->
        <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">خەرجییەکان (دینار IQD)</span>
                <div class="text-xl md:text-2xl font-black text-slate-900 font-mono" dir="ltr">
                    {{ \App\Support\Money::format((float) $totalIqd, 'IQD') }}
                </div>
            </div>
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg md:text-xl font-bold">
                🇮🇶
            </div>
        </div>

        <!-- Add Expense CTA Button -->
        <div class="sm:col-span-2 md:col-span-1 bg-linear-to-br from-indigo-600 to-indigo-800 rounded-2xl p-4 md:p-5 text-white shadow-md shadow-indigo-200 flex items-center justify-between">
            <div class="space-y-0.5">
                <h3 class="font-black text-sm md:text-base">مەسرووفاتی نوێ</h3>
                <p class="text-xs text-indigo-100 font-medium">تۆمارکردنی خەرجی نوێ</p>
            </div>
            <button wire:click="openCreateModal" class="px-3.5 py-2 rounded-xl bg-white text-indigo-900 font-black text-xs hover:bg-indigo-50 transition shadow-xs flex items-center gap-1 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>+ زیادکردن</span>
            </button>
        </div>

    </div>

    <!-- Filters & Actions Header -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
            <div class="w-full">
                <x-input placeholder="گەڕان بەپێی وەسف یان تێبینی..." wire:model.live.debounce.300ms="search" />
            </div>

            <div class="w-full">
                <x-native-select wire:model.live="categoryFilter">
                    <option value="all">هەموو جۆرەکان</option>
                    <option value="software_ai">🤖 AI و نەرمەکاڵا</option>
                    <option value="infrastructure">🖥️ سێرڤەر و ژێرخان</option>
                    <option value="telecom">📱 ئینتەرنێت و مۆبایل</option>
                    <option value="transport">🚗 بەنزین و هاتووچۆ</option>
                    <option value="office">🏢 کەرەستە و شوێن</option>
                    <option value="marketing">📢 مارکێتینگ و ڕیکلام</option>
                    <option value="other">💸 خەرجی تر</option>
                </x-native-select>
            </div>

            <div class="w-full">
                <x-native-select wire:model.live="currencyFilter">
                    <option value="all">هەموو دراوەکان</option>
                    <option value="USD">دۆلار ($)</option>
                    <option value="IQD">دیناری عێراقی (د.ع)</option>
                </x-native-select>
            </div>
        </div>

        <button wire:click="openCreateModal" class="w-full md:w-auto px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center gap-1.5 shadow-xs shadow-indigo-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>تۆمارکردنی خەرجی</span>
        </button>
    </div>

    <!-- 1. Mobile Cards View (Responsive for Mobile Phones) -->
    <div class="block md:hidden space-y-3">
        @forelse($expenses as $exp)
            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-black border {{ $exp->category_color }}">
                                <span>{{ $exp->category_icon }}</span>
                                <span>{{ $exp->category_label }}</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">{{ $exp->expense_date->format('Y-m-d') }}</span>
                        </div>
                        <h4 class="font-extrabold text-slate-900 text-sm">{{ $exp->title }}</h4>
                        @if($exp->vendor)
                            <span class="text-xs text-slate-500 font-medium">کۆمپانیا: {{ $exp->vendor }}</span>
                        @endif
                    </div>

                    <div class="text-left font-mono font-black text-base text-slate-900" dir="ltr">
                        {{ \App\Support\Money::format((float) $exp->amount, $exp->currency) }}
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">{{ $exp->payment_method_label }}</span>
                    <div class="flex items-center gap-3">
                        <button wire:click="edit({{ $exp->id }})" class="font-bold text-indigo-600 hover:text-indigo-800">
                            دەستکاریکردن
                        </button>
                        <button wire:click="confirmDelete({{ $exp->id }})" class="font-bold text-rose-500 hover:text-rose-700">
                            سڕینەوە
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 text-xs border border-slate-200">
                هیچ تۆمارێکی خەرجی نەدۆزرایەوە.
            </div>
        @endforelse
    </div>

    <!-- 2. Desktop Table View -->
    <div class="hidden md:block bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/60 text-xs font-extrabold text-slate-500">
                        <th class="p-4">خەرجی / وەسف</th>
                        <th class="p-4">جۆر (Category)</th>
                        <th class="p-4">بڕی پارە</th>
                        <th class="p-4">خولی پارەدان</th>
                        <th class="p-4">شێوازی پارەدان</th>
                        <th class="p-4">بەروار</th>
                        <th class="p-4 text-center">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($expenses as $exp)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="p-4">
                                <div class="font-extrabold text-slate-900">{{ $exp->title }}</div>
                                @if($exp->vendor)
                                    <span class="text-xs text-slate-400 font-medium">کۆمپانیا: {{ $exp->vendor }}</span>
                                @endif
                                @if($exp->notes)
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ Str::limit($exp->notes, 40) }}</p>
                                @endif
                            </td>

                            <td class="p-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold border {{ $exp->category_color }}">
                                    <span>{{ $exp->category_icon }}</span>
                                    <span>{{ $exp->category_label }}</span>
                                </span>
                            </td>

                            <td class="p-4">
                                <span class="font-black text-base font-mono text-slate-900" dir="ltr">
                                    {{ \App\Support\Money::format((float) $exp->amount, $exp->currency) }}
                                </span>
                            </td>

                            <td class="p-4">
                                <span class="text-xs font-bold text-slate-600">
                                    {{ $exp->billing_cycle === 'monthly' ? 'مانگانە' : ($exp->billing_cycle === 'annual' ? 'ساڵانە' : 'یەکجارە') }}
                                </span>
                            </td>

                            <td class="p-4">
                                <span class="text-xs font-medium text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md">
                                    {{ $exp->payment_method_label }}
                                </span>
                            </td>

                            <td class="p-4 font-mono text-xs font-semibold text-slate-500" dir="ltr">
                                {{ $exp->expense_date->format('Y-m-d') }}
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $exp->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="دەستکاریکردن">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <button wire:click="confirmDelete({{ $exp->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">
                                هیچ تۆمارێکی خەرجی نەدۆزرایەوە. دەتوانیت لە ڕێگەی دوگمەی سەرەوە خەرجی نوێ زیاد بکەیت.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $expenses->links() }}
        </div>
    </div>

    <!-- WireUI Modal Card for Add/Edit Expense -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی خەرجی' : 'تۆمارکردنی خەرجی نوێ' }}" wire:model="showModal" max-width="lg">
        <div class="space-y-4">
            
            <div>
                <x-input label="ناوی خەرجی / وەسف *" placeholder="نموونە: بەشداریکردنی ساڵانەی ChatGPT Plus یان کارتی مۆبایل" wire:model="title" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-native-select
                        label="جۆری خەرجی (Category) *"
                        wire:model="category"
                        :options="[
                            ['name' => '🤖 AI و نەرمەکاڵا', 'id' => 'software_ai'],
                            ['name' => '🖥️ سێرڤەر و ژێرخان', 'id' => 'infrastructure'],
                            ['name' => '📱 ئینتەرنێت و مۆبایل', 'id' => 'telecom'],
                            ['name' => '🚗 بەنزین و هاتووچۆ', 'id' => 'transport'],
                            ['name' => '🏢 کەرەستە و شوێن', 'id' => 'office'],
                            ['name' => '📢 مارکێتینگ و ڕیکلام', 'id' => 'marketing'],
                            ['name' => '💸 خەرجی تر', 'id' => 'other'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                <div>
                    <x-input label="کۆمپانیای وەرگر (Vendor)" placeholder="OpenAI, AsiaCell, FastPay..." wire:model="vendor" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input type="number" step="any" min="0" inputmode="decimal"
                        label="بڕی پارە *"
                        placeholder="20"
                        wire:model="amount"
 />
                </div>

                <div>
                    <x-native-select
                        label="دراو *"
                        wire:model="currency"
                        :options="[
                            ['name' => 'دۆلاری ئەمریکی (USD $)', 'id' => 'USD'],
                            ['name' => 'دیناری عێراقی (IQD د.ع)', 'id' => 'IQD'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-native-select
                        label="خولی پارەدان *"
                        wire:model="billing_cycle"
                        :options="[
                            ['name' => 'مانگانە (Monthly)', 'id' => 'monthly'],
                            ['name' => 'ساڵانە (Annual)', 'id' => 'annual'],
                            ['name' => 'یەکجارەکی (One-Time)', 'id' => 'one_time'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                <div>
                    <x-native-select
                        label="شێوازی پارەدان *"
                        wire:model="payment_method"
                        :options="[
                            ['name' => 'ماستەرکارد / ڤیزا (Card)', 'id' => 'card'],
                            ['name' => 'فاستپەی (FastPay)', 'id' => 'fastpay'],
                            ['name' => 'بانکی FIB', 'id' => 'fib'],
                            ['name' => 'زەین کاش (ZainCash)', 'id' => 'zaincash'],
                            ['name' => 'کاش (نەقد)', 'id' => 'cash'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>
            </div>

            <div>
                <x-datetime-picker
                    label="بەرواری خەرجی *"
                    placeholder="YYYY-MM-DD"
                    without-time="true"
                    wire:model="expense_date"
                    display-format="YYYY-MM-DD"
                />
            </div>

            <div>
                <x-textarea label="تێبینییەکان" placeholder="وردەکاری یان تێبینی لەسەر ئەم خەرجییە..." wire:model="notes" />
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردنی خەرجی' }}" wire:click="save" spinner="save" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
