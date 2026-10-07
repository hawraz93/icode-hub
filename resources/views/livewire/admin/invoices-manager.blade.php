<div class="space-y-6">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto flex-1">
            <div class="w-full sm:max-w-xs">
                <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان بەپێی وەسڵ یان کڕیار..." icon="magnifying-glass" />
            </div>
            
            <select wire:model.live="statusFilter" class="w-full sm:w-auto rounded-xl border-slate-300 text-xs font-semibold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">هەموو دۆخەکان</option>
                <option value="paid">دراوە (Paid)</option>
                <option value="sent">نێردراوە (Sent)</option>
                <option value="partial">بەشێکی دراوە (Partial)</option>
                <option value="overdue">دواکەوتووە (Overdue)</option>
                <option value="draft">ڕەشنووس (Draft)</option>
                <option value="cancelled">هەڵوەشاوە (Cancelled)</option>
            </select>
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>دروستکردنی وەسڵی نوێ</span>
        </button>
    </div>

    <!-- Mobile Invoices Cards (Visible on Mobile Only) -->
    <div class="md:hidden space-y-3">
        @forelse($invoices as $invoice)
            <div class="bg-white rounded-2xl border {{ $invoice->is_overdue ? 'border-rose-300 ring-1 ring-rose-100' : 'border-slate-200' }} p-4 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-mono font-black text-indigo-600 text-sm" dir="ltr">{{ $invoice->invoice_number }}</div>
                        <h4 class="font-bold text-slate-900 text-sm mt-0.5">{{ $invoice->client->business_name ?? $invoice->client->name }}</h4>
                    </div>
                    <div>
                        @include('livewire.admin.partials.invoice-status', ['invoice' => $invoice])
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                    <div>
                        <span class="text-slate-400 text-[11px] block">بەرواری دەرچوون:</span>
                        <span class="font-mono text-slate-700" dir="ltr">{{ $invoice->issue_date->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">بەرواری کۆتایی:</span>
                        <span class="font-mono font-bold {{ $invoice->is_overdue ? 'text-rose-600' : 'text-slate-700' }}" dir="ltr">{{ $invoice->due_date->format('Y-m-d') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">کۆی گشتی:</span>
                        <span class="font-mono font-black text-slate-900 text-sm" dir="ltr">{{ \App\Support\Money::format((float) $invoice->total, $invoice->currency) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">بڕی دراو:</span>
                        <span class="font-mono font-bold text-emerald-600 text-sm" dir="ltr">{{ \App\Support\Money::format((float) $invoice->paid_amount, $invoice->currency) }}</span>
                    </div>
                </div>

                <!-- 1-Tap Quick Action Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <!-- View / Print -->
                        <button wire:click="viewInvoice({{ $invoice->id }})" class="px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>بینین</span>
                        </button>

                        <!-- WhatsApp -->
                        <a href="https://wa.me/{{ $invoice->client->whatsapp_number }}?text={{ urlencode('سڵاو ڕێز بەڕێز ' . $invoice->client->name . '، وەسڵی فەرمی ژمارە (' . $invoice->invoice_number . ') بە کۆی گشتی ' . \App\Support\Money::format((float) $invoice->total, $invoice->currency) . ' ئامادەیە. سوپاس بۆ مامەڵەکردنتان لەگەڵ iCode Group.') }}" 
                           target="_blank"
                           class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>واتسئاپ</span>
                        </a>

                        @if(in_array($invoice->status, ['sent', 'partial', 'overdue'], true) && $invoice->remaining_balance > 0)
                            <button wire:click="openPayment({{ $invoice->id }})" class="px-2.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 text-xs font-bold transition">+ پارەدان</button>
                            <button wire:click="markAsPaid({{ $invoice->id }})" wire:confirm="هەموو قەرزی ماوە وەک وەرگیراو تۆمار بکرێت؟" class="p-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition" title="بە تەواوی درا">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-1">
                        <button wire:click="edit({{ $invoice->id }})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button wire:click="confirmDelete({{ $invoice->id }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                هیچ وەسڵێک نەدۆزرایەوە.
            </div>
        @endforelse
    </div>

    <!-- Desktop Invoices Table (Visible on Desktop / Tablet Only) -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
                <thead class="bg-slate-50/75 text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="p-4 text-start font-bold">ژمارەی وەسڵ</th>
                        <th class="p-4 text-start font-bold">کڕیار / کۆمپانیا</th>
                        <th class="p-4 text-start font-bold">بەرواری دەرچوون</th>
                        <th class="p-4 text-start font-bold">بەرواری کۆتایی</th>
                        <th class="p-4 text-start font-bold">کۆی گشتی</th>
                        <th class="p-4 text-start font-bold">دراوە</th>
                        <th class="p-4 text-start font-bold">دۆخ</th>
                        <th class="p-4 text-center font-bold">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-4 font-mono font-bold text-indigo-600 text-sm" dir="ltr">
                                {{ $invoice->invoice_number }}
                            </td>

                            <td class="p-4">
                                <div class="font-bold text-slate-900">{{ $invoice->client->business_name ?? $invoice->client->name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono" dir="ltr">{{ $invoice->client->phone }}</div>
                            </td>

                            <td class="p-4 font-mono text-slate-600" dir="ltr">
                                {{ $invoice->issue_date->format('Y-m-d') }}
                            </td>

                            <td class="p-4 font-mono {{ $invoice->is_overdue ? 'text-rose-600 font-bold' : 'text-slate-600' }}" dir="ltr">
                                {{ $invoice->due_date->format('Y-m-d') }}
                            </td>

                            <td class="p-4 font-mono font-black text-slate-900 text-sm" dir="ltr">
                                {{ \App\Support\Money::format((float) $invoice->total, $invoice->currency) }}
                            </td>

                            <td class="p-4 font-mono font-bold text-emerald-600" dir="ltr">
                                {{ \App\Support\Money::format((float) $invoice->paid_amount, $invoice->currency) }}
                            </td>

                            <td class="p-4">
                        @include('livewire.admin.partials.invoice-status', ['invoice' => $invoice])
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- View/Print Button -->
                                    <button wire:click="viewInvoice({{ $invoice->id }})" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="بینین و چاپ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <!-- Quick Mark Paid -->
                                    @if(in_array($invoice->status, ['sent', 'partial', 'overdue'], true) && $invoice->remaining_balance > 0)
                                        <button wire:click="openPayment({{ $invoice->id }})" class="px-2 py-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition" title="تۆمارکردنی پارەدان">+ پارەدان</button>
                                        <button wire:click="markAsPaid({{ $invoice->id }})" wire:confirm="هەموو قەرزی ماوە وەک وەرگیراو تۆمار بکرێت؟" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="بە تەواوی درا">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    @endif

                                    <!-- WhatsApp Send -->
                                    <a href="https://wa.me/{{ $invoice->client->whatsapp_number }}?text={{ urlencode('سڵاو ڕێز بەڕێز ' . $invoice->client->name . '، وەسڵی فەرمی ژمارە (' . $invoice->invoice_number . ') بە کۆی گشتی ' . \App\Support\Money::format((float) $invoice->total, $invoice->currency) . ' ئامادەیە. سوپاس بۆ مامەڵەکردنتان لەگەڵ iCode Group.') }}" 
                                       target="_blank"
                                       class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" 
                                       title="ناردن بە واتسئاپ">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    </a>

                                    <button wire:click="edit({{ $invoice->id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="دەستکاری">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <button wire:click="confirmDelete({{ $invoice->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-12 text-center text-slate-400">هیچ وەسڵێک نەدۆزرایەوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $invoices->links() }}
        </div>
    </div>

    <!-- Mobile Pagination -->
    <div class="md:hidden">
        {{ $invoices->links() }}
    </div>

    <!-- WireUI Modal Card for Invoice Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی وەسڵ' : 'دروستکردنی وەسڵی نوێ' }}" wire:model="showModal" max-width="4xl">
        <div class="space-y-6">
            
            <!-- Client & Dates Header Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-select
                        label="کڕیار *"
                        placeholder="کڕیار هەڵبژێرە"
                        wire:model.live="client_id"
                        :options="$clients"
                        option-label="displayName"
                        option-value="id"
                    />
                </div>

                <div>
                    <x-input label="ژمارەی وەسڵ *" wire:model="invoice_number" dir="ltr" />
                </div>

                <div>
                    <x-native-select
                        label="دۆخی وەسڵ *"
                        wire:model="status"
                        hint="«دراوە» و «بەشێکی دراوە» خۆکار لە پارەدانەکانەوە دێن."
                        :options="[
                            ['name' => 'ڕەشنووس (Draft)', 'id' => 'draft'],
                            ['name' => 'دەرچووە / نێردراوە (Issued)', 'id' => 'sent'],
                            ['name' => 'هەڵوەشاوە (Cancelled)', 'id' => 'cancelled'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                <div>
                    <x-datetime-picker
                        label="بەرواری دەرچوون *"
                        placeholder="YYYY-MM-DD"
                        without-time="true"
                        wire:model="issue_date"
                        display-format="YYYY-MM-DD"
                    />
                </div>

                <div>
                    <x-datetime-picker
                        label="بەرواری کۆتایی پارەدان *"
                        placeholder="YYYY-MM-DD"
                        without-time="true"
                        wire:model="due_date"
                        display-format="YYYY-MM-DD"
                    />
                </div>

                <div>
                    <x-input label="شێوازی پارەدان" placeholder="FIB, FastPay, کاش" wire:model="payment_method" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-native-select label="دراوی وەسڵ" wire:model.live="currency">
                    <option value="USD">USD — دۆلار</option><option value="IQD">IQD — دینار</option>
                </x-native-select>
                <x-native-select label="پڕۆژە" wire:model="project_id" :disabled="! $client_id">
                    <option value="">— بێ پڕۆژە —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </x-native-select>
                <x-native-select label="گرێبەست" wire:model="contract_id" :disabled="! $client_id">
                    <option value="">— بێ گرێبەست —</option>
                    @foreach($contracts as $contract)
                        <option value="{{ $contract->id }}">{{ $contract->contract_number }} · {{ $contract->title }}</option>
                    @endforeach
                </x-native-select>
            </div>
            <p class="text-xs text-slate-600 bg-slate-50 rounded-xl p-3">
                دروستکردن بڕگەیەکی یەکجارەیە. بۆ هۆستی دوو ساڵ: «نرخ بۆ ساڵێک»، ژمارەی ساڵ ٢، و نرخی ساڵێک بنووسە؛ کۆ = نرخ × ٢ و بەسەرچوون خۆکار حساب دەکرێت.
                بەرواری پارەدانی وەسڵ جیاوازە لە بەسەرچوونی هۆست. تەنها پڕۆژە، گرێبەست و خزمەتگوزارییەکانی ئەم کڕیارە دەردەکەون.
            </p>
            <!-- Dynamic Items Table -->
            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-slate-900 text-sm">بڕگەکانی ناو وەسڵ (Line Items)</h4>
                    <button type="button" wire:click="addItem" class="px-3 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg transition">
                        + زیادکردنی بڕگەی نوێ
                    </button>
                </div>

                <div class="space-y-2">
                    @foreach($items as $index => $item)
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center bg-white p-3 rounded-xl border border-slate-200">
                            <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-4 gap-3">
                                @php $recurring = ($item['billing_cycle'] ?? 'one_time') !== 'one_time'; @endphp
                                <x-native-select label="جۆری خزمەتگوزاری" wire:model.live="items.{{ $index }}.service_type">
                                    @foreach(\App\Models\InvoiceItem::SERVICE_TYPES as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </x-native-select>
                                <x-native-select label="نرخ بۆ" wire:model.live="items.{{ $index }}.billing_cycle">
                                    <option value="one_time">یەکجار</option><option value="monthly">مانگێک</option><option value="annual">ساڵێک</option>
                                </x-native-select>
                                @if($recurring)
                                    <x-input type="date" label="ڕۆژی دەستپێک *" wire:model.live="items.{{ $index }}.start_date" />
                                    <x-input type="date" label="ڕۆژی بەسەرچوون" wire:model="items.{{ $index }}.expiry_date" :readonly="empty($item['custom_period'])"
                                        hint="{{ empty($item['custom_period']) ? 'خۆکار: دەستپێک + ماوە' : 'ماوەی دەستی' }}" />
                                @endif
                            </div>
                            @if($recurring)
                                <div class="md:col-span-12 grid grid-cols-1 md:grid-cols-3 gap-3 bg-slate-50 rounded-lg p-2">
                                    <x-native-select label="خزمەتگوزاریی کڕیار" wire:model.live="items.{{ $index }}.subscription_id" :disabled="! $client_id">
                                        <option value="">— نوێ / بەبێ بەستنەوە —</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}">{{ $service->domain_name ?: $service->name }} ({{ $service->expiry_date?->format('Y-m-d') }})</option>
                                        @endforeach
                                    </x-native-select>
                                    @if(empty($item['subscription_id']) && in_array($item['service_type'] ?? '', \App\Services\InvoiceService::SERVICE_TYPES, true))
                                        <x-checkbox label="لەم بڕگەیەوە خزمەتگوزاری دروست بکە (بۆ نوێکردنەوە و ئاگادارکردنەوە)" wire:model="items.{{ $index }}.create_service" />
                                    @else
                                        <div></div>
                                    @endif
                                    <div class="space-y-2">
                                        <x-checkbox label="ماوەی دەستی (جیاواز لە دەستپێک + ماوە)" wire:model.live="items.{{ $index }}.custom_period" />
                                        @if(! empty($item['custom_period']))
                                            <x-input placeholder="هۆکاری ماوەی دەستی *" wire:model="items.{{ $index }}.period_note" />
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="md:col-span-6">
                                <x-input label="وەسف / خزمەتگوزاری" placeholder="پەرەپێدان، نوێکردنەوەی دۆمەین، هۆستینگ..." wire:model="items.{{ $index }}.description" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input label="{{ match($item['billing_cycle'] ?? 'one_time') { 'annual' => 'ژمارەی ساڵ', 'monthly' => 'ژمارەی مانگ', default => 'ژمارە' } }}"
                                    type="number" step="{{ $recurring ? 1 : 'any' }}" min="1" wire:model.live="items.{{ $index }}.quantity" />
                            </div>

                            <div class="md:col-span-3">
                                <x-input type="number" step="any" min="0" inputmode="decimal"
                                    label="{{ match($item['billing_cycle'] ?? 'one_time') { 'annual' => 'نرخی ساڵێک', 'monthly' => 'نرخی مانگێک', default => 'نرخی تاک' } }} ({{ $currency }})"
                                    placeholder="0"
                                    prefix="{{ $currency }}"
                                    wire:model="items.{{ $index }}.unit_price"
 />
                            </div>

                            <div class="md:col-span-1 flex justify-center pt-6">
                                @if(count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Financial Totals & Discounts -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <x-input type="number" step="any" min="0" inputmode="decimal"
                        label="داشکاندن (Discount)"
                        placeholder="0"
                        prefix="{{ $currency }}"
                        wire:model="discount"
 />
                </div>

                <div class="text-xs text-slate-600 bg-slate-50 rounded-xl p-3 self-start">
                    <span class="font-bold block mb-1">بڕی دراو</span>
                    لێرە دەستکاری ناکرێت. پارەدان لە لیستی وەسڵەکان بە دوگمەی «+ پارەدان» تۆمار بکە؛ مێژووی هەموو پارەدانێک دەپارێزرێت.
                </div>

                <div>
                    <x-textarea label="مەرج و تێبینی وەسڵ" placeholder="مەرجەکانی پارەدان..." wire:model="terms" />
                </div>
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'دروستکردنی وەسڵ' }}" wire:click="save" spinner="save" />
            </div>
        </x-slot>
    </x-modal-card>

    <!-- Official Printable Invoice Modal -->
    @if($viewingInvoice)
        <x-modal-card title="وەسڵی فەرمی (چاپکردن)" wire:model="showViewModal" max-width="4xl">
            <!-- Printable Invoice Box -->
            <div id="invoice-print-area">
                @include('partials.invoice-document', ['invoice' => $viewingInvoice])
            </div>

                <!-- Payment history (admin only, hidden when printing) -->
                @if($viewingInvoice->payments->isNotEmpty())
                    <div class="print:hidden border border-slate-200 rounded-xl p-4 space-y-2 text-xs">
                        <div class="font-bold text-slate-800">مێژووی پارەدان</div>
                        @foreach($viewingInvoice->payments as $payment)
                            <div class="flex flex-wrap items-center justify-between gap-2 py-1.5 border-b border-slate-100 last:border-0">
                                <div>
                                    <span class="font-mono" dir="ltr">{{ $payment->paid_on?->format('Y-m-d') ?? 'بەروار نەزانراوە' }}</span>
                                    <span class="text-slate-500">· {{ $payment->method_label }}</span>
                                    @if($payment->type === 'reversal')<span class="text-rose-600 font-bold">· گەڕاندنەوە</span>@endif
                                    @if($payment->source === 'legacy_import')<span class="text-amber-600">· هاوردەکراو</span>@endif
                                    @if($payment->needs_review)<span class="text-amber-700 font-bold">· پێویستی بە پشکنینە</span>@endif
                                    @if($payment->reference)<span class="text-slate-400 font-mono" dir="ltr">· {{ $payment->reference }}</span>@endif
                                    @if($payment->notes)<div class="text-slate-500">{{ $payment->notes }}</div>@endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold {{ $payment->amount < 0 ? 'text-rose-600' : 'text-emerald-700' }}" dir="ltr">{{ \App\Support\Money::format((float) $payment->amount, $payment->currency) }}</span>
                                    @if($payment->type === 'payment' && ! $payment->reversal)
                                        <button wire:click="reversePayment({{ $payment->id }})" wire:confirm="ئەم پارەدانە بگەڕێنرێتەوە؟ سەرەتا هۆکار لە خانەی خوارەوە بنووسە." class="text-[11px] text-rose-600 font-bold hover:underline">گەڕاندنەوە</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <x-input placeholder="هۆکاری گەڕاندنەوە (پێویستە)" wire:model="reverseReason" />
                    </div>
                @endif


            <x-slot name="footer">
                <div class="flex items-center justify-between w-full">
                    <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>چاپکردن / داگرتنی PDF</span>
                    </button>
                    <x-button flat label="داخستن" x-on:click="close" />
                </div>
            </x-slot>
        </x-modal-card>
    @endif

    <!-- Record Payment Modal -->
    <x-modal-card title="تۆمارکردنی پارەدان" wire:model="showPaymentModal" max-width="lg">
        @if($paymentInvoice)
            <div class="space-y-4">
                <div class="text-xs bg-slate-50 rounded-xl p-3 space-y-1">
                    <div>وەسڵ: <span class="font-mono font-bold" dir="ltr">{{ $paymentInvoice->invoice_number }}</span></div>
                    <div>کۆی گشتی: <span class="font-mono" dir="ltr">{{ \App\Support\Money::format((float) $paymentInvoice->total, $paymentInvoice->currency) }}</span>
                        · ماوە: <span class="font-mono font-bold text-amber-700" dir="ltr">{{ \App\Support\Money::format($paymentInvoice->remaining_balance, $paymentInvoice->currency) }}</span></div>
                    <div class="text-slate-500">پارەدان تەنها بە دراوی وەسڵ ({{ $paymentInvoice->currency }}) وەردەگیرێت.</div>
                </div>
                <x-input type="number" step="any" min="0" inputmode="decimal" label="بڕ *" prefix="{{ $paymentInvoice->currency }}" wire:model="pay_amount" />
                <x-input type="date" label="بەرواری وەرگرتن *" wire:model="pay_date" />
                <x-native-select label="شێواز *" wire:model="pay_method">
                    @foreach($paymentMethods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
                <x-input label="ژمارەی مامەڵە / سەرچاوە" wire:model="pay_reference" dir="ltr" />
                <x-textarea label="تێبینی" wire:model="pay_notes" />
            </div>
        @endif
        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                <x-button positive label="تۆمارکردن" wire:click="savePayment" spinner="savePayment" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
