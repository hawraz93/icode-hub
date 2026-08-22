<div class="space-y-6 max-w-6xl mx-auto">

    <!-- Top Tabs Bar (Clean Navigation) -->
    <div class="bg-white rounded-2xl p-2 border border-slate-200 shadow-xs flex items-center gap-2">
        <button 
            wire:click="$set('activeTab', 'profile')"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer {{ $activeTab === 'profile' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span>پرۆفایلی کەسی من</span>
        </button>

        <button 
            wire:click="$set('activeTab', 'users')"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer {{ $activeTab === 'users' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>بەڕێوەبردنی بەکارهێنەران و ئەدمینەکان</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $activeTab === 'users' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">
                {{ count($users) }}
            </span>
        </button>
    </div>

    @if($activeTab === 'profile')
        <!-- ================= TAB 1: MY PROFILE ================= -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 1. Avatar & Quick Info Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 flex flex-col items-center text-center space-y-4">
                <div class="relative group">
                    <div class="w-28 h-28 rounded-full overflow-hidden ring-4 ring-indigo-50 border-2 border-indigo-500 shadow-md">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar Preview" class="w-full h-full object-cover">
                        @else
                            <img src="{{ $currentAvatarUrl }}" alt="{{ $currentUser->name }}" class="w-full h-full object-cover">
                        @endif
                    </div>

                    <label class="absolute bottom-0 right-0 p-2 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 cursor-pointer transition transform hover:scale-105" title="گۆڕینی وێنە">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                    </label>
                </div>

                <div class="space-y-1">
                    <h2 class="text-base font-extrabold text-slate-900">{{ $currentUser->name }}</h2>
                    <p class="text-xs text-slate-500 font-mono">{{ $currentUser->email }}</p>
                    <div class="pt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-100">
                            🛡️ {{ $currentUser->role_label }}
                        </span>
                    </div>
                </div>

                @if($currentUser->avatar)
                    <button 
                        type="button" 
                        wire:click="removeAvatar" 
                        wire:confirm="ئایا دڵنیایت دەتەوێت وێنەی پرۆفایلەکەت بسڕیتەوە؟"
                        class="text-xs font-bold text-rose-600 hover:text-rose-800 transition">
                        سڕینەوەی وێنەی تایبەت
                    </button>
                @endif

                <div wire:loading wire:target="avatar" class="text-xs text-indigo-600 font-bold animate-pulse">
                    وێنەکە باردەکرێت... تکایە چاوەڕێبە
                </div>
            </div>

            <!-- 2. Personal Information Form -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- General Info Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            زانیارییە سەرەکییەکان
                        </h3>
                    </div>

                    <form wire:submit.prevent="updateProfile" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input 
                                    label="ناوی تەواو *" 
                                    placeholder="ناوی خۆت بنووسە" 
                                    wire:model="name" 
                                />
                            </div>

                            <div>
                                <x-input 
                                    label="ناونیشانی ئیمەیڵ *" 
                                    placeholder="admin@example.com" 
                                    wire:model="email" 
                                    dir="ltr" 
                                />
                            </div>

                            <div>
                                <x-phone
                                    label="ژمارەی مۆبایل"
                                    placeholder="0750-123-4567"
                                    wire:model="phone"
                                    dir="ltr"
                                    mask="####-###-####"
                                />
                            </div>

                            <div>
                                <x-input 
                                    label="ڕۆڵ و پلە لە سیستەم" 
                                    value="{{ $currentUser->role_label }}" 
                                    disabled 
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-button type="submit" primary label="پاشەکەوتکردنی گۆڕانکارییەکان" spinner="updateProfile" />
                        </div>
                    </form>
                </div>

                <!-- Change Password Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            گۆڕینی وشەی نهێنی (Password & Security)
                        </h3>
                    </div>

                    <form wire:submit.prevent="updatePassword" class="space-y-4">
                        <div>
                            <x-password 
                                label="وشەی نهێنی ئێستات *" 
                                placeholder="••••••••" 
                                wire:model="current_password" 
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-password 
                                    label="وشەی نهێنی نوێ *" 
                                    placeholder="لانیکەم ٨ پیت یان ژمارە" 
                                    wire:model="new_password" 
                                />
                            </div>

                            <div>
                                <x-password 
                                    label="دووپاتکردنەوەی وشەی نهێنی نوێ *" 
                                    placeholder="••••••••" 
                                    wire:model="new_password_confirmation" 
                                />
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <x-button type="submit" warning label="نوێکردنەوەی وشەی نهێنی" spinner="updatePassword" />
                        </div>
                    </form>
                </div>

            </div>

        </div>

    @else
        <!-- ================= TAB 2: MANAGE USERS ================= -->
        <div class="space-y-4">
            
            <!-- Action Bar -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                <div class="w-full sm:w-80">
                    <x-input 
                        wire:model.live.debounce.300ms="userSearch" 
                        placeholder="گەڕان بەپێی ناوی بەکارهێنەر، ئیمەیڵ، مۆبایل..." 
                    />
                </div>

                <button 
                    wire:click="openNewUserModal" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-xs shadow-indigo-200 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    <span>+ زیادکردنی بەکارهێنەری نوێ</span>
                </button>
            </div>

            <!-- Users Grid / Table -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($users as $u)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between space-y-4 relative overflow-hidden {{ $u->id === auth()->id() ? 'ring-2 ring-indigo-500/20 bg-indigo-50/10' : '' }}">
                        
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $u->avatar_url }}" alt="{{ $u->name }}" class="w-12 h-12 rounded-full object-cover border border-slate-200 shadow-xs">
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <h4 class="font-extrabold text-slate-900 text-sm">{{ $u->name }}</h4>
                                        @if($u->id === auth()->id())
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">تۆ</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 font-mono">{{ $u->email }}</p>
                                </div>
                            </div>

                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold {{ $u->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $u->status === 'active' ? 'چالاک' : 'ناچالاک' }}
                            </span>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                            <div>
                                <span class="font-bold">ڕۆڵ:</span>
                                <span class="text-slate-800 font-semibold">{{ $u->role_label }}</span>
                            </div>
                            @if($u->phone)
                                <div class="font-mono text-slate-500 text-[11px]" dir="ltr">
                                    {{ $u->phone }}
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                            <button 
                                wire:click="editUser({{ $u->id }})" 
                                class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <span>دەستکاریکردن</span>
                            </button>

                            @if($u->id !== auth()->id())
                                <button 
                                    wire:click="deleteUser({{ $u->id }})" 
                                    wire:confirm="ئایا دڵنیایت لە سڕینەوەی ئەم بەکارهێنەرە؟"
                                    class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-400 space-y-2">
                        <p class="font-bold text-slate-700">هیچ بەکارهێنەرێک نەدۆزرایەوە</p>
                    </div>
                @endforelse
            </div>

        </div>
    @endif

    <!-- WireUI Modal for Create / Edit User -->
    <x-modal-card title="{{ $editingUserId ? 'دەستکاریکردنی بەکارهێنەر' : 'زیادکردنی بەکارهێنەری نوێ' }}" wire:model="showUserModal" max-width="md">
        <div class="space-y-4">
            <div>
                <x-input 
                    label="ناوی تەواو *" 
                    placeholder="ناوی بەکارهێنەر" 
                    wire:model="user_name" 
                />
            </div>

            <div>
                <x-input 
                    label="ئیمەیڵ *" 
                    placeholder="user@example.com" 
                    wire:model="user_email" 
                    dir="ltr" 
                />
            </div>

            <div>
                <x-phone
                    label="ژمارەی مۆبایل"
                    placeholder="0750-123-4567"
                    wire:model="user_phone"
                    dir="ltr"
                    mask="####-###-####"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <x-native-select
                        label="ڕۆڵ و دەسەڵات *"
                        wire:model="user_role"
                        :options="[
                            ['name' => 'بەڕێوەبەری گشتی (Super Admin)', 'id' => 'super_admin'],
                            ['name' => 'ئەدمین (Admin)', 'id' => 'admin'],
                            ['name' => 'کارمەند (Staff)', 'id' => 'staff'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>

                <div>
                    <x-native-select
                        label="دۆخی ئەکاونت *"
                        wire:model="user_status"
                        :options="[
                            ['name' => 'چالاک (Active)', 'id' => 'active'],
                            ['name' => 'ناچالاک (Inactive)', 'id' => 'inactive'],
                        ]"
                        option-label="name"
                        option-value="id"
                    />
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-3">
                <p class="text-xs text-slate-500 font-medium">
                    {{ $editingUserId ? 'ئەگەر ناتەوێت وشەی نهێنی بگۆڕیت، بە بەتاڵی جێی بهێڵە:' : 'وشەی نهێنی بۆ بەکارهێنەر دیاریبکە:' }}
                </p>

                <div>
                    <x-password 
                        label="{{ $editingUserId ? 'وشەی نهێنی نوێ (ئارەزوومەندانە)' : 'وشەی نهێنی *' }}" 
                        placeholder="••••••••" 
                        wire:model="user_password" 
                    />
                </div>

                <div>
                    <x-password 
                        label="دووپاتکردنەوەی وشەی نهێنی" 
                        placeholder="••••••••" 
                        wire:model="user_password_confirmation" 
                    />
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex items-center justify-between w-full">
                <x-button primary label="{{ $editingUserId ? 'نوێکردنەوە' : 'تۆمارکردن' }}" wire:click="saveUser" />
                <x-button flat label="پاشگەزبوونەوە" x-on:click="close" />
            </div>
        </x-slot>
    </x-modal-card>

</div>
