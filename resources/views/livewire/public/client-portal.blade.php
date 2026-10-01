<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    
    @if(!$client)
        <!-- Portal Login Card -->
        <div class="max-w-md mx-auto bg-slate-900/80 border border-slate-800 rounded-3xl p-8 shadow-2xl backdrop-blur-xl space-y-6">
            <div class="text-center space-y-2">
                <img src="{{ asset('images/logo.png') }}" alt="iCode" class="h-12 w-auto mx-auto object-contain">
                <h2 class="text-2xl font-black text-white">پۆرتاڵی تایبەت بە کڕیاران</h2>
                <p class="text-xs text-slate-400">کۆدی تایبەتی کڕیار یان ژمارەی مۆبایلەکەت بنووسە</p>
            </div>

            <form wire:submit.prevent="login" class="space-y-4">
                <div>
                    <x-input 
                        label="کۆدی پۆرتاڵ یان ژمارەی مۆبایل" 
                        placeholder="نموونە: CL-SP-8821 یان 07501234567" 
                        wire:model="accessCode" 
                        dir="ltr"
                    />
                </div>

                <button type="submit" class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white font-extrabold text-sm shadow-xl shadow-indigo-500/25 transition">
                    چوونەژوورەوە
                </button>
            </form>

            <div class="pt-4 border-t border-slate-800 text-center text-xs text-slate-500">
                کۆدی پۆرتاڵت لەدەستداوە؟ <a href="https://wa.me/9647504451234" target="_blank" class="text-indigo-400 hover:underline">پەیوەندی بە پشتگیری بکە</a>
            </div>
        </div>
    @else
        <!-- Client Dashboard View -->
        <div class="space-y-8">
            
            <!-- Client Header Banner -->
            <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 backdrop-blur-xl">
                <div>
                    <span class="text-xs text-cyan-400 font-bold uppercase tracking-wider">بەخێربێیت بۆ پۆرتاڵەکەت</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white mt-1">{{ $client->business_name ?? $client->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">کۆدی پۆرتاڵ: <strong class="text-slate-200 font-mono" dir="ltr">{{ $client->portal_access_code }}</strong></p>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="$set('showTicketModal', true)" class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white transition shadow">
                        + داواکاری نوێ / پشتگیری
                    </button>

                    <button wire:click="logout" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                        دەرچوون
                    </button>
                </div>
            </div>

            <!-- Client Active Services & Expiry Timeline -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6">
                <div>
                    <h3 class="text-xl font-bold text-white">خزمەتگوزاری و دۆمەینە چالاکەکانت</h3>
                    <p class="text-xs text-slate-400">چاودێری بەرواری نوێکردنەوە و کۆتا ڕۆژی هۆستینگ و دۆمەین</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($client->subscriptions as $sub)
                        <div class="p-5 rounded-2xl bg-slate-950/60 border {{ $sub->days_until_expiry <= 30 ? 'border-rose-500/50' : 'border-slate-800' }} space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-indigo-400">
                                    {{ $sub->type_label }}
                                </span>
                                <span class="text-xs font-extrabold {{ $sub->days_until_expiry <= 30 ? 'text-rose-400 animate-pulse' : 'text-emerald-400' }}">
                                    {{ $sub->days_until_expiry }} ڕۆژ ماوە
                                </span>
                            </div>

                            <h4 class="font-bold text-white text-base">{{ $sub->name }}</h4>
                            @if($sub->domain_name)
                                <div class="font-mono text-xs text-cyan-400 font-bold" dir="ltr">{{ $sub->domain_name }}</div>
                            @endif

                            <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                                <span>بەرواری بەسەرچوون:</span>
                                <span class="font-mono font-bold text-slate-200" dir="ltr">{{ $sub->expiry_date->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-8 text-center text-slate-500 text-sm">هیچ خزمەتگوزارییەکی چالاک تۆمار نەکراوە.</div>
                    @endforelse
                </div>
            </div>

            <!-- Invoices & Contracts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Client Invoices -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 space-y-4">
                    <h3 class="text-lg font-bold text-white">وەسڵەکانی ئێوە</h3>
                    <div class="divide-y divide-slate-800">
                        @forelse($client->invoices as $inv)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <div class="font-mono font-bold text-indigo-400 text-xs" dir="ltr">{{ $inv->invoice_number }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $inv->issue_date->format('Y-m-d') }}</div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-white text-sm" dir="ltr">{{ \App\Support\Money::format((float) $inv->total, $inv->currency) }}</span>
                                    <button wire:click="viewInvoice({{ $inv->id }})" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg transition">
                                        بینین و داگرتن
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-500 text-xs">هیچ وەسڵێک نەدۆزرایەوە.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Client Contracts -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-6 space-y-4">
                    <h3 class="text-lg font-bold text-white">عەقد و ڕێککەوتننامەکان</h3>
                    <div class="divide-y divide-slate-800">
                        @forelse($client->contracts as $cnt)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-white text-xs">{{ $cnt->title }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono" dir="ltr">{{ $cnt->contract_number }}</div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="font-mono font-bold text-white text-sm" dir="ltr">{{ \App\Support\Money::format((float) $cnt->total_amount, $cnt->currency) }}</span>
                                    <button wire:click="viewContract({{ $cnt->id }})" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg transition">
                                        بینینی عەقد
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-slate-500 text-xs">هیچ عەقدێک نەدۆزرایەوە.</div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

        <!-- Ticket Modal -->
        <x-modal wire:model="showTicketModal" max-width="lg">
            <x-card title="داواکاری نوێ / پشتگیری تەکنیکی">
                <div class="space-y-4 text-slate-900">
                    <x-input label="بابەتی داواکاری *" placeholder="کێشەی ئیمەیڵ، گۆڕینی وێنەی وێبسایت..." wire:model="ticket_subject" />
                    
                    <x-native-select
                        label="ئاستی گرنگی *"
                        wire:model="ticket_priority"
                        :options="[
                            ['name' => 'ئاسایی (Medium)', 'id' => 'medium'],
                            ['name' => 'کەم (Low)', 'id' => 'low'],
                            ['name' => 'گرنگ (High)', 'id' => 'high'],
                            ['name' => 'بەپەلە (Urgent)', 'id' => 'urgent'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />

                    <x-textarea label="شیکاری و ڕوونکردنەوەی کێشەکە *" placeholder="تکایە بە وردی ڕوونی بکەرەوە..." wire:model="ticket_description" />
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-end gap-3">
                        <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                        <x-button primary label="ناردنی داواکاری" wire:click="submitTicket" />
                    </div>
                </x-slot>
            </x-card>
        </x-modal>

        <!-- Printable Invoice Modal for Client -->
        @if($selectedInvoice)
            <x-modal wire:model="showInvoiceModal" max-width="3xl">
                <x-card>
                    <div class="bg-white p-6 rounded-2xl text-slate-900 space-y-6">
                        <div class="flex justify-between items-center border-b pb-4">
                            <img src="{{ asset('images/logo.png') }}" alt="iCode" class="h-12 w-auto">
                            <div class="text-end">
                                <div class="font-mono font-bold text-indigo-600 text-sm" dir="ltr">{{ $selectedInvoice->invoice_number }}</div>
                                <div class="text-xs text-slate-500">{{ $selectedInvoice->issue_date->format('Y-m-d') }}</div>
                            </div>
                        </div>

                        <div class="space-y-2 text-xs">
                            @foreach($selectedInvoice->items as $item)
                                <div class="flex justify-between p-2 bg-slate-50 rounded-lg">
                                    <span>{{ $item->description }}</span>
                                    <span class="font-mono font-bold" dir="ltr">{{ \App\Support\Money::format((float) $item->total_price, $selectedInvoice->currency) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between text-base font-black border-t pt-3">
                            <span>کۆی گشتی:</span>
                            <span class="font-mono text-indigo-600" dir="ltr">{{ \App\Support\Money::format((float) $selectedInvoice->total, $selectedInvoice->currency) }}</span>
                        </div>
                    </div>

                    <x-slot name="footer">
                        <div class="flex items-center justify-between w-full">
                            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">
                                چاپکردن
                            </button>
                            <x-button flat label="داخستن" x-on:click="close" />
                        </div>
                    </x-slot>
                </x-card>
            </x-modal>
        @endif

        <!-- Printable Contract Modal for Client -->
        @if($selectedContract)
            <x-modal wire:model="showContractModal" max-width="3xl">
                <x-card>
                    <div class="bg-white p-6 rounded-2xl text-slate-900 space-y-4 text-xs">
                        <h3 class="text-lg font-black">{{ $selectedContract->title }}</h3>
                        <div class="font-mono text-slate-500 font-bold" dir="ltr">{{ $selectedContract->contract_number }}</div>
                        <div class="whitespace-pre-line bg-slate-50 p-4 rounded-xl leading-relaxed">
                            {{ $selectedContract->terms }}
                        </div>
                        <div class="flex justify-between font-bold text-sm pt-2">
                            <span>کۆی گوژمە:</span>
                            <span class="font-mono text-indigo-600" dir="ltr">{{ \App\Support\Money::format((float) $selectedContract->total_amount, $selectedContract->currency) }}</span>
                        </div>
                    </div>

                    <x-slot name="footer">
                        <div class="flex items-center justify-between w-full">
                            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">
                                چاپکردن
                            </button>
                            <x-button flat label="داخستن" x-on:click="close" />
                        </div>
                    </x-slot>
                </x-card>
            </x-modal>
        @endif
    @endif

</div>
