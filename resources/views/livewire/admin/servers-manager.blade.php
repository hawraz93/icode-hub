<div class="space-y-6">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="font-extrabold text-slate-900 text-base sm:text-lg">خزمەتگوزارییەکانی خۆم</h2>
            <p class="text-xs text-slate-500 mt-0.5">سێرڤەر، دۆمەین، ئیمەیڵ و هەر شتێک بۆ خۆت دەیکڕیت. ئەمانە پلانی خەرجین؛ تەنها کاتێک «پارەدرا» دادەگریت خەرجی تۆمار دەکرێت.</p>
            <div class="flex flex-wrap gap-x-6 gap-y-1 mt-2 text-xs">
                <span class="text-slate-500">پێشبینی مانگانە: <strong class="text-slate-900 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($monthlyTotals) }}</strong></span>
                <span class="text-slate-500">پێشبینی ساڵانە: <strong class="text-slate-900 font-mono" dir="ltr">{{ \App\Support\Money::formatTotals($annualTotals) }}</strong></span>
            </div>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>زیادکردن</span>
        </button>
    </div>

    <!-- Servers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($servers as $server)
            <div class="bg-white rounded-2xl border {{ $server->days_until_renewal <= 7 ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200' }} shadow-sm p-6 flex flex-col justify-between">
                
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                            {{ $server->kind_label }}@if($server->provider) · {{ $server->provider }}@endif
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold {{ $server->days_until_renewal < 0 ? 'bg-red-100 text-red-800' : ($server->days_until_renewal <= 7 ? 'bg-rose-100 text-rose-800 animate-pulse' : 'bg-emerald-100 text-emerald-800') }}">
                            {{ $server->renewal_status_text }}
                        </span>
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $server->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        <span>{{ $server->name }}</span>
                    </h3>

                    @if($server->specs && $server->kind === 'server')
                        <p class="text-xs text-slate-500 font-mono mt-1" dir="ltr">{{ $server->specs }}</p>
                    @endif

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                        @if($server->kind === 'server')
                        <div class="flex items-center justify-between text-slate-600">
                            <span>IP Address:</span>
                            <span class="font-mono font-bold text-slate-800" dir="ltr">{{ $server->ip_address ?? 'N/A' }}</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>شوێن (Location):</span>
                            <span class="text-slate-700">{{ $server->location ?: '—' }}</span>
                        </div>
                        @endif

                        <div class="flex items-center justify-between text-slate-600">
                            <span>خەرجی نوێکردنەوە:</span>
                            <span class="font-mono font-extrabold text-rose-600 text-sm" dir="ltr">
                                {{ $server->cost_label }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>بەرواری دانی پارە:</span>
                            <span class="font-mono font-bold text-slate-900" dir="ltr">{{ $server->renewal_date ? $server->renewal_date->format('Y-m-d') : 'N/A' }}</span>
                        </div>

                        @if($server->kind === 'server')
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-slate-500">پڕۆژە میوانداریکراوەکان:</span>
                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-xs">{{ $server->subscriptions->count() }} پڕۆژە</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button wire:click="edit({{ $server->id }})" class="text-xs font-bold text-indigo-600 hover:text-indigo-700">
                            دەستکاریکردن
                        </button>
                        <span class="text-slate-300">|</span>
                        <button wire:click="confirmDelete({{ $server->id }})" class="text-xs font-bold text-rose-500 hover:text-rose-700">
                            سڕینەوە
                        </button>
                    </div>

                    <button wire:click="confirmRenewServer({{ $server->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white text-xs font-extrabold transition shadow-sm" title="پارەی ئەم ماوەیە درا">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>پارەدرا</span>
                    </button>
                </div>

            </div>
        @endforeach
    </div>

                    <!-- WireUI Modal Card for Server Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاری' : 'زیادکردنی خزمەتگوزاری خۆم' }}" wire:model="showModal" max-width="2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-native-select
                    label="جۆر *"
                    wire:model.live="kind"
                    :options="collect(\App\Models\Server::KINDS)->map(fn ($label, $id) => ['id' => $id, 'name' => $label])->values()->all()"
                    option-label="name"
                    option-value="id"
                />
                <div>
                    <span class="block text-sm font-medium text-slate-700 mb-1">دراو</span>
                    <div class="inline-flex bg-slate-100 p-1 rounded-xl" role="radiogroup" aria-label="دراو">
                        <button type="button" wire:click="$set('currency', 'USD')" role="radio" aria-checked="{{ $currency === 'USD' ? 'true' : 'false' }}" class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $currency === 'USD' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500' }}">$ دۆلار</button>
                        <button type="button" wire:click="$set('currency', 'IQD')" role="radio" aria-checked="{{ $currency === 'IQD' ? 'true' : 'false' }}" class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $currency === 'IQD' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500' }}">د.ع دینار</button>
                    </div>
                </div>
            </div>
            <div class="md:col-span-2">
                <x-input label="ناو *" placeholder="Contabo VPS / icodegroup.net / Google Workspace" wire:model="name" />
            </div>

            <div>
                <x-input label="دابینکەر" placeholder="Contabo, Namecheap, GoDaddy, Google" wire:model="provider" />
            </div>

            @if($kind === 'server')
            <div>
                <x-input label="IP Address" placeholder="65.108.72.19" wire:model="ip_address" dir="ltr" />
            </div>
            @endif

            @if($kind === 'server')
            <div>
                <x-input label="شوێنی سێرڤەر (Location)" placeholder="Germany, Frankfurt, USA" wire:model="location" />
            </div>
            @endif

            @if($kind === 'server')
            <div>
                <x-input label="تایبەتمەندییەکان (Specs)" placeholder="4 vCPU, 8 GB RAM, 160 GB NVMe" wire:model="specs" dir="ltr" />
            </div>
            @endif

            <!-- WireUI Currency Component -->
            <div>
                <x-input type="number" step="any" min="0" inputmode="decimal"
                    label="تێچوو *"
                    placeholder="0"
                    prefix="{{ $currency === 'IQD' ? 'د.ع' : '$' }}"
                    wire:model="cost"
 />
            </div>

            <div>
                <x-native-select
                    label="شێوازی پارەدان *"
                    wire:model="billing_cycle"
                    :options="[
                        ['name' => 'مانگانە', 'id' => 'monthly'],
                        ['name' => 'سێ مانگ جارێک', 'id' => 'quarterly'],
                        ['name' => 'شەش مانگ جارێک', 'id' => 'semi_annual'],
                        ['name' => 'ساڵانە', 'id' => 'annual'],
                        ['name' => 'دوو ساڵ جارێک', 'id' => 'biennial'],
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>

            <!-- WireUI DateTime Pickers -->
            <div>
                <x-datetime-picker
                    label="بەرواری کڕین"
                    placeholder="YYYY-MM-DD"
                    without-time="true"
                    wire:model="purchase_date"
                    display-format="YYYY-MM-DD"
                />
            </div>

            <div>
                <x-datetime-picker
                    label="بەرواری نوێکردنەوە (Renewal Date) *"
                    placeholder="YYYY-MM-DD"
                    without-time="true"
                    wire:model="renewal_date"
                    display-format="YYYY-MM-DD"
                />
            </div>

            <div class="md:col-span-2">
                <x-textarea label="تێبینییەکان" placeholder="تێبینی لەسەر SSH، لۆگین یان کۆمپانیای دابینکەر..." wire:model="notes" />
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردن' }}" wire:click="save" spinner="save" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
