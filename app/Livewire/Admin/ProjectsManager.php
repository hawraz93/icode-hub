<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ProjectsManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';
    public string $categoryFilter = 'all';

    public bool $showModal = false;
    public ?int $editingId = null;

    // Project Form fields
    public ?int $client_id = null;
    public string $title = '';
    public string $slug = '';
    public string $category = 'pos';
    public string $client_name = '';
    public string $summary = '';
    public string $description = '';
    public string $case_study = '';
    public string $demo_url = '';
    public string $live_url = '';
    public string $github_url = '';
    public string $status = 'completed';
    public bool $is_featured = true;
    public string $features_text = '';
    public string $tech_stack_text = '';

    protected function rules(): array
    {
        return [
            'client_id' => 'nullable|exists:clients,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'client_name' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'description' => 'nullable|string',
            'case_study' => 'nullable|string',
            'demo_url' => 'nullable|url|max:255',
            'live_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'status' => 'required|in:completed,in_progress,maintenance,planned',
            'is_featured' => 'boolean',
            'features_text' => 'nullable|string',
            'tech_stack_text' => 'nullable|string',
        ];
    }

    public function updatedTitle($value): void
    {
        if (!$this->editingId) {
            $this->slug = Str::slug($value);
            if (empty($this->slug)) {
                $this->slug = 'project-' . time();
            }
        }
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $proj = Project::findOrFail($id);
        $this->editingId = $proj->id;
        $this->client_id = $proj->client_id;
        $this->title = $proj->title;
        $this->slug = $proj->slug;
        $this->category = $proj->category;
        $this->client_name = $proj->client_name ?? '';
        $this->summary = $proj->summary ?? '';
        $this->description = $proj->description ?? '';
        $this->case_study = $proj->case_study ?? '';
        $this->demo_url = $proj->demo_url ?? '';
        $this->live_url = $proj->live_url ?? '';
        $this->github_url = $proj->github_url ?? '';
        $this->status = $proj->status;
        $this->is_featured = (bool) $proj->is_featured;
        $this->features_text = is_array($proj->features) ? implode("\n", $proj->features) : '';
        $this->tech_stack_text = is_array($proj->tech_stack) ? implode(', ', $proj->tech_stack) : '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $features = array_filter(array_map('trim', explode("\n", $this->features_text)));
        $techStack = array_filter(array_map('trim', explode(',', $this->tech_stack_text)));

        $data = [
            'client_id' => $this->client_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->category,
            'client_name' => $this->client_name,
            'summary' => $this->summary,
            'description' => $this->description,
            'case_study' => $this->case_study,
            'features' => $features,
            'tech_stack' => $techStack,
            'demo_url' => $this->demo_url,
            'live_url' => $this->live_url,
            'github_url' => $this->github_url,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
        ];

        if ($this->editingId) {
            $proj = Project::findOrFail($this->editingId);
            $proj->update($data);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'پڕۆژە نوێکرایەوە',
                'description' => 'زانیاری پڕۆژە نوێکرایەوە.',
            ]);
        } else {
            Project::create($data);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'پڕۆژە دروستکرا',
                'description' => 'پڕۆژەی نوێ بە سەرکەوتوویی زیادکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $proj = Project::findOrFail($id);
        $proj->delete();

        $this->notification()->send([
            'icon' => 'info',
            'title' => 'سڕایەوە',
            'description' => 'پڕۆژە بە سەرکەوتوویی سڕایەوە.',
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->client_id = null;
        $this->title = '';
        $this->slug = '';
        $this->category = 'pos';
        $this->client_name = '';
        $this->summary = '';
        $this->description = '';
        $this->case_study = '';
        $this->demo_url = '';
        $this->live_url = '';
        $this->github_url = '';
        $this->status = 'completed';
        $this->is_featured = true;
        $this->features_text = '';
        $this->tech_stack_text = 'Laravel 12, Livewire 3, Tailwind CSS, MySQL';
    }

    public function render()
    {
        $projects = Project::with('client')
            ->when($this->search, function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('summary', 'like', "%{$this->search}%");
            })
            ->when($this->categoryFilter !== 'all', fn($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('order_index')
            ->paginate(9);

        $clients = Client::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.projects-manager', [
            'projects' => $projects,
            'clients' => $clients,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی پڕۆژەکان', 'header' => 'بەڕێوەبردنی بەرهەمەکان و پۆرتفۆلیۆ']);
    }
}
