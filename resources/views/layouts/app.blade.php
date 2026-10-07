<!DOCTYPE html>
<html lang="ckb" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="iCode Hub">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>{{ $title ?? 'داشبۆردی بەڕێوەبردن' }} | iCode Group Hub</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- WireUI Scripts -->
    <wireui:scripts />
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @livewireStyles
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased font-sans flex flex-col min-h-screen">
    
    <!-- WireUI Notification & Dialog Mounts -->
    <x-dialog />
    <x-notifications />

    <div class="flex h-screen overflow-hidden bg-slate-100" x-data="{ sidebarOpen: false }">
        
        <!-- Sidebar Backdrop for Mobile -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden">
        </div>

        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
               class="fixed inset-y-0 right-0 z-50 flex flex-col w-72 bg-white border-l border-slate-200 shadow-xl lg:shadow-none lg:static lg:inset-auto lg:translate-x-0 transition-transform duration-300 ease-in-out">
            
            <!-- Logo Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="iCode Group" class="h-10 w-auto object-contain">
                    <div>
                        <div class="font-extrabold text-lg text-slate-900 tracking-tight flex items-center gap-1.5">
                            <span>iCode</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold border border-indigo-200">Hub</span>
                        </div>
                        <p class="text-xs text-slate-500 font-medium">Business Operating System</p>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-1.5">
                
                <div class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">سەرەکی و چاودێری</div>
                
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>داشبۆردی سەرەکی</span>
                </a>

                @php
                    $expiringCount = \App\Models\Subscription::openRenewals(30)->count();
                @endphp
                <a href="{{ route('admin.renewals') }}" 
                   class="flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.renewals') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"></circle><circle cx="12" cy="12" r="4.5" stroke-width="2"></circle><path stroke-linecap="round" stroke-width="2" d="M12 12l6-6"></path></svg>
                        <span>ڕاداری نوێکردنەوە</span>
                    </div>
                    @if($expiringCount > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ request()->routeIs('admin.renewals') ? 'bg-white text-indigo-700' : 'bg-rose-500 text-white' }}">{{ $expiringCount }}</span>
                    @endif
                </a>

                <div class="pt-4 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">بەڕێوەبردنی کارەکان</div>

                <a href="{{ route('admin.subscriptions') }}" 
                   class="flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.subscriptions') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        <span>هۆستینگ و دۆمەین</span>
                    </div>
                </a>

                <a href="{{ route('admin.expenses') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.expenses') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>خەرجییەکان (Expenses)</span>
                </a>

                <a href="{{ route('admin.clients') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.clients') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>کڕیاران (Clients)</span>
                </a>

                <a href="{{ route('admin.invoices') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.invoices') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>وەسڵەکان (Invoices)</span>
                </a>

                <a href="{{ route('admin.contracts') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.contracts') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>عەقد و ڕێککەوتننامە</span>
                </a>

                <div class="pt-4 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">پۆرتفۆلیۆ و بەرهەمەکان</div>

                <a href="{{ route('admin.projects') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.projects') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>بەڕێوەبردنی پڕۆژەکان</span>
                </a>

                <div class="pt-4 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">سیستەم و ئەکاونت</div>

                <a href="{{ route('admin.profile') }}" 
                   class="flex items-center gap-3 px-3.5 py-2.5 text-sm font-semibold rounded-xl transition-colors {{ request()->routeIs('admin.profile') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>پرۆفایل و بەکارهێنەران</span>
                </a>

                <a href="{{ route('home') }}" target="_blank" 
                   class="flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-xl transition-colors">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        <span>بینینی ماڵپەڕی گشتی</span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-bold">Public</span>
                </a>
            </div>

            <!-- User Footer Profile & Logout -->
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <a href="{{ route('admin.profile') }}" class="flex items-center gap-3 min-w-0 flex-1 hover:opacity-80 transition group" title="کردنەوەی پرۆفایل">
                    <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name=Admin&background=4f46e5&color=fff' }}" alt="{{ auth()->user()->name ?? 'User' }}" class="w-9 h-9 flex-shrink-0 rounded-full object-cover border border-slate-200 shadow-xs">
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate group-hover:text-indigo-600 transition">{{ auth()->user()->name ?? 'Hawraz Khaled' }}</div>
                        <div class="text-[11px] text-slate-500 truncate font-mono" dir="ltr">{{ auth()->user()->email ?? 'admin@icode.com' }}</div>
                    </div>
                </a>

                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="دەرچوون (Logout)" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            
            <!-- Top Navbar (Mobile Optimized & Clean) -->
            <header class="flex items-center justify-between px-3 sm:px-6 py-2.5 sm:py-4 pt-[calc(0.625rem+env(safe-area-inset-top))] sm:pt-4 bg-white border-b border-slate-200 gap-2">
                <div class="flex items-center gap-2 sm:gap-4 min-w-0 flex-1">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm sm:text-base lg:text-lg font-bold text-slate-900 truncate leading-tight">{{ $header ?? 'داشبۆردی سەرەکی' }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                    @php
                        $expiringSubs = \App\Models\Subscription::with('client')
                            ->openRenewals(30)
                            ->orderBy('expiry_date')
                            ->take(5)
                            ->get();

                        $renewingServers = \App\Models\Server::where('status', 'active')
                            ->where('renewal_date', '<=', now()->addDays(15))
                            ->orderBy('renewal_date')
                            ->take(3)
                            ->get();

                        $overdueInvoices = \App\Models\Invoice::with('client')
                            ->whereIn('status', ['sent', 'partial', 'overdue'])
                            ->where('due_date', '<', now())
                            ->orderBy('due_date')
                            ->take(3)
                            ->get();

                        $totalAlertsCount = $expiringSubs->count() + $renewingServers->count() + $overdueInvoices->count();
                    @endphp

                    <!-- Modern Interactive Notification Dropdown -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen" 
                                class="relative p-2 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50/70 rounded-xl transition-all duration-200 focus:outline-none"
                                title="ئاگادارییەکان">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            @if($totalAlertsCount > 0)
                                <span class="absolute top-0.5 right-0.5 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white ring-2 ring-white">
                                    {{ $totalAlertsCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Notification Dropdown Panel -->
                        <div x-show="notifOpen"
                             @click.outside="notifOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                             class="fixed sm:absolute inset-x-3 sm:inset-x-auto top-14 sm:top-auto sm:left-0 sm:mt-3 w-auto sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
                             style="display: none;">
                            
                            <!-- Header -->
                            <div class="p-3.5 sm:p-4 bg-slate-900 text-white flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-rose-400 animate-ping"></div>
                                    <h3 class="font-extrabold text-sm">ناوەندی ئاگادارییەکان</h3>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-white/20 text-white">
                                    {{ $totalAlertsCount }} ئاگاداری
                                </span>
                            </div>

                            <!-- Content List -->
                            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 text-xs">
                                @if($totalAlertsCount === 0)
                                    <div class="p-8 text-center text-slate-400">
                                        <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <p class="font-bold text-slate-700">هیچ ئاگادارییەکی بەپەلە نییە!</p>
                                        <p class="text-[11px] text-slate-400 mt-1">هەموو بەشداریکردن و سێرڤەرەکان لە دۆخی ئاساییدان.</p>
                                    </div>
                                @else
                                    <!-- Expiring / Expired Subscriptions -->
                                    @foreach($expiringSubs as $sub)
                                        <div class="p-3.5 hover:bg-slate-50 transition flex items-start justify-between gap-3">
                                            <div class="space-y-1 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $sub->type === 'domain' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                                        {{ $sub->type_label }}
                                                    </span>
                                                    <span class="font-mono text-[11px] font-extrabold {{ $sub->days_until_expiry < 0 ? 'text-rose-600' : ($sub->days_until_expiry <= 7 ? 'text-rose-600' : 'text-amber-600') }}">
                                                        {{ $sub->expiry_status_text }}
                                                    </span>
                                                </div>
                                                <h4 class="font-bold text-slate-900 text-xs">{{ $sub->name }}</h4>
                                                <p class="text-slate-500 text-[11px]">کڕیار: {{ $sub->client->business_name ?? $sub->client->name }}</p>
                                            </div>
                                            <a href="{{ route('admin.renewals') }}" class="p-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-[11px] font-bold transition">
                                                بینین
                                            </a>
                                        </div>
                                    @endforeach

                                    <!-- Renewing Servers -->
                                    @foreach($renewingServers as $srv)
                                        <div class="p-3.5 hover:bg-slate-50 transition flex items-start justify-between gap-3 bg-rose-50/20">
                                            <div class="space-y-1 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">سێرڤەر</span>
                                                    <span class="font-mono text-[11px] font-extrabold text-rose-600">
                                                        {{ $srv->renewal_status_text }}
                                                    </span>
                                                </div>
                                                <h4 class="font-bold text-slate-900 text-xs">{{ $srv->name }}</h4>
                                                <p class="text-slate-500 text-[11px] font-mono" dir="ltr">{{ $srv->cost_label }} · {{ $srv->kind_label }}</p>
                                            </div>
                                            <a href="{{ route('admin.servers') }}" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-[11px] font-bold transition">
                                                نوێکردنەوە
                                            </a>
                                        </div>
                                    @endforeach

                                    <!-- Overdue Invoices -->
                                    @foreach($overdueInvoices as $inv)
                                        <div class="p-3.5 hover:bg-slate-50 transition flex items-start justify-between gap-3 bg-amber-50/20">
                                            <div class="space-y-1 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">وەسڵی دواکەوتوو</span>
                                                    <span class="font-mono text-[11px] font-extrabold text-amber-700" dir="ltr">{{ \App\Support\Money::format((float) $inv->remaining_balance, $inv->currency) }}</span>
                                                </div>
                                                <h4 class="font-bold text-slate-900 text-xs font-mono" dir="ltr">{{ $inv->invoice_number }}</h4>
                                                <p class="text-slate-500 text-[11px]">کڕیار: {{ $inv->client->business_name ?? $inv->client->name }}</p>
                                            </div>
                                            <a href="{{ route('admin.invoices') }}" class="p-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded-lg text-[11px] font-bold transition">
                                                وەسڵ
                                            </a>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <!-- Footer -->
                            <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                                <a href="{{ route('admin.dashboard') }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition">
                                    بینینی هەموو چاودێرییەکان لە داشبۆرد &larr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Public Portfolio Button -->
                    <a href="{{ route('home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                        <span>ماڵپەڕی گشتی</span>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>

                    <!-- Top Header Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" title="دەرچوون لە سیستەم" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-3 sm:p-6 lg:p-8 pb-[calc(7rem+env(safe-area-inset-bottom))] lg:pb-8">
                {{ $slot }}
            </main>

            <!-- Mobile Bottom App Bar (Optimized for Phone Touch UX) -->
            <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 pt-2 pb-[calc(0.5rem+env(safe-area-inset-bottom))] px-2 shadow-lg">
                <div class="flex items-center justify-around max-w-md mx-auto">
                    
                    <!-- Dashboard -->
                    <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="text-[10px]">داشبۆرد</span>
                    </a>

                    <!-- Renewal Radar -->
                    <a href="{{ route('admin.renewals') }}" class="relative flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition {{ request()->routeIs('admin.renewals') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"></circle><circle cx="12" cy="12" r="4.5" stroke-width="2"></circle><path stroke-linecap="round" stroke-width="2" d="M12 12l6-6"></path></svg>
                        <span class="text-[10px]">ڕادار</span>
                        @if($expiringCount > 0)
                            <span class="absolute top-0 right-2 w-2 h-2 bg-rose-500 rounded-full"></span>
                        @endif
                    </a>

                    <!-- Invoices -->
                    <a href="{{ route('admin.invoices') }}" class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition {{ request()->routeIs('admin.invoices') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-[10px]">وەسڵەکان</span>
                    </a>

                    <!-- Clients -->
                    <a href="{{ route('admin.clients') }}" class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl transition {{ request()->routeIs('admin.clients') ? 'text-indigo-600 font-extrabold' : 'text-slate-500 hover:text-slate-800' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="text-[10px]">کڕیاران</span>
                    </a>

                    <!-- More / Sidebar Trigger -->
                    <button @click="sidebarOpen = true" class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-slate-500 hover:text-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <span class="text-[10px]">زیاتر</span>
                    </button>

                </div>
            </nav>
        </div>

    </div>

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>
</body>
</html>
