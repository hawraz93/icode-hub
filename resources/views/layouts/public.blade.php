<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ku', 'ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- Primary Meta Tags -->
    <title>{{ $title ?? 'I‑CODE Group — Modern Software Studio & Enterprise Systems' }}</title>
    <meta name="title" content="I‑CODE Group | Modern Software Studio for Universities, Healthcare & Retail">
    <meta name="description" content="I‑CODE Group builds modern, reliable software for universities, healthcare, retail, agriculture, and finance across Kurdistan & Iraq.">
    <meta name="keywords" content="I-CODE, I-CODE Group, software studio, university software, medical lab system, pharmacy pos, erbil software, kurdistan developers, hawraz khaled">
    <meta name="author" content="I-CODE Group - Hawraz Khaled">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- GEO / Geographic Targeting Meta Tags for Kurdistan & Iraq -->
    <meta name="geo.region" content="IQ-KR">
    <meta name="geo.placename" content="Erbil, Sulaymaniyah, Kirkuk, Kurdistan Region, Iraq">
    <meta name="geo.position" content="36.1901;44.0091">
    <meta name="ICBM" content="36.1901, 44.0091">
    <meta name="language" content="English, Kurdish, Arabic">

    <!-- Open Graph / Facebook / WhatsApp / Viber -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="I‑CODE Group — Modern Software Studio">
    <meta property="og:description" content="Modern, reliable software for universities, healthcare, retail, finance, and cloud server hosting.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:site_name" content="I-CODE Group">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : (app()->getLocale() === 'ar' ? 'ar_IQ' : 'ckb_IQ') }}">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="I‑CODE Group — Modern Software Studio">
    <meta name="twitter:description" content="Modern software for universities, healthcare, retail and finance.">
    <meta name="twitter:image" content="{{ asset('images/logo.png') }}">

    <!-- Schema.org JSON-LD Structured Data for Google Rich Snippets -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "SoftwareApplication",
      "name": "I-CODE Group",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "Web, Windows, Android, iOS",
      "description": "Modern software studio for universities, healthcare, retail, and finance in Kurdistan Region & Iraq",
      "author": {
        "@@type": "Person",
        "name": "Hawraz Khaled",
        "jobTitle": "Lead Software Architect & Founder"
      },
      "provider": {
        "@@type": "Organization",
        "name": "I-CODE Group",
        "url": "https://icodegroup.net",
        "logo": "{{ asset('images/logo.png') }}",
        "address": {
          "@@type": "PostalAddress",
          "addressLocality": "Erbil",
          "addressRegion": "Kurdistan Region",
          "addressCountry": "Iraq"
        },
        "contactPoint": {
          "@@type": "ContactPoint",
          "telephone": "+9647700941717",
          "contactType": "Customer Service",
          "availableLanguage": ["English", "Kurdish", "Arabic"]
        }
      }
    }
    </script>
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- WireUI Scripts -->
    <wireui:scripts />
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @livewireStyles
</head>
<body class="bg-slate-950 text-slate-100 antialiased font-sans selection:bg-indigo-500 selection:text-white min-h-screen flex flex-col">

    <x-dialog />
    <x-notifications />

    <!-- Top Navigation Bar -->
    <nav class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Brand Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
                    <div class="relative flex items-center justify-center p-1 rounded-2xl bg-slate-900/80 border border-slate-800 group-hover:border-indigo-500/50 transition">
                        <img src="{{ asset('images/logo.png') }}" alt="I-CODE Group" class="h-10 w-auto object-contain">
                    </div>
                    <div>
                        <div class="font-black text-xl tracking-tight text-white flex items-center gap-1.5">
                            <span>I‑CODE</span>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-indigo-400 to-pink-500 font-extrabold">Group</span>
                        </div>
                        <p class="text-[11px] text-slate-400 font-medium">Software Studio & Enterprise Systems</p>
                    </div>
                </a>

                <!-- Nav Links -->
                <div class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-300">
                    <a href="#solutions" class="hover:text-cyan-400 transition">{{ __('site.nav.suites') }}</a>
                    <a href="#capabilities" class="hover:text-indigo-400 transition">{{ __('site.nav.capabilities') }}</a>
                    <a href="#services" class="hover:text-pink-400 transition">{{ __('site.nav.services') }}</a>
                    <a href="#clients" class="hover:text-amber-400 transition">{{ __('site.nav.clients') }}</a>
                </div>

                <!-- Language Switcher & Action Buttons -->
                <div class="flex items-center gap-3">
                    
                    <!-- Language Switcher (EN | کوردی | العربية) -->
                    <div class="flex items-center gap-1 bg-slate-900/90 border border-slate-800 p-1 rounded-xl text-xs font-bold shadow-xs">
                        <a href="{{ route('set.locale', 'en') }}" class="px-2 py-1 rounded-lg transition {{ app()->getLocale() === 'en' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">EN</a>
                        <a href="{{ route('set.locale', 'ku') }}" class="px-2 py-1 rounded-lg transition {{ app()->getLocale() === 'ku' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">کوردی</a>
                        <a href="{{ route('set.locale', 'ar') }}" class="px-2 py-1 rounded-lg transition {{ app()->getLocale() === 'ar' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-400 hover:text-white' }}">العربية</a>
                    </div>

                    <a href="{{ route('client.portal') }}" 
                       class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 text-xs font-bold rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition shadow-sm">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>{{ __('site.nav.portal') }}</span>
                    </a>

                    <a href="{{ route('admin.dashboard') }}" 
                       class="inline-flex items-center gap-1.5 sm:gap-2 px-3.5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white shadow-lg shadow-indigo-500/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="hidden sm:inline">{{ __('site.nav.admin') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Slot Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 py-12 text-slate-400 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="I-CODE Group" class="h-8 w-auto">
                        <span class="font-extrabold text-lg text-white">I‑CODE Group</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed max-w-md">
                        {{ __('site.hero.description') }}
                    </p>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-3">{{ __('site.footer.suites_list') }}</h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="#solutions" class="hover:text-white transition">University & Education Suite</a></li>
                        <li><a href="#solutions" class="hover:text-white transition">Healthcare (MedLab & Dental)</a></li>
                        <li><a href="#solutions" class="hover:text-white transition">Commerce & POS Enterprise</a></li>
                        <li><a href="#solutions" class="hover:text-white transition">Agriculture (HappyEgg)</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-3">{{ __('site.nav.contact') }}</h4>
                    <ul class="space-y-2.5 text-xs">
                        <li class="flex items-center gap-2">
                            <span class="text-slate-500">{{ __('site.contact.whatsapp') }}:</span>
                            <a href="https://wa.me/9647700941717" target="_blank" dir="ltr" class="text-emerald-400 font-mono font-bold hover:underline">+964 770 094 1717</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-slate-500">{{ __('site.contact.telegram') }}:</span>
                            <a href="https://t.me/icodegroup" target="_blank" dir="ltr" class="text-cyan-400 font-mono font-bold hover:underline">@icodegroup</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-slate-500">{{ __('site.contact.email') }}:</span>
                            <a href="mailto:info@icodegroup.net" dir="ltr" class="text-slate-300 font-mono hover:text-white">info@icodegroup.net</a>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="text-slate-300 font-bold">{{ __('site.contact.offices') }}</span>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="pt-8 border-t border-slate-900/80 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>© {{ date('Y') }} I‑CODE Group. {{ __('site.footer.rights') }}</p>
                <div class="flex items-center gap-4">
                    <span class="text-indigo-400 font-medium">Built with Laravel 12 & Livewire 3</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Floating WhatsApp Direct Contact Button -->
    <a href="https://wa.me/9647700941717?text={{ urlencode('Hello I-CODE team, I would like to inquire about your software solutions.') }}" 
       target="_blank" 
       rel="noopener noreferrer"
       class="fixed bottom-6 end-6 z-50 flex items-center gap-2.5 px-4 py-3 bg-emerald-500 hover:bg-emerald-400 text-white rounded-full shadow-2xl shadow-emerald-500/40 hover:scale-105 transition-all duration-300 group"
       title="Direct WhatsApp (+9647700941717)">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
        </svg>
        <span class="text-xs font-black hidden sm:inline-block">WhatsApp</span>
    </a>

    @livewireScripts
</body>
</html>
