<div class="space-y-6">
    
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex flex-1 items-center gap-3">
            <div class="w-full max-w-xs">
                <x-input wire:model.live.debounce.300ms="search" placeholder="گەڕان لە پڕۆژەکان..." icon="magnifying-glass" />
            </div>
            
            <select wire:model.live="categoryFilter" class="rounded-xl border-slate-300 text-xs font-semibold text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all">هەموو کەرتەکان</option>
                <option value="pos">خاڵی فرۆش و دەرمانخانە</option>
                <option value="medical">پزیشکی و تاقیگە</option>
                <option value="education">فێرکاری و زانکۆکان</option>
                <option value="finance">دارایی و ژمێریاری</option>
                <option value="commercial">بازرگانی و FMCG</option>
            </select>
        </div>

        <button wire:click="openModal" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>زیادکردنی پڕۆژەی نوێ</span>
        </button>
    </div>

    <!-- Projects Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($projects as $proj)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $proj->category_label }}
                        </span>

                        @if($proj->is_featured)
                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded bg-amber-50 text-amber-700 border border-amber-200">Featured ★</span>
                        @endif
                    </div>

                    <h3 class="font-extrabold text-slate-900 text-base line-clamp-1">{{ $proj->title }}</h3>
                    <p class="text-xs text-slate-500 mt-2 line-clamp-3 leading-relaxed">{{ $proj->summary }}</p>

                    @if($proj->tech_stack)
                        <div class="flex flex-wrap gap-1.5 mt-4">
                            @foreach($proj->tech_stack as $tech)
                                <span class="px-2 py-0.5 text-[10px] font-mono font-bold bg-slate-100 text-slate-700 rounded-md">{{ $tech }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($proj->demo_url)
                            <a href="{{ $proj->demo_url }}" target="_blank" class="p-1.5 text-slate-500 hover:text-indigo-600 rounded-lg transition" title="دیمۆی ڕاستەوخۆ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        @endif
                        @if($proj->github_url)
                            <a href="{{ $proj->github_url }}" target="_blank" class="p-1.5 text-slate-500 hover:text-slate-900 rounded-lg transition" title="GitHub Repo">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                            </a>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button wire:click="edit({{ $proj->id }})" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="دەستکاری">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>

                        <button wire:click="delete({{ $proj->id }})" wire:confirm="ئایا دڵنیایت لە سڕینەوەی ئەم پڕۆژەیە؟" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="سڕینەوە">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400">هیچ پڕۆژەیەک نەدۆزرایەوە.</div>
        @endforelse
    </div>

    <!-- WireUI Modal Card for Project Create/Edit -->
    <x-modal-card title="{{ $editingId ? 'دەستکاریکردنی پڕۆژە' : 'زیادکردنی پڕۆژەی نوێ' }}" wire:model="showModal" max-width="3xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="md:col-span-2">
                <x-input label="ناوی پڕۆژە *" placeholder="سیستەمی ..." wire:model.live.debounce.500ms="title" />
            </div>

            <div>
                <x-input label="Slug (لینکی تایبەت) *" wire:model="slug" dir="ltr" />
            </div>

            <div>
                <x-native-select
                    label="کەرت و جۆری پڕۆژە *"
                    wire:model="category"
                    :options="[
                        ['name' => 'خاڵی فرۆش و دەرمانخانە (POS & Pharmacy)', 'id' => 'pos'],
                        ['name' => 'پزیشکی و تاقیگە (Medical & Laboratory)', 'id' => 'medical'],
                        ['name' => 'فێرکاری و زانکۆکان (University & Education)', 'id' => 'education'],
                        ['name' => 'دارایی و ژمێریاری (Finance & Accounting)', 'id' => 'finance'],
                        ['name' => 'بازرگانی و پیشەسازی (Commercial & FMCG)', 'id' => 'commercial'],
                        ['name' => 'وێبسایت و پۆرتال (Web & Portal)', 'id' => 'web'],
                    ]"
                    option-label="name"
                    option-value="id"
                />
            </div>

            <div class="md:col-span-2">
                <x-textarea label="پوختەی پڕۆژە (Summary) *" placeholder="وەسفێکی کورت بۆ سەر ماڵپەڕ..." wire:model="summary" />
            </div>

            <div class="md:col-span-2">
                <x-textarea label="کەیسی پڕۆژە و شیکاری (Case Study)" placeholder="کێشەی کڕیار چی بوو و سیستەمەکە چۆن چارەسەری کرد..." wire:model="case_study" />
            </div>

            <div class="md:col-span-2">
                <x-textarea label="تایبەتمەندییە سەرەکییەکان (Features - هەر دێڕێک دانەیەک)" placeholder="ئاگاداری بەسەرچوون&#10;چاپکردنی وەسڵ&#10;..." wire:model="features_text" />
            </div>

            <div class="md:col-span-2">
                <x-input label="تەکنەلۆژیاکان (Tech Stack - بە کۆما جیای بکەوە)" placeholder="Laravel 12, Livewire 3, Tailwind CSS, MySQL" wire:model="tech_stack_text" />
            </div>

            <div>
                <x-input label="لینکی دیمۆی ڕاستەوخۆ (Demo URL)" placeholder="https://..." wire:model="demo_url" dir="ltr" />
            </div>

            <div>
                <x-input label="لینکی GitHub Repo" placeholder="https://github.com/..." wire:model="github_url" dir="ltr" />
            </div>

            <div class="flex items-center pt-6">
                <x-toggle label="پڕۆژەی هەڵبژێردراو بێت لە سەرەکی (Featured)" wire:model="is_featured" />
            </div>

        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-end gap-3">
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
                <x-button primary label="{{ $editingId ? 'نوێکردنەوە' : 'تۆمارکردنی پڕۆژە' }}" wire:click="save" spinner="save" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
