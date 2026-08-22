<div class="w-full">
    <!-- Card Container -->
    <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-indigo-950/50 space-y-6">
        
        <!-- Header / Brand -->
        <div class="text-center space-y-3">
            <div class="inline-flex p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 shadow-inner">
                <img src="{{ asset('images/logo.png') }}" alt="iCode Group" class="h-12 w-auto object-contain">
            </div>
            <div>
                <h2 class="text-2xl font-black tracking-tight text-white flex items-center justify-center gap-2">
                    <span>چوونەژوورەوە بۆ</span>
                    <span class="text-indigo-400">iCode Hub</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1 font-medium">سیستەمی بەڕێوەبردنی گشتی و ژێرخانی بزنس</p>
            </div>
        </div>

        <!-- Validation Errors Alert -->
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold space-y-1">
                <div class="flex items-center gap-2 font-bold text-rose-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>هەڵە لە چوونەژوورەوەدا ڕوویدا:</span>
                </div>
                <ul class="list-disc list-inside pr-2 text-rose-200">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form -->
        <form wire:submit="login" class="space-y-4">
            
            <!-- Email Field -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold text-slate-300">ئیمەیڵ (Email)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"></path></svg>
                    </div>
                    <input wire:model="email" type="email" id="email" required autofocus
                           dir="ltr"
                           placeholder="admin@icode.com"
                           class="w-full pr-11 pl-4 py-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-white placeholder-slate-500 text-sm font-medium focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                </div>
            </div>

            <!-- Password Field -->
            <div class="space-y-1.5" x-data="{ showPass: false }">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs font-bold text-slate-300">وشەی نهێنی (Password)</label>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input wire:model="password" :type="showPass ? 'text' : 'password'" id="password" required
                           dir="ltr"
                           placeholder="••••••••"
                           class="w-full pr-11 pl-11 py-3 rounded-2xl bg-slate-950/60 border border-slate-800 text-white placeholder-slate-500 text-sm font-medium focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 hover:text-slate-300 focus:outline-none">
                        <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded-lg bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500/20 focus:ring-offset-0 transition">
                    <span class="text-xs text-slate-400 font-medium">لەبیرم مەکە (Remember me)</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white text-sm font-bold shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/50 active:scale-[0.99] transition duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                <span wire:loading.remove>چوونەژوورەوە</span>
                <span wire:loading.inline-flex class="items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                    <span>خەریکی پەسەندکردنە...</span>
                </span>
            </button>
        </form>

        <!-- Back to Website -->
        <div class="pt-2 text-center border-t border-slate-800/80">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-200 transition font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                <span>گەڕانەوە بۆ ماڵپەڕی سەرەکی پۆرتفۆلیۆ</span>
            </a>
        </div>

    </div>
</div>
