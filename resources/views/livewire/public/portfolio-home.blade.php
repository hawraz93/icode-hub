<div class="space-y-24 pb-20">
    
    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-32">
        
        <!-- Glowing Ambient Lights -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-cyan-500/20 via-indigo-500/20 to-pink-500/20 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute top-10 right-10 w-72 h-72 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
            
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-slate-900/90 border border-slate-800 text-xs font-semibold text-slate-300 shadow-xl">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>{{ __('site.hero.badge') }}</span>
            </div>

            <!-- Main Heading -->
            <div class="max-w-4xl mx-auto space-y-4">
                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.15]">
                    {{ __('site.hero.title_start') }}
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-indigo-400 to-pink-500">
                        {{ __('site.hero.title_highlight') }}
                    </span>
                    {{ __('site.hero.title_end') }}
                </h1>
                <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    {{ __('site.hero.description') }}
                </p>
            </div>

            <!-- Hero Action Buttons -->
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <a href="#solutions" class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-cyan-500 hover:from-indigo-500 hover:to-cyan-400 text-white font-extrabold text-sm shadow-xl shadow-indigo-500/25 transition transform hover:-translate-y-0.5">
                    {{ __('site.hero.btn_work') }}
                </a>
                
                <button wire:click="openQuoteModal('general')" class="px-8 py-3.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white font-extrabold text-sm border border-slate-800 transition">
                    {{ __('site.hero.btn_quote') }}
                </button>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto pt-16 border-t border-slate-900">
                <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-indigo-400 font-mono" dir="ltr">
                        {{ $stats['projects_count'] }}+
                    </div>
                    <div class="text-xs text-slate-400 font-medium mt-1">{{ __('site.stats.projects') }}</div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-pink-400 font-mono" dir="ltr">
                        {{ $stats['clients_count'] }}+
                    </div>
                    <div class="text-xs text-slate-400 font-medium mt-1">{{ __('site.stats.clients') }}</div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-amber-400 font-mono" dir="ltr">
                        99.9%
                    </div>
                    <div class="text-xs text-slate-400 font-medium mt-1">{{ __('site.stats.uptime') }}</div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800/80">
                    <div class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-emerald-400 font-mono" dir="ltr">
                        24/7
                    </div>
                    <div class="text-xs text-slate-400 font-medium mt-1">{{ __('site.stats.support') }}</div>
                </div>
            </div>

        </div>
    </section>

    <!-- Solutions & Projects Explorer -->
    <section id="solutions" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-900 pb-8">
            <div>
                <div class="text-xs font-extrabold uppercase tracking-widest text-cyan-400 mb-2">{{ __('site.work.badge') }}</div>
                <h2 class="text-3xl sm:text-4xl font-black text-white">{{ __('site.work.title') }}</h2>
                <p class="text-sm text-slate-400 mt-2">{{ __('site.work.subtitle') }}</p>
            </div>

            <!-- Filter Buttons -->
            <div class="flex flex-wrap gap-2">
                @php
                    $categories = [
                        'all' => __('site.work.all'),
                        'pos' => __('site.work.pos'),
                        'medical' => __('site.work.medical'),
                        'education' => __('site.work.education'),
                        'finance' => __('site.work.finance'),
                        'commercial' => __('site.work.commercial'),
                    ];
                @endphp

                @foreach($categories as $key => $label)
                    <button wire:click="filterCategory('{{ $key }}')" 
                            class="px-4 py-2 text-xs font-bold rounded-xl transition {{ $selectedCategory === $key ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Projects Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($projects as $project)
                <div class="group relative rounded-3xl bg-slate-900/60 border border-slate-800/80 hover:border-indigo-500/50 p-6 flex flex-col justify-between transition duration-300 hover:shadow-2xl hover:shadow-indigo-500/10 backdrop-blur-sm">
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-slate-800 text-indigo-400 border border-slate-700">
                                {{ $project->category_label }}
                            </span>

                            @if($project->is_featured)
                                <span class="text-xs text-amber-400 font-bold flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span>Featured</span>
                                </span>
                            @endif
                        </div>

                        <h3 class="text-xl font-extrabold text-white group-hover:text-cyan-400 transition leading-snug">
                            {{ $project->title }}
                        </h3>

                        <p class="text-xs text-slate-400 leading-relaxed line-clamp-3">
                            {{ $project->summary }}
                        </p>

                        @if($project->features)
                            <ul class="space-y-1.5 pt-2 text-xs text-slate-300">
                                @foreach(array_slice($project->features, 0, 3) as $feat)
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="line-clamp-1">{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($project->tech_stack)
                            <div class="flex flex-wrap gap-1.5 pt-3">
                                @foreach($project->tech_stack as $tech)
                                    <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-slate-800 text-slate-400 rounded-md">{{ $tech }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-6 mt-6 border-t border-slate-800/80 flex items-center justify-between gap-3">
                        <button wire:click="viewProject({{ $project->id }})" class="text-xs font-bold text-slate-300 hover:text-white flex items-center gap-1.5 transition group/btn">
                            <span>{{ __('site.work.details') }}</span>
                            <svg class="w-4 h-4 text-indigo-400 rtl:rotate-0 ltr:rotate-180 transition group-hover/btn:translate-x-0.5 rtl:group-hover/btn:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>

                        <button wire:click="openQuoteModal('{{ $project->category }}')" class="px-3.5 py-1.5 rounded-xl bg-indigo-600/80 hover:bg-indigo-600 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition">
                            {{ __('site.work.request_demo') }}
                        </button>
                    </div>

                </div>
            @endforeach
        </div>

    </section>

    <!-- Capabilities Section -->
    <section id="capabilities" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-2xl mx-auto space-y-3">
            <div class="text-xs font-extrabold uppercase tracking-widest text-indigo-400">{{ __('site.cap.badge') }}</div>
            <h2 class="text-3xl sm:text-4xl font-black text-white">{{ __('site.cap.title') }}</h2>
            <p class="text-sm text-slate-400">{{ __('site.cap.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.product_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.product_desc') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.webapps_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.webapps_desc') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.deploy_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.deploy_desc') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.multi_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.multi_desc') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.docs_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.docs_desc') }}</p>
            </div>
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-2">
                <h3 class="font-bold text-white text-base">{{ __('site.cap.support_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.cap.support_desc') }}</p>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <div class="text-center max-w-2xl mx-auto space-y-3">
            <div class="text-xs font-extrabold uppercase tracking-widest text-pink-400">{{ __('site.srv.badge') }}</div>
            <h2 class="text-3xl sm:text-4xl font-black text-white">{{ __('site.srv.title') }}</h2>
            <p class="text-sm text-slate-400">{{ __('site.srv.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold">📱</div>
                <h3 class="text-base font-bold text-white">{{ __('site.srv.mobile_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.srv.mobile_desc') }}</p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold">🌐</div>
                <h3 class="text-base font-bold text-white">{{ __('site.srv.web_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.srv.web_desc') }}</p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-pink-500/10 text-pink-400 flex items-center justify-center font-bold">💻</div>
                <h3 class="text-base font-bold text-white">{{ __('site.srv.desktop_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.srv.desktop_desc') }}</p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-slate-700 transition space-y-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold">☁️</div>
                <h3 class="text-base font-bold text-white">{{ __('site.srv.hosting_title') }}</h3>
                <p class="text-xs text-slate-400 leading-relaxed">{{ __('site.srv.hosting_desc') }}</p>
            </div>
        </div>
    </section>

    <!-- Clients Section -->
    <section id="clients" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        <div class="text-center max-w-2xl mx-auto space-y-2">
            <div class="text-xs font-extrabold uppercase tracking-widest text-amber-400">{{ __('site.clients.badge') }}</div>
            <h2 class="text-2xl sm:text-3xl font-black text-white">{{ __('site.clients.title') }}</h2>
            <p class="text-xs sm:text-sm text-slate-400">{{ __('site.clients.subtitle') }}</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 font-bold text-xs flex items-center justify-center">QU</span>
                <span class="text-xs font-bold text-slate-300">Qalam University</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 font-bold text-xs flex items-center justify-center">BAX</span>
                <span class="text-xs font-bold text-slate-300">Baxshin Medical</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 font-bold text-xs flex items-center justify-center">HE</span>
                <span class="text-xs font-bold text-slate-300">HappyEgg</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 font-bold text-xs flex items-center justify-center">ML</span>
                <span class="text-xs font-bold text-slate-300">MedLab Suite</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-pink-500/10 text-pink-400 font-bold text-xs flex items-center justify-center">POS</span>
                <span class="text-xs font-bold text-slate-300">POS Enterprise</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800 text-center flex flex-col items-center justify-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-violet-500/10 text-violet-400 font-bold text-xs flex items-center justify-center">IC</span>
                <span class="text-xs font-bold text-slate-300">I‑CODE Cloud</span>
            </div>
        </div>
    </section>

    <!-- Client Portal Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="relative rounded-3xl bg-gradient-to-r from-indigo-950 via-slate-900 to-indigo-950 border border-indigo-800/40 p-8 sm:p-12 overflow-hidden shadow-2xl">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 relative z-10">
                <div class="space-y-3 max-w-xl">
                    <div class="text-xs font-extrabold uppercase tracking-widest text-cyan-400">{{ __('site.portal_banner.badge') }}</div>
                    <h3 class="text-2xl sm:text-3xl font-black text-white">{{ __('site.portal_banner.title') }}</h3>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        {{ __('site.portal_banner.desc') }}
                    </p>
                </div>

                <a href="{{ route('client.portal') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl bg-white text-slate-900 hover:bg-slate-100 font-extrabold text-sm shadow-xl transition">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    <span>{{ __('site.portal_banner.btn') }}</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Project Details & Case Study Modal -->
    <x-modal wire:model="showProjectModal" max-width="3xl">
        @if($selectedProject)
            <x-card>
                <div class="space-y-6 text-slate-900">
                    <div class="flex items-center justify-between border-b pb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700">
                                {{ $selectedProject->category_label }}
                            </span>
                            <h3 class="text-2xl font-black text-slate-900 mt-2">{{ $selectedProject->title }}</h3>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="font-bold text-slate-800 text-sm">{{ __('site.work.details') }}:</h4>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ $selectedProject->description ?: $selectedProject->summary }}</p>
                    </div>

                    @if($selectedProject->case_study)
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                            <h4 class="font-extrabold text-indigo-900 text-xs uppercase tracking-wider">Case Study & Outcome:</h4>
                            <p class="text-xs text-slate-700 leading-relaxed">{{ $selectedProject->case_study }}</p>
                        </div>
                    @endif

                    @if($selectedProject->features)
                        <div class="space-y-2">
                            <h4 class="font-bold text-slate-800 text-sm">Key Features:</h4>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-700">
                                @foreach($selectedProject->features as $f)
                                    <li class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span>{{ $f }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($selectedProject->tech_stack)
                        <div class="pt-4 border-t border-slate-100 flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-slate-500 font-bold">Tech Stack:</span>
                            @foreach($selectedProject->tech_stack as $t)
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-[11px] font-bold text-slate-700">{{ $t }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <x-slot name="footer">
                    <div class="flex items-center justify-between w-full">
                        @if($selectedProject->github_url)
                            <a href="{{ $selectedProject->github_url }}" target="_blank" class="text-xs font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                                <span>GitHub Repository</span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        <x-button flat label="{{ __('site.quote.cancel') }}" x-on:click="close" />
                    </div>
                </x-slot>
            </x-card>
        @endif
    </x-modal>

    <!-- Request Quote / Demo Modal -->
    <x-modal wire:model="showQuoteModal" max-width="lg">
        <x-card title="{{ __('site.quote.modal_title') }}">
            <div class="space-y-4">
                <x-input label="{{ __('site.quote.name') }}" placeholder="Full Name..." wire:model="req_name" />
                <x-input label="{{ __('site.quote.business') }}" placeholder="Company / Clinic..." wire:model="req_business" />
                
                <x-phone
                    label="{{ __('site.quote.phone') }}"
                    placeholder="07##-###-####"
                    wire:model="req_phone"
                    :mask="['07##-###-####', '####-###-####']"
                    dir="ltr"
                />

                <x-native-select
                    label="{{ __('site.quote.service') }}"
                    wire:model="req_service"
                    :options="[
                        ['name' => 'Pharmacy POS & Inventory', 'id' => 'pos'],
                        ['name' => 'Medical Laboratory System (LIS)', 'id' => 'medical'],
                        ['name' => 'University & Student Management', 'id' => 'education'],
                        ['name' => 'Finance & Accounting', 'id' => 'finance'],
                        ['name' => 'FMCG & Warehouse Management', 'id' => 'commercial'],
                        ['name' => 'Custom Cloud Server & Architecture', 'id' => 'general'],
                    ]"
                    option-label="name"
                    option-value="id"
                />

                <x-textarea label="{{ __('site.quote.message') }}" placeholder="Tell us more about your requirements..." wire:model="req_message" />
            </div>

            <x-slot name="footer">
                <div class="flex items-center justify-end gap-3">
                    <x-button flat label="{{ __('site.quote.cancel') }}" x-on:click="close" />
                    <x-button primary label="{{ __('site.quote.submit') }}" wire:click="submitQuote" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>

</div>
