<div class="max-w-3xl mx-auto space-y-4">

    {{-- 1. Paste box --}}
    <section class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 space-y-3">
        <div>
            <h2 class="font-display font-extrabold text-slate-900 text-base">دۆمەینەکان بنووسە یان paste بکە</h2>
            <p class="text-xs text-slate-500 mt-0.5">هەر دێڕێک یەک خزمەتگوزاری. بەروار و دابینکەر خۆکار لە تۆمارگە دێن.</p>
        </div>
        <textarea wire:model="input" id="quick-input" rows="4" dir="ltr"
                  class="w-full rounded-2xl border-slate-200 bg-slate-50 font-mono text-sm text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 placeholder:text-slate-400"
                  placeholder="epochsp.com  $100  1-4-2027&#10;ghsooncompany.com.iq  100k&#10;norduz.net 100 hosting"></textarea>
        @error('input') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="read" wire:loading.attr="disabled" wire:target="read"
                    class="inline-flex items-center gap-2 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-bold px-5 py-3 cursor-pointer transition">
                <svg wire:loading.remove wire:target="read" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z"/></svg>
                <svg wire:loading wire:target="read" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span wire:loading.remove wire:target="read">بخوێنەوە</span>
                <span wire:loading wire:target="read">پشکنینی تۆمارگە…</span>
            </button>
            <span class="text-[11px] text-slate-400">نموونە: <span class="font-mono" dir="ltr">$100</span> · <span class="font-mono" dir="ltr">100k</span> (دینار) · <span class="font-mono">hosting</span> · <span class="font-mono">email</span></span>
        </div>
    </section>

    {{-- 2. Review rows --}}
    @if($drafts)
        <div class="flex items-center justify-between">
            <h2 class="font-display font-extrabold text-[13px] text-slate-900">پێش تۆمارکردن سەیری بکە <span class="font-mono text-slate-400 font-medium">{{ count($drafts) }}</span></h2>
            <button type="button" wire:click="$toggle('showNewClient')" class="text-xs font-bold text-indigo-600 hover:bg-indigo-50 rounded-xl px-3 py-1.5 cursor-pointer">+ کڕیاری نوێ</button>
        </div>

        @if($showNewClient)
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-3 grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2">
                <input wire:model="newClientName" id="new-client-name" type="text" placeholder="ناوی کڕیار / کۆمپانیا" class="rounded-xl border-slate-200 text-sm">
                <input wire:model="newClientPhone" id="new-client-phone" type="tel" dir="ltr" placeholder="0750 000 0000" class="rounded-xl border-slate-200 text-sm font-mono">
                <button type="button" wire:click="createClient" class="rounded-xl bg-indigo-600 text-white text-sm font-bold px-4 py-2 cursor-pointer">زیادکردن</button>
                @error('newClientName') <p class="sm:col-span-3 text-xs text-rose-600">{{ $message }}</p> @enderror
                <p class="sm:col-span-3 text-[11px] text-indigo-800">دەدرێتە ئەو دێڕانەی هێشتا کڕیاریان نییە.</p>
            </div>
        @endif

        <div class="space-y-2.5">
            @foreach($drafts as $i => $d)
                <div wire:key="draft-{{ $i }}-{{ md5($d['raw']) }}" class="rounded-[20px] bg-white border border-slate-200/90 p-4 space-y-3 shadow-[0_1px_2px_rgba(15,23,42,.05)]">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <input wire:model="drafts.{{ $i }}.domain" id="d-{{ $i }}-domain" type="text" dir="ltr"
                                   class="w-full border-0 border-b border-transparent hover:border-slate-200 focus:border-indigo-500 focus:ring-0 p-0 font-mono font-bold text-[15px] text-slate-900 bg-transparent text-right">
                            <p class="text-[11px] text-slate-400 font-mono truncate mt-0.5" dir="ltr">{{ $d['raw'] }}</p>
                        </div>
                        <button type="button" wire:click="remove({{ $i }})" class="p-1.5 rounded-xl text-slate-400 hover:bg-rose-50 hover:text-rose-600 cursor-pointer flex-shrink-0" aria-label="لابردن">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                        <label class="space-y-1">
                            <span class="text-[11px] text-slate-500">جۆر</span>
                            <select wire:model="drafts.{{ $i }}.type" id="d-{{ $i }}-type" class="w-full rounded-xl border-slate-200 text-sm">
                                <option value="domain">دۆمەین</option>
                                <option value="hosting">هۆستینگ</option>
                                <option value="email">ئیمەیڵی بزنس</option>
                                <option value="vps">VPS</option>
                                <option value="license">مۆڵەت</option>
                                <option value="maintenance">پشتگیری</option>
                            </select>
                        </label>
                        <label class="space-y-1">
                            <span class="text-[11px] text-slate-500">نرخ</span>
                            <div class="flex">
                                <input wire:model="drafts.{{ $i }}.amount" id="d-{{ $i }}-amount" type="number" step="any" dir="ltr" class="w-full min-w-0 rounded-s-xl border-slate-200 text-sm font-mono">
                                <select wire:model="drafts.{{ $i }}.currency" id="d-{{ $i }}-currency" class="rounded-e-xl border-s-0 border-slate-200 text-xs bg-slate-50">
                                    <option value="USD">$</option>
                                    <option value="IQD">د.ع</option>
                                </select>
                            </div>
                        </label>
                        <label class="space-y-1">
                            <span class="text-[11px] text-slate-500 flex items-center gap-1">
                                بەسەرچوون
                                @if($d['date_source'] === 'registry')<span class="text-emerald-600 font-bold">✓ تۆمارگە</span>@endif
                            </span>
                            <input wire:model="drafts.{{ $i }}.expiry" id="d-{{ $i }}-expiry" type="date" class="w-full rounded-xl text-sm font-mono {{ $errors->has("drafts.$i.expiry") ? 'border-rose-400' : 'border-slate-200' }}">
                        </label>
                        <label class="space-y-1">
                            <span class="text-[11px] text-slate-500">کڕیار</span>
                            <select wire:model="drafts.{{ $i }}.client_id" id="d-{{ $i }}-client" class="w-full rounded-xl text-sm {{ $errors->has("drafts.$i.client_id") ? 'border-rose-400 bg-rose-50' : 'border-slate-200' }}">
                                <option value="">— هەڵیبژێرە —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->business_name ?: $c->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    @if($d['date_source'] === 'ambiguous')
                        <div class="flex flex-wrap items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            <span class="font-bold">بەروارەکە ڕوون نییە، کامیانە؟</span>
                            @foreach($d['date_options'] as $opt)
                                <button type="button" wire:click="pickDate({{ $i }}, '{{ $opt }}')"
                                        class="rounded-lg px-2.5 py-1 font-mono font-bold cursor-pointer {{ $d['expiry'] === $opt ? 'bg-amber-600 text-white' : 'bg-white text-amber-800' }}">
                                    {{ \Carbon\Carbon::parse($opt)->format('j') }} {{ ['','کانوونی دووەم','شوبات','ئازار','نیسان','ئایار','حوزەیران','تەمموز','ئاب','ئەیلوول','تشرینی یەکەم','تشرینی دووەم','کانوونی یەکەم'][\Carbon\Carbon::parse($opt)->month] }} {{ \Carbon\Carbon::parse($opt)->year }}
                                </button>
                            @endforeach
                        </div>
                    @elseif($d['typed_differs'])
                        <p class="text-[11px] text-sky-700 bg-sky-50 rounded-xl px-3 py-1.5">بەرواری نووسراو جیاواز بوو، بەرواری ڕاستی تۆمارگە دانرا.</p>
                    @elseif($d['date_source'] === 'missing')
                        <p class="text-[11px] text-slate-500">ئەم دۆمەینە لە تۆمارگە نەدۆزرایەوە، بەروارەکە بنووسە.</p>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-[13px] font-semibold cursor-pointer {{ ($d['paid'] ?? true) ? 'text-emerald-700' : 'text-rose-700' }}">
                            <input type="checkbox" wire:model.live="drafts.{{ $i }}.paid" id="d-{{ $i }}-paid" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            {{ ($d['paid'] ?? true) ? 'پارەی داوە' : 'پارەی نەداوە · دەچێتە لیستی پارە نەداوەکان' }}
                        </label>
                        @if($d['provider'])
                            <span class="text-[11px] text-slate-400">دابینکەر: {{ $d['provider'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="sticky bottom-24 lg:bottom-4 z-10">
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save"
                    class="w-full rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold py-4 shadow-xl cursor-pointer transition">
                تۆمارکردنی {{ count($drafts) }} خزمەتگوزاری
            </button>
            @if($errors->isNotEmpty())
                <p class="mt-2 text-center text-xs text-rose-600 bg-white/90 rounded-xl py-1">هەندێک دێڕ کڕیار یان بەرواری نییە، بە سوور دیاری کراون.</p>
            @endif
        </div>
    @endif
</div>
