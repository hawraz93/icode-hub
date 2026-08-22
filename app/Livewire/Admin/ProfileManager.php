<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class ProfileManager extends Component
{
    use WithFileUploads;
    use WireUiActions;

    // Tab control: 'profile' or 'users'
    public string $activeTab = 'profile';

    // 1. My Profile Form
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public $avatar = null;
    public ?string $currentAvatarUrl = null;

    // 2. Change Password Form
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    // 3. Manage Users
    public bool $showUserModal = false;
    public ?int $editingUserId = null;
    public string $user_name = '';
    public string $user_email = '';
    public string $user_phone = '';
    public string $user_role = 'admin';
    public string $user_status = 'active';
    public string $user_password = '';
    public string $user_password_confirmation = '';
    public string $userSearch = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->currentAvatarUrl = $user->avatar_url;
        }
    }

    public function updateProfile(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'avatar' => 'nullable|image|max:2048', // 2MB Max
        ], [
            'name.required' => 'تکایە ناو بنووسە.',
            'email.required' => 'تکایە ئیمەیڵ بنووسە.',
            'email.email' => 'شێوازی ئیمەیڵەکە دروست نییە.',
            'email.unique' => 'ئەم ئیمەیڵە پێشتر بەکارهاتووە.',
            'avatar.image' => 'فایلی وێنە هەڵبژێرە.',
            'avatar.max' => 'قەبارەی وێنە نابێت لە ٢ مێگابایت زیاتر بێت.',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];

        if ($this->avatar) {
            // Delete old avatar if custom
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $this->avatar->store('avatars', 'public');
            $data['avatar'] = $path;
            $this->avatar = null;
        }

        $user->update($data);
        $this->currentAvatarUrl = $user->fresh()->avatar_url;

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'پرۆفایل نوێکرایەوە! ✨',
            'description' => 'زانیارییەکانی پرۆفایلەکەت بە سەرکەوتوویی نوێکرانەوە.',
        ]);
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->update(['avatar' => null]);
        $this->avatar = null;
        $this->currentAvatarUrl = $user->fresh()->avatar_url;

        $this->notification()->send([
            'icon' => 'info',
            'title' => 'وێنە لابرا',
            'description' => 'وێنەی پرۆفایلەکەت گەڕێندرایەوە سەر دۆخی سەرەتایی.',
        ]);
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'تکایە وشەی نهێنی ئێستات بنووسە.',
            'current_password.current_password' => 'وشەی نهێنی ئێستات هەڵەیە.',
            'new_password.required' => 'تکایە وشەی نهێنی نوێ بنووسە.',
            'new_password.min' => 'وشەی نهێنی نوێ دەبێت لانیکەم ٨ پیت یان ژمارە بێت.',
            'new_password.confirmed' => 'دووپاتکردنەوەی وشەی نهێنی نوێ یەکناگرێتەوە.',
        ]);

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'وشەی نهێنی گۆڕدرا! 🔐',
            'description' => 'وشەی نهێنی ئەکاونتەکەت بە سەرکەوتوویی نوێکرایەوە.',
        ]);
    }

    // --- Manage Users Section ---

    public function openNewUserModal(): void
    {
        $this->editingUserId = null;
        $this->user_name = '';
        $this->user_email = '';
        $this->user_phone = '';
        $this->user_role = 'admin';
        $this->user_status = 'active';
        $this->user_password = '';
        $this->user_password_confirmation = '';
        $this->showUserModal = true;
    }

    public function editUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingUserId = $user->id;
        $this->user_name = $user->name;
        $this->user_email = $user->email;
        $this->user_phone = $user->phone ?? '';
        $this->user_role = $user->role ?? 'admin';
        $this->user_status = $user->status ?? 'active';
        $this->user_password = '';
        $this->user_password_confirmation = '';
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $rules = [
            'user_name' => 'required|string|max:255',
            'user_email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'user_phone' => 'nullable|string|max:30',
            'user_role' => 'required|in:super_admin,admin,staff',
            'user_status' => 'required|in:active,inactive',
        ];

        if (!$this->editingUserId) {
            $rules['user_password'] = 'required|string|min:8|confirmed';
        } else {
            $rules['user_password'] = 'nullable|string|min:8|confirmed';
        }

        $this->validate($rules, [
            'user_name.required' => 'تکایە ناو بنووسە.',
            'user_email.required' => 'تکایە ئیمەیڵ بنووسە.',
            'user_email.unique' => 'ئەم ئیمەیڵە پێشتر تۆمارکراوە.',
            'user_password.required' => 'تکایە وشەی نهێنی بنووسە.',
            'user_password.min' => 'وشەی نهێنی دەبێت لانیکەم ٨ پیت بێت.',
            'user_password.confirmed' => 'دووپاتکردنەوەی وشەی نهێنی یەکناگرێتەوە.',
        ]);

        $data = [
            'name' => $this->user_name,
            'email' => $this->user_email,
            'phone' => $this->user_phone,
            'role' => $this->user_role,
            'status' => $this->user_status,
        ];

        if (!empty($this->user_password)) {
            $data['password'] = Hash::make($this->user_password);
        }

        if ($this->editingUserId) {
            $user = User::findOrFail($this->editingUserId);
            $user->update($data);
            $msg = "زانیارییەکانی بەکارهێنەر «{$user->name}» نوێکرایەوە.";
        } else {
            $user = User::create($data);
            $msg = "بەکارهێنەری نوێ «{$user->name}» بە سەرکەوتوویی زیادکرا.";
        }

        $this->showUserModal = false;

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'سەرکەوتوو بوو! 🎉',
            'description' => $msg,
        ]);
    }

    public function deleteUser(int $id): void
    {
        if ($id === Auth::id()) {
            $this->notification()->send([
                'icon' => 'error',
                'title' => 'کردارەکە ڕێگەپێدراو نییە!',
                'description' => 'ناتوانیت ئەکاونتەکەی خۆت بسڕیتەوە کاتێک چوویتەتە ژوورەوە.',
            ]);
            return;
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->delete();

        $this->notification()->send([
            'icon' => 'warning',
            'title' => 'بەکارهێنەر سڕایەوە',
            'description' => "بەکارهێنەر «{$name}» بە سەرکەوتوویی سڕایەوە.",
        ]);
    }

    public function render()
    {
        $users = User::query()
            ->when($this->userSearch, function ($q) {
                $q->where('name', 'like', "%{$this->userSearch}%")
                  ->orWhere('email', 'like', "%{$this->userSearch}%")
                  ->orWhere('phone', 'like', "%{$this->userSearch}%");
            })
            ->orderBy('id', 'desc')
            ->get();

        return view('livewire.admin.profile-manager', [
            'users' => $users,
            'currentUser' => Auth::user(),
        ])->layout('layouts.app', [
            'title' => 'پرۆفایل و بەڕێوەبردنی بەکارهێنەران',
            'header' => 'ڕێکخستنی پرۆفایل و ئەدمینەکان',
        ]);
    }
}
