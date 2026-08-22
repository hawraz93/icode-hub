<div class="space-y-6">
    
    <!-- Top Action Bar (Mobile Responsive) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="w-full sm:max-w-sm">
            <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان بەپێی عەقد، کڕیار یان بابەت..." icon="magnifying-glass" />
        </div>

        <button wire:click="openModal" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>دروستکردنی عەقدی نوێ</span>
        </button>
    </div>

    <!-- Mobile Contracts Cards (Visible on Mobile Only) -->
    <div class="md:hidden space-y-3">
        @forelse($contracts as $contract)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-mono font-bold text-indigo-600 text-xs" dir="ltr">{{ $contract->contract_number }}</div>
                        <h3 class="font-extrabold text-slate-900 text-base mt-0.5">{{ $contract->title }}</h3>
                        <p class="text-xs text-slate-500 font-medium">کڕیار: {{ $contract->client->business_name ?? $contract->client->name }}</p>
                    </div>
                    <div>
                        @if($contract->signed_by_client)
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">واژۆکراوە</span>
                        @else
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">چاوەڕوانە</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t border-slate-100">
                    <div>
                        <span class="text-slate-400 text-[11px] block">ماوەی عەقد:</span>
                        <span class="font-mono text-slate-700 text-[11px]" dir="ltr">
                            {{ $contract->start_date->format('Y-m-d') }} &rarr; {{ $contract->end_date ? $contract->end_date->format('Y-m-d') : 'بەردەوام' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[11px] block">بڕی گرێبەست:</span>
                        <span class="font-mono font-black text-slate-900 text-sm" dir="ltr">${{ number_format($contract->total_amount, 2) }}</span>
                    </div>
                </div>

                <!-- Mobile 1-Tap Action Buttons -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button wire:click="viewContract({{ $contract->id }})" class="px-3.5 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <span>بینین و چاپ</span>
                    </button>

                    <div class="flex items-center gap-1">
                        <button wire:click="edit({{ $contract->id }})" class="p-2 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="دەستکاری">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <button wire:click="confirmDelete({{ $contract->id }})" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition" title="سڕینەوە">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-slate-400 border border-slate-200">
                هیچ عەقدێک نەدۆزرایەوە.
            </div>
        @endforelse
    </div>

    <!-- Desktop Contracts Table (Visible on Desktop / Tablet Only) -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-xs">
                <thead class="bg-slate-50/75 text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="p-4 text-start font-bold">ژمارەی عەقد</th>
                        <th class="p-4 text-start font-bold">بابەتی عەقد</th>
                        <th class="p-4 text-start font-bold">کڕیار / کۆمپانیا</th>
                        <th class="p-4 text-start font-bold">ماوەی عەقد</th>
                        <th class="p-4 text-start font-bold">بڕی پارە</th>
                        <th class="p-4 text-start font-bold">واژۆ</th>
                        <th class="p-4 text-center font-bold">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($contracts as $contract)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="p-4 font-mono font-bold text-indigo-600" dir="ltr">
                                {{ $contract->contract_number }}
                            </td>

                            <td class="p-4">
                                <div class="font-extrabold text-slate-900">{{ $contract->title }}</div>
                                @if($contract->project)
                                    <div class="text-[11px] text-slate-500">پڕۆژە: {{ $contract->project->title }}</div>
                                @endif
                            </td>

                            <td class="p-4 font-semibold text-slate-800">
                                {{ $contract->client->business_name ?? $contract->client->name }}
                            </td>

                            <td class="p-4 font-mono text-slate-600" dir="ltr">
                                {{ $contract->start_date->format('Y-m-d') }} &rarr; {{ $contract->end_date ? $contract->end_date->format('Y-m-d') : 'بەردەوام' }}
                            </td>

                            <td class="p-4 font-mono font-black text-slate-900 text-sm" dir="ltr">
                                ${{ number_format($contract->total_amount, 2) }}
                            </td>

                            <td class="p-4">
                                @if($contract->signed_by_client)
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">واژۆکراوە</span>
                                @else
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">چاوەڕوانە</span>
                                @endif
                            </td>

                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="viewContract({{ $contract->id }})" class="p-1.5 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="بینین و چاپ">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <button wire:click="edit({{ $contract->id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="دەستکاری">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <button wire:click="confirmDelete({{ $contract->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-400">هیچ عەقدێک نەدۆزرایەوە.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $contracts->links() }}
        </div>
    </div>

    <!-- Mobile Pagination -->
    <div class="md:hidden">
        {{ $contracts->links() }}
    </div>

    <!-- WireUI Modal Card for Contract Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی عەقد' : 'دروستکردنی عەقدی کاری نوێ' }}" wire:model="showModal" max-width="3xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div>
                <x-select
                    label="کڕیار / کۆمپانیا *"
                    placeholder="کڕیار هەڵبژێرە"
                    wire:model="client_id"
                    :options="$clients"
                    option-label="displayName"
                    option-value="id"
                />
            </div>

            <div>
                <x-select
                    label="پڕۆژەی پەیوەندیدار"
                    placeholder="پڕۆژە هەڵبژێرە (ئارەزوومەندانە)"
                    wire:model="project_id"
                    :options="$projects"
                    option-label="title"
                    option-value="id"
                />
            </div>

            <div>
                <x-input label="ژمارەی عەقد *" wire:model="contract_number" dir="ltr" />
            </div>

            <div>
                <x-input label="ناونیشانی عەقد *" placeholder="عەقدی پەرەپێدانی سیستەم..." wire:model="title" />
            </div>

            <div>
                <x-currency
                    label="کۆی گوژمەی عەقد *"
                    placeholder="0.00"
                    prefix="$"
                    wire:model="total_amount"
                    thousands=","
                    decimal="."
                    precision="2"
                />
            </div>

            <div>
                <x-native-select
                    label="دۆخی عەقد *"
                    wire:model="status"
                    :options="[
                        ['name' => 'چالاک (Active)', 'id' => 'active'],
                        ['name' => 'تەواوبوو (Completed)', 'id' => 'completed'],
                        ['name' => 'ڕەشنووس (Draft)', 'id' => 'draft'],
                        ['name' => 'هەڵوەشاوەتەوە (Cancelled)', 'id' => 'cancelled'],
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>

            <div>
                <x-datetime-picker
                    label="بەرواری دەستپێکردن *"
                    placeholder="YYYY-MM-DD"
                    without-time="true"
                    wire:model="start_date"
                    display-format="YYYY-MM-DD"
                />
            </div>

            <div>
                <x-datetime-picker
                    label="بەرواری کۆتایی"
                    placeholder="YYYY-MM-DD"
                    without-time="true"
                    wire:model="end_date"
                    display-format="YYYY-MM-DD"
                />
            </div>

            <div class="md:col-span-2">
                <x-textarea rows="6" label="مەرج و بەندەکانی عەقد *" placeholder="ماددە و بەندەکان بنووسە..." wire:model="terms" />
            </div>

            <div>
                <x-input label="ناوی واژۆکەری کڕیار" placeholder="ناوی خاوەن کار..." wire:model="client_signature_name" />
            </div>

            <div class="flex items-center pt-6">
                <x-toggle label="واژۆکراوە لەلایەن کڕیارەوە" wire:model="signed_by_client" />
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردنی عەقد' }}" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>

    <!-- Official Printable Contract Modal -->
    @if($viewingContract)
        <x-modal-card title="ڕێککەوتننامە و گرێبەست (چاپکردن)" wire:model="showViewModal" max-width="4xl">
            <div id="contract-print-area" class="bg-white p-6 sm:p-10 rounded-2xl text-slate-900 space-y-6">
                
                <!-- Contract Official Header -->
                <div class="text-center pb-6 border-b-2 border-slate-900 space-y-2">
                    <img src="{{ asset('images/logo.png') }}" alt="iCode Group" class="h-16 w-auto mx-auto object-contain">
                    <h2 class="text-xl font-black text-slate-900">ڕێککەوتننامە و عەقدی پەرەپێدانی سۆفتوێر</h2>
                    <div class="text-xs font-mono text-slate-500 font-bold" dir="ltr">{{ $viewingContract->contract_number }}</div>
                </div>

                <!-- Contract Parties -->
                <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl text-xs border border-slate-200">
                    <div>
                        <strong class="font-extrabold text-slate-900 block mb-1 text-sm">لایەنی یەکەم (دابینکەر):</strong>
                        <div class="text-slate-700 font-bold">iCode Group (Hawraz Khaled)</div>
                        <div class="text-slate-600">پسپۆڕی پەرەپێدانی سیستەم و تەکنەلۆژیای زانیاری</div>
                        <div class="text-slate-500 font-mono mt-1" dir="ltr">info@icode.com | 0750 445 1234</div>
                    </div>

                    <div>
                        <strong class="font-extrabold text-slate-900 block mb-1 text-sm">لایەنی دووەم (کڕیار):</strong>
                        <div class="text-slate-700 font-bold">{{ $viewingContract->client->business_name ?? $viewingContract->client->name }}</div>
                        <div class="text-slate-600">نوێنەر: {{ $viewingContract->client->name }}</div>
                        <div class="text-slate-500 font-mono mt-1" dir="ltr">{{ $viewingContract->client->phone }}</div>
                    </div>
                </div>

                <!-- Contract Title & Amount Banner -->
                <div class="flex items-center justify-between p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl text-xs">
                    <div>
                        <span class="text-slate-500">بابەتی عەقد:</span>
                        <strong class="text-slate-900 block text-sm font-extrabold">{{ $viewingContract->title }}</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-slate-500">کۆی گوژمەی عەقد:</span>
                        <div class="text-lg font-black font-mono text-indigo-700" dir="ltr">${{ number_format($viewingContract->total_amount, 2) }}</div>
                    </div>
                </div>

                <!-- Terms Body -->
                <div class="space-y-3 text-xs leading-relaxed text-slate-800">
                    <strong class="font-bold text-slate-900 block text-sm border-b pb-1">مەرج و بەندەکانی ڕێککەوتننامە:</strong>
                    <div class="whitespace-pre-line bg-slate-50/40 p-4 rounded-xl border border-slate-100">
                        {{ $viewingContract->terms }}
                    </div>
                </div>

                <!-- Signatures Block -->
                <div class="grid grid-cols-2 gap-12 pt-10 border-t-2 border-slate-200 text-xs">
                    <div class="text-center space-y-8">
                        <strong class="font-extrabold text-slate-900 block">واژۆی لایەنی یەکەم (iCode Group)</strong>
                        <div class="font-mono text-slate-400 font-bold">Hawraz Khaled</div>
                        <div class="border-t border-slate-300 w-40 mx-auto pt-1 text-slate-500">مۆر و واژۆ</div>
                    </div>

                    <div class="text-center space-y-8">
                        <strong class="font-extrabold text-slate-900 block">واژۆی لایەنی دووەم (کڕیار)</strong>
                        <div class="text-slate-700 font-bold">{{ $viewingContract->client_signature_name ?: $viewingContract->client->name }}</div>
                        <div class="border-t border-slate-300 w-40 mx-auto pt-1 text-slate-500">مۆر و واژۆ</div>
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
