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
                        @if($invoice->status === 'paid')
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">دراوە</span>
                        @elseif($invoice->status === 'sent')
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">نێردراوە</span>
                        @elseif($invoice->status === 'partial')
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">بەشێکی دراوە</span>
                        @elseif($invoice->status === 'overdue')
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">دواکەوتووە</span>
                        @else
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600">ڕەشنووس</span>
                        @endif
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
                        <span class="font-mono font-black text-slate-900 text-sm" dir="ltr">${{ number_format($invoice->total, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">بڕی دراو:</span>
                        <span class="font-mono font-bold text-emerald-600 text-sm" dir="ltr">${{ number_format($invoice->paid_amount, 2) }}</span>
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
                        <a href="https://wa.me/{{ $invoice->client->whatsapp_number }}?text={{ urlencode('سڵاو ڕێز بەڕێز ' . $invoice->client->name . '، وەسڵی فەرمی ژمارە (' . $invoice->invoice_number . ') بە کۆی گشتی $' . $invoice->total . ' ئامادەیە. سوپاس بۆ مامەڵەکردنتان لەگەڵ iCode Group.') }}" 
                           target="_blank"
                           class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>واتسئاپ</span>
                        </a>

                        @if($invoice->status !== 'paid')
                            <button wire:click="markAsPaid({{ $invoice->id }})" class="p-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition" title="پاکتاو">
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
                                ${{ number_format($invoice->total, 2) }}
                            </td>

                            <td class="p-4 font-mono font-bold text-emerald-600" dir="ltr">
                                ${{ number_format($invoice->paid_amount, 2) }}
                            </td>

                            <td class="p-4">
                                @if($invoice->status === 'paid')
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">دراوە</span>
                                @elseif($invoice->status === 'sent')
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200">نێردراوە</span>
                                @elseif($invoice->status === 'partial')
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">بەشێکی دراوە</span>
                                @elseif($invoice->status === 'overdue')
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">دواکەوتووە</span>
                                @else
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-slate-100 text-slate-600">ڕەشنووس</span>
                                @endif
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- View/Print Button -->
                                    <button wire:click="viewInvoice({{ $invoice->id }})" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="بینین و چاپ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <!-- Quick Mark Paid -->
                                    @if($invoice->status !== 'paid')
                                        <button wire:click="markAsPaid({{ $invoice->id }})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="دیاریکردن وەک دراو">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    @endif

                                    <!-- WhatsApp Send -->
                                    <a href="https://wa.me/{{ $invoice->client->whatsapp_number }}?text={{ urlencode('سڵاو ڕێز بەڕێز ' . $invoice->client->name . '، وەسڵی فەرمی ژمارە (' . $invoice->invoice_number . ') بە کۆی گشتی $' . $invoice->total . ' ئامادەیە. سوپاس بۆ مامەڵەکردنتان لەگەڵ iCode Group.') }}" 
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
                        wire:model="client_id"
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
                        :options="[
                            ['name' => 'ڕەشنووس (Draft)', 'id' => 'draft'],
                            ['name' => 'نێردراوە (Sent)', 'id' => 'sent'],
                            ['name' => 'دراوە (Paid)', 'id' => 'paid'],
                            ['name' => 'بەشێکی دراوە (Partial)', 'id' => 'partial'],
                            ['name' => 'دواکەوتووە (Overdue)', 'id' => 'overdue'],
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
                            <div class="md:col-span-6">
                                <x-input label="وەسف / خزمەتگوزاری" placeholder="پەرەپێدان، نوێکردنەوەی دۆمەین، هۆستینگ..." wire:model="items.{{ $index }}.description" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input label="ژمارە / بڕ" type="number" step="0.5" wire:model="items.{{ $index }}.quantity" />
                            </div>

                            <div class="md:col-span-3">
                                <x-currency
                                    label="نرخی تاک ($)"
                                    placeholder="0.00"
                                    prefix="$"
                                    wire:model="items.{{ $index }}.unit_price"
                                    thousands=","
                                    decimal="."
                                    precision="2"
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
                    <x-currency
                        label="داشکاندن (Discount)"
                        placeholder="0.00"
                        prefix="$"
                        wire:model="discount"
                        thousands=","
                        decimal="."
                        precision="2"
                    />
                </div>

                <div>
                    <x-currency
                        label="بڕی دراو (Paid Amount)"
                        placeholder="0.00"
                        prefix="$"
                        wire:model="paid_amount"
                        thousands=","
                        decimal="."
                        precision="2"
                    />
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
            <div id="invoice-print-area" class="bg-white p-6 sm:p-8 rounded-2xl text-slate-900 space-y-6">
                
                <!-- Invoice Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b-2 border-slate-100">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo.png') }}" alt="iCode Group" class="h-16 w-auto object-contain">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 tracking-tight">iCode Group</h2>
                            <p class="text-xs text-slate-500 font-medium">Software Development & IT Solutions</p>
                            <p class="text-xs text-slate-400 font-mono" dir="ltr">info@icode.com | 0750 445 1234</p>
                        </div>
                    </div>

                    <div class="text-start sm:text-end">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">وەسڵی فەرمی / INVOICE</div>
                        <div class="text-xl font-black font-mono text-indigo-600 mt-1" dir="ltr">{{ $viewingInvoice->invoice_number }}</div>
                        <div class="text-xs text-slate-500 mt-1 font-mono">بەروار: {{ $viewingInvoice->issue_date->format('Y-m-d') }}</div>
                        <div class="text-xs text-slate-500 font-mono">کۆتا کات: {{ $viewingInvoice->due_date->format('Y-m-d') }}</div>
                    </div>
                </div>

                <!-- Client Details & Bill To -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl text-xs">
                    <div>
                        <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">وەسڵ بۆ / Bill To:</span>
                        <strong class="text-sm font-extrabold text-slate-900 block">{{ $viewingInvoice->client->business_name ?? $viewingInvoice->client->name }}</strong>
                        <div class="text-slate-600 mt-1">{{ $viewingInvoice->client->name }}</div>
                        <div class="text-slate-600 font-mono mt-0.5" dir="ltr">{{ $viewingInvoice->client->phone }}</div>
                        <div class="text-slate-500 mt-0.5">{{ $viewingInvoice->client->city }} - {{ $viewingInvoice->client->address }}</div>
                    </div>

                    <div class="sm:text-end">
                        <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">دۆخی وەسڵ / Status:</span>
                        <span class="inline-block px-3 py-1 text-xs font-black rounded-full {{ $viewingInvoice->status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $viewingInvoice->status_label }}
                        </span>
                        <div class="text-slate-500 text-xs mt-2">شێوازی دان: {{ $viewingInvoice->payment_method ?? 'FIB / FastPay / کاش' }}</div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-start">
                        <thead class="bg-slate-100/80 text-slate-700 font-bold border-y border-slate-200">
                            <tr>
                                <th class="p-3 text-start">#</th>
                                <th class="p-3 text-start">وەسف و خزمەتگوزاری (Description)</th>
                                <th class="p-3 text-center">ژمارە (Qty)</th>
                                <th class="p-3 text-end">نرخی تاک (Unit Price)</th>
                                <th class="p-3 text-end">کۆی بڕ (Total)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($viewingInvoice->items as $idx => $item)
                                <tr>
                                    <td class="p-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="p-3 font-semibold text-slate-900">{{ $item->description }}</td>
                                    <td class="p-3 text-center font-mono font-bold">{{ $item->quantity }}</td>
                                    <td class="p-3 text-end font-mono" dir="ltr">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="p-3 text-end font-mono font-extrabold text-slate-900" dir="ltr">${{ number_format($item->total_price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Summary Totals -->
                <div class="flex flex-col sm:flex-row justify-between gap-6 pt-4 border-t-2 border-slate-100">
                    <div class="text-xs text-slate-500 max-w-sm space-y-1">
                        <strong class="text-slate-800 block mb-1">تێبینی و مەرجەکان:</strong>
                        <p>{{ $viewingInvoice->terms ?? 'سوپاس بۆ متمانەکردنتان بە iCode Group.' }}</p>
                        @if($viewingInvoice->notes)
                            <p class="text-indigo-600 font-medium">{{ $viewingInvoice->notes }}</p>
                        @endif
                    </div>

                    <div class="w-full sm:w-64 space-y-2 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>کۆی سەرەتایی (Subtotal):</span>
                            <span class="font-mono font-bold" dir="ltr">${{ number_format($viewingInvoice->subtotal, 2) }}</span>
                        </div>

                        @if($viewingInvoice->discount > 0)
                            <div class="flex justify-between text-rose-600">
                                <span>داشکاندن (Discount):</span>
                                <span class="font-mono font-bold" dir="ltr">-${{ number_format($viewingInvoice->discount, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between pt-2 border-t border-slate-200 text-sm font-black text-slate-900">
                            <span>کۆی گشتی (Total):</span>
                            <span class="font-mono font-black text-indigo-600" dir="ltr">${{ number_format($viewingInvoice->total, 2) }}</span>
                        </div>

                        <div class="flex justify-between text-emerald-600 font-bold pt-1">
                            <span>بڕی دراو (Paid):</span>
                            <span class="font-mono" dir="ltr">${{ number_format($viewingInvoice->paid_amount, 2) }}</span>
                        </div>

                        @if($viewingInvoice->remaining_balance > 0)
                            <div class="flex justify-between text-amber-700 font-extrabold pt-1">
                                <span>ماوە بۆ دان (Balance):</span>
                                <span class="font-mono" dir="ltr">${{ number_format($viewingInvoice->remaining_balance, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment Accounts / Bank Details -->
                <div class="p-4 bg-slate-900 text-white rounded-xl text-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <span class="text-slate-400 block text-[11px]">شێوازی پارەدان و هەژمارەکان:</span>
                        <span class="font-mono text-cyan-400 font-bold">FIB Account / FastPay: 0750 445 1234</span>
                    </div>
                    <div class="text-start sm:text-end text-[11px] text-slate-400 font-mono">
                        iCode Group | Hawraz Khaled
                    </div>
                </div>

            </div>

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

</div>
