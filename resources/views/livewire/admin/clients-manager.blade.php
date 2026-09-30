<div class="space-y-6">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="w-full sm:max-w-sm">
            <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان بەپێی ناوی کڕیار، کۆمپانیا یان مۆبایل..." icon="magnifying-glass" />
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>زیادکردنی کڕیاری نوێ</span>
        </button>
    </div>

    <!-- Mobile-First Clients Cards (Visible on Mobile Only) -->
    <div class="md:hidden space-y-3">
        @forelse($clients as $client)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base">{{ $client->business_name ?? $client->name }}</h3>
                        @if($client->business_name)
                            <p class="text-xs text-slate-500 font-medium">{{ $client->name }}</p>
                        @endif
                    </div>
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full {{ $client->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $client->status === 'active' ? 'چالاک' : 'ناچالاک' }}
                    </span>
                </div>

                <!-- Contact & Location Info -->
                <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                    <div>
                        <span class="text-slate-400 text-[11px] block">مۆبایل:</span>
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $client->phone) }}" class="font-mono font-bold text-indigo-600" dir="ltr">
                            {{ $client->phone }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">شار:</span>
                        <span class="font-semibold text-slate-700">{{ $client->city ?? 'هەولێر' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">بەشداریکردن:</span>
                        <span class="font-bold text-slate-800">{{ $client->subscriptions->count() }} دۆمەین/هۆست</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">باڵانس:</span>
                        <span class="font-mono font-extrabold {{ $client->pending_amount > 0 ? 'text-amber-600' : 'text-emerald-600' }}" dir="ltr">
                            ${{ number_format($client->total_paid, 2) }}
                        </span>
                    </div>
                </div>

                <!-- 1-Tap Quick Action Buttons for Mobile -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <!-- Direct Phone Call -->
                        <a href="tel:{{ preg_replace('/[^0-9]/', '', $client->phone) }}" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span>پەیوەندی</span>
                        </a>

                        <!-- Direct WhatsApp -->
                        <a href="https://wa.me/{{ $client->whatsapp_number }}" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            <span>واتسئاپ</span>
                        </a>
                    </div>

                    <div class="flex items-center gap-1">
                        <button wire:click="edit({{ $client->id }})" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="دەستکاری">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button wire:click="confirmDelete({{ $client->id }})" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition" title="سڕینەوە">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                هیچ کڕیارێک نەدۆزرایەوە.
            </div>
        @endforelse
    </div>

    <!-- Desktop Clients Table (Visible on Desktop / Tablet Only) -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
                <thead class="bg-slate-50/75 text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="p-4 text-start font-bold">کڕیار / ناوی کۆمپانیا</th>
                        <th class="p-4 text-start font-bold">پەیوەندی (Phone / WhatsApp)</th>
                        <th class="p-4 text-start font-bold">شار و ناونیشان</th>
                        <th class="p-4 text-start font-bold">خزمەتگوزارییە چالاکەکان</th>
                        <th class="p-4 text-start font-bold">باڵانسی دارایی</th>
                        <th class="p-4 text-start font-bold">کۆدی پۆرتاڵ</th>
                        <th class="p-4 text-center font-bold">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-4">
                                <div class="font-extrabold text-slate-900 text-sm">{{ $client->business_name ?? $client->name }}</div>
                                @if($client->business_name)
                                    <div class="text-slate-500 text-xs">{{ $client->name }}</div>
                                @endif
                            </td>

                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-slate-800" dir="ltr">{{ $client->phone }}</span>
                                    <a href="https://wa.me/{{ $client->whatsapp_number }}" target="_blank" class="p-1 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    </a>
                                </div>
                                @if($client->email)
                                    <div class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $client->email }}</div>
                                @endif
                            </td>

                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">{{ $client->city ?? 'هەولێر' }}</span>
                                <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ $client->address }}</div>
                            </td>

                            <td class="p-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700">
                                    {{ $client->subscriptions->count() }} دۆمەین/هۆست
                                </span>
                            </td>

                            <td class="p-4">
                                <div>
                                    <span class="text-slate-400 text-[10px]">دراوە: </span>
                                    <span class="font-mono font-bold text-emerald-600" dir="ltr">${{ number_format($client->total_paid, 2) }}</span>
                                </div>
                                @if($client->pending_amount > 0)
                                    <div>
                                        <span class="text-slate-400 text-[10px]">ماوە: </span>
                                        <span class="font-mono font-extrabold text-amber-600" dir="ltr">${{ number_format($client->pending_amount, 2) }}</span>
                                    </div>
                                @endif
                            </td>

                            <td class="p-4">
                                <span class="px-2 py-1 rounded bg-slate-100 text-slate-800 font-mono font-bold text-xs" dir="ltr">
                                    {{ $client->portal_access_code }}
                                </span>
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $client->id }})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="دەستکاری">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <button wire:click="confirmDelete({{ $client->id }})" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">هیچ کڕیارێک نەدۆزرایەوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $clients->links() }}
        </div>
    </div>

    <!-- Mobile Pagination -->
    <div class="md:hidden">
        {{ $clients->links() }}
    </div>

    <!-- WireUI Modal Card for Client Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی کڕیار' : 'زیادکردنی کڕیاری نوێ' }}" wire:model="showModal" max-width="2xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div>
                <x-input label="ناوی کەسی بەرپرسیار *" placeholder="کاک / د. ..." wire:model="name" />
            </div>

            <div>
                <x-input label="ناوی کۆمپانیا / تاقیگە / دەرمانخانە" placeholder="کۆمپانیای ..." wire:model="business_name" />
            </div>

            <!-- Phone & WhatsApp Inputs -->
            <div>
                <x-phone
                    label="ژمارەی مۆبایل *"
                    placeholder="0750-123-4567"
                    wire:model="phone"
                    mask="####-###-####"
                    dir="ltr"
                />
            </div>

            <div>
                <x-phone
                    label="ژمارەی واتسئاپ"
                    placeholder="0750-123-4567"
                    wire:model="whatsapp"
                    mask="####-###-####"
                    dir="ltr"
                />
            </div>

            <div>
                <x-input label="ئیمەیڵ" placeholder="client@company.com" wire:model="email" dir="ltr" />
            </div>

            <div>
                <x-native-select
                    label="شار *"
                    wire:model="city"
                    :options="[
                        ['name' => 'هەولێر (Erbil)', 'id' => 'هەولێر'],
                        ['name' => 'سلێمانی (Sulaymaniyah)', 'id' => 'سلێمانی'],
                        ['name' => 'دهۆک (Duhok)', 'id' => 'دهۆک'],
                        ['name' => 'کەرکووک (Kirkuk)', 'id' => 'کەرکووک'],
                        ['name' => 'هەڵەبجە (Halabja)', 'id' => 'هەڵەبجە'],
                        ['name' => 'زاخۆ (Zakho)', 'id' => 'زاخۆ'],
                        ['name' => 'سۆران (Soran)', 'id' => 'سۆران'],
                        ['name' => 'گەرمیان / کەلار (Kalar)', 'id' => 'گەرمیان'],
                        ['name' => 'ڕانیە / ڕاپەڕین (Ranya)', 'id' => 'ڕانیە'],
                        ['name' => 'بەغداد (Baghdad)', 'id' => 'بەغداد'],
                        ['name' => 'شارێکی تر (Other)', 'id' => 'شارێکی تر'],
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>

            <div class="md:col-span-2">
                <x-input label="ناونیشانی تەواو" placeholder="شەقام، ناوچە، بینا..." wire:model="address" />
            </div>

            <div>
                <x-input label="کۆدی چوونەژوورەوەی پۆرتاڵ *" wire:model="portal_access_code" dir="ltr" />
            </div>

            <div>
                <x-native-select
                    label="دۆخی ئەکاونت *"
                    wire:model="status"
                    :options="[
                        ['name' => 'چالاک (Active)', 'id' => 'active'],
                        ['name' => 'ناچالاک (Inactive)', 'id' => 'inactive'],
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>

            <div class="md:col-span-2">
                <x-textarea label="تێبینی تایبەت لەسەر کڕیار" placeholder="تێبینی لەسەر ڕێککەوتن، شێوازی پەیوەندی..." wire:model="notes" />
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
