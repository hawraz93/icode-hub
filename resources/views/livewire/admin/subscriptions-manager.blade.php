<div class="space-y-6">
    
    <!-- 1. Totals per currency (dollars and dinars shown separately) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500">🌐 هۆستینگ و سێرڤەر</div>
            <x-money-lines :totals="$totalHostingRevenue" class="text-lg sm:text-xl font-black text-slate-900" />
            <p class="text-[10px] text-slate-400 font-medium">نرخی فرۆشتنی چالاکەکان</p>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500">🔗 دۆمەین و ئیمەیڵ</div>
            <x-money-lines :totals="$totalDomainEmailRevenue" class="text-lg sm:text-xl font-black text-slate-900" />
            <p class="text-[10px] text-slate-400 font-medium">نرخی فرۆشتنی چالاکەکان</p>
        </div>

        <div class="bg-emerald-50/70 rounded-2xl p-4 border border-emerald-200/70 shadow-xs space-y-1">
            <div class="text-xs font-bold text-emerald-800">🟢 قازانج <span class="font-normal">({{ $yearFilter === 'all' ? 'هەمووی' : $yearFilter }})</span></div>
            <x-money-lines :totals="$filteredProfit" sign="+" class="text-lg sm:text-xl font-black text-emerald-700" />
            <p class="text-[10px] text-emerald-600 font-medium">فرۆشتن - تێچووی خۆت</p>
        </div>

        <button type="button" wire:click="$set('statusFilter', '{{ $statusFilter === 'unpaid' ? 'all' : 'unpaid' }}')"
                class="text-start rounded-2xl p-4 border shadow-xs space-y-1 cursor-pointer transition {{ $unpaidCount ? 'bg-rose-50 border-rose-200 hover:border-rose-300' : 'bg-white border-slate-200/80' }} {{ $statusFilter === 'unpaid' ? 'ring-2 ring-rose-300' : '' }}">
            <div class="text-xs font-bold {{ $unpaidCount ? 'text-rose-700' : 'text-slate-500' }}">🔴 پارەی نەداوە · {{ $unpaidCount }}</div>
            <x-money-lines :totals="$unpaidTotals" class="text-lg sm:text-xl font-black {{ $unpaidCount ? 'text-rose-600' : 'text-slate-400' }}" />
            <p class="text-[10px] {{ $unpaidCount ? 'text-rose-600' : 'text-slate-400' }} font-medium">{{ $statusFilter === 'unpaid' ? 'دەست لێبدە بۆ پیشاندانی هەمووی' : 'دەست لێبدە بۆ بینینیان' }}</p>
        </button>
    </div>
    <!-- 2. Search, Year Filter & Action Bar (Mobile Responsive) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex items-center gap-2.5 w-full lg:w-auto flex-1">
            
            <!-- Search -->
            <div class="w-full lg:w-64">
                <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان بەپێی ناوی دۆمەین، کڕیار..." />
            </div>

            <!-- Year Filter Dropdown -->
            <div class="w-full sm:w-36">
                <select wire:model.live="yearFilter" class="w-full rounded-xl border-slate-300 text-xs font-bold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 bg-slate-50">
                    <option value="all">📅 هەموو ساڵەکان</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}">ساڵی {{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Type Filter Dropdown -->
            <div class="w-full sm:w-48">
                <select wire:model.live="typeFilter" class="w-full rounded-xl border-slate-300 text-xs font-semibold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">هەموو جۆرەکان</option>
                    <option value="bundle">🌐 هۆستینگ و وێبسایت (Bundle)</option>
                    <option value="hosting">🖥️ تەنها هۆستینگ (Hosting)</option>
                    <option value="domain">🔗 تەنها دۆمەین (Domain)</option>
                    <option value="email">✉️ ئیمەیڵ (Email)</option>
                    <option value="license">🔑 مۆڵەتنامە (License)</option>
                    <option value="vps">☁️ سێرڤەر (VPS)</option>
                    <option value="maintenance">🛠️ پشتگیری و چاکسازی (Maintenance)</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="w-full sm:w-32">
                <select wire:model.live="statusFilter" class="w-full rounded-xl border-slate-300 text-xs font-semibold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="all">هەموو دۆخەکان</option>
                    <option value="active">چالاک (Active)</option>
                    <option value="expired">بەسەرچوو (Expired)</option>
                    <option value="unpaid">🔴 پارەی نەداوە</option>
                </select>
            </div>

        </div>

        <button wire:click="openModal" class="w-full lg:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 sm:py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-200 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>تۆمارکردنی دۆمەین / هۆستینگی نوێ</span>
        </button>
    </div>

    <!-- Subscriptions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($subscriptions as $sub)
            <div class="bg-white rounded-2xl border {{ $sub->days_until_expiry <= 7 ? 'border-rose-300 ring-2 ring-rose-100' : ($sub->days_until_expiry <= 30 ? 'border-amber-300' : 'border-slate-200') }} shadow-sm p-6 flex flex-col justify-between relative overflow-hidden">
                
                <!-- Status Top Tag -->
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $sub->type === 'domain' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : ($sub->type === 'hosting' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : ($sub->type === 'license' ? 'bg-amber-50 text-amber-800 border border-amber-300 font-extrabold' : 'bg-purple-50 text-purple-700 border border-purple-200')) }}">
                            {{ $sub->type_label }}
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold {{ $sub->days_until_expiry < 0 ? 'bg-red-100 text-red-800' : ($sub->days_until_expiry <= 7 ? 'bg-rose-100 text-rose-800 animate-pulse' : ($sub->days_until_expiry <= 30 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700')) }}">
                            {{ $sub->expiry_status_text }}
                        </span>
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-base">{{ $sub->name }}</h3>
                    @if($sub->status !== 'cancelled' && ($sub->renewal_stage > 0 || $sub->days_until_expiry <= $sub->reminder_days_before))
                        <a href="{{ route('admin.renewals') }}" class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold {{ $sub->renewal_stage === 2 ? 'text-indigo-700' : 'text-slate-500' }} hover:text-indigo-700">
                            <span class="inline-flex gap-0.5">@for($i = 1; $i <= 3; $i++)<i class="w-3 h-1 rounded-full {{ $i <= $sub->renewal_stage ? 'bg-indigo-600' : 'bg-slate-200' }}"></i>@endfor</span>
                            {{ \App\Models\Subscription::STAGE_LABELS[$sub->renewal_stage] ?? '' }}
                        </a>
                    @endif
                    @if($sub->domain_name)
                        <div class="flex items-center justify-between mt-1">
                            <a href="https://{{ $sub->domain_name }}" target="_blank" class="font-mono text-xs text-indigo-600 hover:text-indigo-800 font-bold hover:underline flex items-center gap-1" dir="ltr">
                                <span>{{ $sub->domain_name }}</span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>

                            <!-- Live Ping Check Button -->
                            <button wire:click="checkUptime({{ $sub->id }})" 
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-800 text-[10px] font-extrabold transition cursor-pointer" 
                                    title="پشکنینی بەردەوامی وێبسایت">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>پشکنین (Ping)</span>
                            </button>
                        </div>
                    @endif

                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span>کڕیار:</span>
                            <strong class="text-slate-900">{{ $sub->client->business_name ?? $sub->client->name }}</strong>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>سێرڤەری میوانداریکەر:</span>
                            <strong class="text-slate-700 font-mono">{{ $sub->server->name ?? 'دەرەکی / کۆمپانیا' }}</strong>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>دەستپێکردن:</span>
                            <span class="font-mono text-slate-700" dir="ltr">{{ $sub->start_date->format('Y-m-d') }}</span>
                        </div>

                        <div class="flex items-center justify-between text-slate-600">
                            <span>بەرواری بەسەرچوون:</span>
                            <span class="font-mono font-bold {{ $sub->days_until_expiry <= 30 ? 'text-rose-600' : 'text-slate-900' }}" dir="ltr">
                                {{ $sub->expiry_date->format('Y-m-d') }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                            <div>
                                <span class="text-slate-500">تێچوو: </span>
                                <span class="font-mono font-bold text-slate-600" dir="ltr">{{ \App\Models\Subscription::formatAmount((float) $sub->cost_price, $sub->currency) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">فرۆش: </span>
                                <span class="font-mono font-black text-emerald-600 text-sm" dir="ltr">{{ \App\Models\Subscription::formatAmount((float) $sub->selling_price, $sub->currency) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="togglePaid({{ $sub->id }})" wire:loading.attr="disabled" wire:target="togglePaid({{ $sub->id }})"
                        class="mt-3 w-full flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold cursor-pointer transition {{ $sub->is_paid ? 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <span>{{ $sub->is_paid ? '✓ پارەی داوە' : '✗ پارەی نەداوە' }}</span>
                    <span class="font-normal opacity-75">{{ $sub->is_paid ? 'گۆڕین بۆ نەداوە' : 'دەست لێبدە کاتێک دای' }}</span>
                </button>
                <!-- Card Actions -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5">
                        <button wire:click="edit({{ $sub->id }})" class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="دەستکاریکردن">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>

                        <button wire:click="confirmDelete({{ $sub->id }})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <!-- 1-Click Renew Button -->
                        <button wire:click="confirmRenew({{ $sub->id }})" 
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-extrabold rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white transition shadow-sm"
                                title="نوێکردنەوەی بەرواری بەسەرچوون بۆ ساڵێکی تر">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>نوێکردنەوە</span>
                        </button>

                        <a href="{{ route('admin.renewals', ['open' => $sub->id]) }}"
                           class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold rounded-lg bg-slate-100 hover:bg-slate-900 text-slate-700 hover:text-white transition"
                           title="پارەدان و قەرز">
                            💰 <span>پارەدان</span>
                        </a>

                        <!-- Auto Create Renewal Invoice Button -->
                        <button wire:click="createRenewalInvoice({{ $sub->id }})" 
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-bold rounded-lg bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white transition"
                                title="دروستکردنی وەسڵی نوێکردنەوە">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>وەسڵ</span>
                        </button>

                        <!-- WhatsApp Reminder Button -->
                        <a href="{{ $sub->whatsappUrl() ?? route('admin.clients') }}" 
                           target="_blank"
                           class="p-1.5 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white transition shadow-sm"
                           title="ناردنی نامەی واتسئاپ">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200">
                هیچ تۆمارێک نەدۆزرایەوە.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div>
        {{ $subscriptions->links() }}
    </div>

    <!-- WireUI Modal Card for Create/Edit (Clean & Dynamic Design) -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی خزمەتگوزاری' : 'تۆمارکردنی خزمەتگوزاری نوێ' }}" wire:model="showModal" max-width="2xl">
        <div class="space-y-5">
            
            <!-- 1. Service Type Selector (Header Bar) -->
            <div class="p-3.5 bg-slate-50/80 border border-slate-200/80 rounded-2xl">
                <x-native-select
                    label="جۆری خزمەتگوزاری دیاریبکە *"
                    wire:model.live="type"
                    :options="array_merge($type === 'bundle' ? [
                        ['name' => '📦 گورزەی دۆمەین + هۆستینگ (کۆن)', 'id' => 'bundle'],
                    ] : [], [
                        ['name' => '🌐 هۆستینگ (Hosting)', 'id' => 'hosting'],
                        ['name' => '🔗 تەنها دۆمەین (Domain Only)', 'id' => 'domain'],
                        ['name' => '✉️ ئیمەیڵی بزنس (Business Email)', 'id' => 'email'],
                        ['name' => '🖥️ سێرڤەری تایبەت (VPS)', 'id' => 'vps'],
                        ['name' => '🔑 مۆڵەتنامەی بەرنامە (License)', 'id' => 'license'],
                        ['name' => '🛠️ پشتگیری و چاکسازی (Maintenance)', 'id' => 'maintenance'],
                        ['name' => '📁 خزمەتگوزاری تر (Other)', 'id' => 'other'],
                    ])"
                    option-label="name"
                    option-value="id"
                />
                @if($type === 'domain' && ! $editingId)
                    <p class="mt-2 text-[11px] text-slate-500">هۆستینگ و دۆمەین بەرواری جیاوازیان هەیە، بۆیە هۆستینگەکە وەک تۆمارێکی جیا زیاد بکە.</p>
                @endif
            </div>

            <!-- 2. Client Selection -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">کڕیاری پەیوەندیدار *</label>
                    <button type="button" wire:click="openQuickClientModal" class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-lg">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>+ کڕیاری نوێ</span>
                    </button>
                </div>
                <x-select
                    placeholder="کڕیار هەڵبژێرە"
                    wire:model.live="client_id"
                    :options="$clients"
                    option-label="displayName"
                    option-value="id"
                />
                <x-native-select label="پڕۆژە (هی هەمان کڕیار)" wire:model="project_id" :disabled="! $client_id">
                    <option value="">— بێ پڕۆژە —</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->title }}</option>
                    @endforeach
                </x-native-select>
            </div>

            <!-- 3. Dynamic Service Details (Context-Aware) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                
                @if(in_array($type, ['bundle', 'domain', 'hosting', 'email']))
                    <!-- Domain Name -->
                    <div>
                        <x-input 
                            label="{{ $type === 'email' ? 'دۆمەینی ئیمەیڵەکان (@example.com)' : ($type === 'hosting' ? 'ناوی دۆمەین (ئەگەر هەیە)' : 'ناوی دۆمەین (Domain Name) *') }}" 
                            placeholder="example.com" 
                            wire:model.live.debounce.400ms="domain_name" 
                            dir="ltr" 
                        />
                    </div>
                @endif

                <!-- Service Name -->
                <div class="{{ !in_array($type, ['bundle', 'domain', 'hosting', 'email']) ? 'md:col-span-2' : '' }}">
                    <x-input 
                        label="ناوی ناسێنەری خزمەتگوزاری *" 
                        placeholder="نموونە: دۆمەین و هۆستینگی razilab.com" 
                        wire:model="name" 
                    />
                </div>

                @if(in_array($type, ['bundle', 'hosting']) || $cost_basis === 'shared_infrastructure')
                    <!-- Host Server (where it is hosted; linking never creates an expense) -->
                    <div>
                        <x-native-select
                            label="سێرڤەری میوانداریکەر (Host Server)"
                            wire:model="server_id"
                            :options="$servers"
                            option-label="name"
                            option-value="id"
                            placeholder="دەرەکی / لەسەر هیچ سێرڤەرێکی خۆت نییە"
                        />
                    </div>
                @endif

                @if(in_array($type, ['bundle', 'domain', 'email', 'vps', 'license']))
                    <!-- Provider -->
                    <div class="{{ in_array($type, ['domain', 'email', 'vps', 'license']) ? 'md:col-span-2' : '' }}">
                        <x-input 
                            label="{{ $type === 'email' ? 'دابینکەری ئیمەیڵ (Zoho, Google, cPanel)' : ($type === 'vps' ? 'دابینکەری سێرڤەر (Hetzner, Contabo)' : 'کۆمپانیای دابینکەر (Registrar / Provider)') }}" 
                            placeholder="{{ $type === 'email' ? 'Zoho Mail / Google Workspace' : ($type === 'vps' ? 'Hetzner Cloud' : 'Namecheap / GoDaddy / KRD Registry') }}" 
                            wire:model="provider" 
                        />
                    </div>
                @endif
            </div>

            <!-- 4. Pricing & Real-Time Profit Calculation (Simple & Clean) -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-700">دراو</span>
                    <div class="inline-flex bg-slate-100 p-1 rounded-xl" role="radiogroup" aria-label="دراو">
                        <button type="button" wire:click="$set('currency', 'USD')" role="radio" aria-checked="{{ $currency === 'USD' ? 'true' : 'false' }}"
                                class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $currency === 'USD' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500' }}">$ دۆلار</button>
                        <button type="button" wire:click="$set('currency', 'IQD')" role="radio" aria-checked="{{ $currency === 'IQD' ? 'true' : 'false' }}"
                                class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $currency === 'IQD' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500' }}">د.ع دینار</button>
                    </div>
                </div>
                <x-native-select label="تێچووی ئەم خزمەتگوزارییە چۆنە؟" wire:model.live="cost_basis">
                    @foreach($costBases as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-native-select>
                @if($cost_basis === 'shared_infrastructure')
                    <p class="text-[11px] text-slate-500">تێچووی VPS یەکجار لە «خەرجی دووبارە» هەژمار دەکرێت؛ لێرە نرخی کڕین نانووسرێت. تەنها VPSی میواندار هەڵبژێرە.</p>
                @endif
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if($cost_basis !== 'shared_infrastructure')
                    <div>
                        <x-input type="number" step="any" min="0" inputmode="decimal"
                            label="{{ $cost_basis === 'direct_purchase' ? 'نرخی کڕینی ڕاستەوخۆ *' : 'تێچوو (ئەگەر دەزانیت)' }}"
                            placeholder="{{ $currency === 'IQD' ? '25,000' : '20' }}"
                            prefix="{{ $currency === 'IQD' ? 'د.ع' : '$' }}"
                            wire:model.live="cost_price"
 />
                    </div>
                    @endif

                    <div>
                        <x-input type="number" step="any" min="0" inputmode="decimal"
                            label="کۆی نرخی فرۆشتن بە کڕیار *"
                            placeholder="{{ $currency === 'IQD' ? '100,000' : '130' }}"
                            prefix="{{ $currency === 'IQD' ? 'د.ع' : '$' }}"
                            wire:model.live="selling_price"
 />
                    </div>
                </div>

                <!-- Margin only when the direct cost is known; shared hosting has no per-client cost -->
                @if($cost_basis === 'direct_purchase')
                <div class="p-3 bg-emerald-50/90 border border-emerald-200/80 rounded-xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-xs font-extrabold text-emerald-900">پەراوێزی ڕاستەوخۆ:</span>
                        <span class="text-[11px] text-emerald-700 font-mono" dir="ltr">({{ \App\Support\Money::format((float) $selling_price, $currency) }} فرۆشتن - {{ \App\Support\Money::format((float) $cost_price, $currency) }} تێچوو)</span>
                    </div>
                    <span class="text-sm font-black text-emerald-700 font-mono" dir="ltr">
                        +{{ \App\Support\Money::format(max(0, (float) $selling_price - (float) $cost_price), $currency) }}
                    </span>
                </div>
                @endif
            </div>

            <!-- 5. Billing Cycle & Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-slate-100">
                <div>
                    <x-native-select
                        label="ماوەی خزمەتگوزاری (بۆ چەند کڕاوە) *"
                        wire:model.live="billing_cycle"
                        :options="[
                            ['name' => 'ساڵانە (Annual - ١ ساڵ)', 'id' => 'annual'],
                            ['name' => 'دوو ساڵە (Biennial - ٢ ساڵ)', 'id' => 'biennial'],
                            ['name' => '٦ مانگە (Semi Annual)', 'id' => 'semi_annual'],
                            ['name' => 'سێ مانگە (Quarterly)', 'id' => 'quarterly'],
                            ['name' => 'مانگانە (Monthly)', 'id' => 'monthly'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                <div class="md:col-span-2 flex items-center justify-between gap-3 rounded-2xl border p-3 {{ $is_paid ? 'bg-emerald-50/60 border-emerald-100' : 'bg-rose-50/70 border-rose-200' }}">
                    <div>
                        <div class="text-sm font-bold text-slate-800">کڕیار پارەی داوە؟</div>
                        @unless($is_paid)
                            <p class="text-[11px] text-rose-700 mt-0.5">دەچێتە لیستی «پارەی نەداوە» تا دیاری دەکەیت کە دای.</p>
                        @endunless
                    </div>
                    <div class="inline-flex bg-white p-1 rounded-xl border border-slate-200 flex-shrink-0" role="radiogroup" aria-label="کڕیار پارەی داوە؟">
                        <button type="button" wire:click="$set('is_paid', true)" role="radio" aria-checked="{{ $is_paid ? 'true' : 'false' }}"
                                class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $is_paid ? 'bg-emerald-600 text-white' : 'text-slate-500' }}">بەڵێ</button>
                        <button type="button" wire:click="$set('is_paid', false)" role="radio" aria-checked="{{ $is_paid ? 'false' : 'true' }}"
                                class="px-4 py-1.5 text-sm rounded-lg font-bold cursor-pointer {{ $is_paid ? 'text-slate-500' : 'bg-rose-600 text-white' }}">نەخێر</button>
                    </div>
                </div>
                <div>
                    <x-native-select
                        label="دۆخ *"
                        wire:model="status"
                        :options="[
                            ['name' => 'چالاک (Active)', 'id' => 'active'],
                            ['name' => 'بەسەرچوو (Expired)', 'id' => 'expired'],
                            ['name' => 'مۆڵەت (Grace Period)', 'id' => 'grace_period'],
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
                        wire:model.live="start_date"
                        display-format="YYYY-MM-DD"
                    />
                </div>

                <div>
                    <x-datetime-picker
                        label="بەرواری بەسەرچوون (خۆکار هەژمار دەکرێت) *"
                        placeholder="YYYY-MM-DD"
                        without-time="true"
                        wire:model="expiry_date"
                        display-format="YYYY-MM-DD"
                    />
                </div>

                <div class="md:col-span-2">
                    <x-textarea label="تێبینییەکان" placeholder="تێبینی لەسەر ڕێکخستنی DNS یان پارەدان..." wire:model="notes" />
                </div>
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردن' }}" wire:click="save" spinner="save" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

    <!-- WireUI Quick Client Modal -->
    <x-modal-card title="زیادکردنی کڕیاری نوێ بە خێرایی" wire:model="showQuickClientModal" max-width="md">
        <div class="space-y-4">
            <div>
                <x-input label="ناوی کەسی بەرپرسیار *" placeholder="کاک / د. ..." wire:model="quick_client_name" />
            </div>
            <div>
                <x-input label="ناوی کۆمپانیا / تاقیگە / دەرمانخانە" placeholder="کۆمپانیای ..." wire:model="quick_client_business_name" />
            </div>
            <div>
                <x-phone
                    label="ژمارەی مۆبایل *"
                    placeholder="0750-123-4567"
                    wire:model="quick_client_phone"
                    dir="ltr"
                    mask="####-###-####"
                />
            </div>
            <div>
                <x-native-select
                    label="شار *"
                    wire:model="quick_client_city"
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
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="تۆمارکردن و هەڵبژاردن" wire:click="saveQuickClient" spinner="saveQuickClient" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
