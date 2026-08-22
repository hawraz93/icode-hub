<div class="space-y-6">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="font-extrabold text-slate-900 text-base sm:text-lg">سێرڤەر و VPSە تایبەتەکان</h2>
            <p class="text-xs text-slate-500 mt-0.5">کۆی خەرجی مانگانەی سێرڤەرەکان: <strong class="text-slate-900 font-mono" dir="ltr">${{ number_format($totalMonthlyCost, 2) }}</strong></p>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>زیادکردنی سێرڤەری نوێ (VPS)</span>
        </button>
    </div>

    <!-- Servers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($servers as $server)
            <div class="bg-white rounded-2xl border {{ $server->days_until_renewal <= 7 ? 'border-rose-300 ring-2 ring-rose-100' : 'border-slate-200' }} shadow-sm p-6 flex flex-col justify-between">
                
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">
                            {{ $server->provider }}
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold {{ $server->days_until_renewal < 0 ? 'bg-red-100 text-red-800' : ($server->days_until_renewal <= 7 ? 'bg-rose-100 text-rose-800 animate-pulse' : 'bg-emerald-100 text-emerald-800') }}">
                            {{ $server->renewal_status_text }}
                        </span>
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $server->status === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        <span>{{ $server->name }}</span>
                    </h3>

                    @if($server->specs)
                        <p class="text-xs text-slate-500 font-mono mt-1" dir="ltr">{{ $server->specs }}</p>
                    @endif

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>IP Address:</span>
                            <span class="font-mono font-bold text-slate-800" dir="ltr">{{ $server->ip_address ?? 'N/A' }}</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>شوێن (Location):</span>
                            <span class="text-slate-700">{{ $server->location ?? 'Global' }}</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>خەرجی نوێکردنەوە:</span>
                            <span class="font-mono font-extrabold text-rose-600 text-sm" dir="ltr">
                                ${{ number_format($server->cost, 2) }} / {{ $server->billing_cycle === 'annual' ? 'ساڵ' : 'مانگ' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>بەرواری دانی پارە:</span>
                            <span class="font-mono font-bold text-slate-900" dir="ltr">{{ $server->renewal_date ? $server->renewal_date->format('Y-m-d') : 'N/A' }}</span>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-slate-500">پڕۆژە میوانداریکراوەکان:</span>
                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-xs">{{ $server->subscriptions->count() }} پڕۆژە</span>
                        </div>
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

                    <button wire:click="confirmRenewServer({{ $server->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white text-xs font-extrabold transition shadow-sm" title="نوێکردنەوەی بەروار">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>نوێکردنەوە</span>
                    </button>
                </div>

            </div>
        @endforeach
    </div>

                    <!-- WireUI Modal Card for Server Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی سێرڤەر' : 'تۆمارکردنی سێرڤەری نوێ (VPS)' }}" wire:model="showModal" max-width="2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="md:col-span-2">
                <x-input label="ناوی سێرڤەر *" placeholder="Hetzner CPX31 / Core Node" wire:model="name" />
            </div>

            <div>
                <x-input label="کۆمپانیای دابینکەر *" placeholder="Hetzner, Contabo, DigitalOcean, AWS" wire:model="provider" />
            </div>

            <div>
                <x-input label="IP Address" placeholder="65.108.72.19" wire:model="ip_address" dir="ltr" />
            </div>

            <div>
                <x-input label="شوێنی سێرڤەر (Location)" placeholder="Germany, Frankfurt, USA" wire:model="location" />
            </div>

            <div>
                <x-input label="تایبەتمەندییەکان (Specs)" placeholder="4 vCPU, 8 GB RAM, 160 GB NVMe" wire:model="specs" dir="ltr" />
            </div>

            <!-- WireUI Currency Component -->
            <div>
                <x-currency
                    label="تێچووی سێرڤەر *"
                    placeholder="0.00"
                    prefix="$"
                    wire:model="cost"
                    thousands=","
                    decimal="."
                    precision="2"
                />
            </div>

            <div>
                <x-native-select
                    label="شێوازی پارەدان *"
                    wire:model="billing_cycle"
                    :options="[
                        ['name' => 'مانگانە (Monthly)', 'id' => 'monthly'],
                        ['name' => 'ساڵانە (Annual)', 'id' => 'annual'],
                        ['name' => 'سێ مانگە (Quarterly)', 'id' => 'quarterly'],
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
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردن' }}" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
