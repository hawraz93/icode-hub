<?php

namespace App\Livewire\Public;

use App\Models\Client;
use App\Models\Project;
use App\Models\Server;
use App\Models\Subscription;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class PortfolioHome extends Component
{
    use WireUiActions;

    public string $selectedCategory = 'all';
    
    // Project View Modal
    public bool $showProjectModal = false;
    public ?Project $selectedProject = null;

    // Quote / Demo Request Modal
    public bool $showQuoteModal = false;
    public string $req_name = '';
    public string $req_business = '';
    public string $req_phone = '';
    public string $req_service = 'pos';
    public string $req_message = '';

    protected function rules(): array
    {
        return [
            'req_name' => 'required|string|max:255',
            'req_business' => 'nullable|string|max:255',
            'req_phone' => 'required|string|max:50',
            'req_service' => 'required|string|max:50',
            'req_message' => 'nullable|string',
        ];
    }

    public function filterCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    public function viewProject(int $id): void
    {
        $this->selectedProject = Project::with('client')->findOrFail($id);
        $this->showProjectModal = true;
    }

    public function openQuoteModal(string $service = 'pos'): void
    {
        $this->req_service = $service;
        $this->showQuoteModal = true;
    }

    public function submitQuote(): void
    {
        $this->validate();

        // Create new client lead
        Client::create([
            'name' => $this->req_name,
            'business_name' => $this->req_business,
            'phone' => $this->req_phone,
            'notes' => "داواکاری نوێ بۆ خزمەتگوزاری: {$this->req_service}. پەیام: {$this->req_message}",
            'status' => 'active',
        ]);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'داواکارییەکەت گەیشت!',
            'description' => 'سوپاس بۆ پەیوەندیکردنت، تیمی iCode لە زووترین کاتدا پەیوەندیت پێوە دەکات.',
        ]);

        $this->showQuoteModal = false;
        $this->reset(['req_name', 'req_business', 'req_phone', 'req_message']);
    }

    public function render()
    {
        $projects = Project::when($this->selectedCategory !== 'all', function ($q) {
                $q->where('category', $this->selectedCategory);
            })
            ->orderBy('order_index')
            ->get();

        $stats = [
            'projects_count' => Project::count(),
            'clients_count' => Client::count(),
            'servers_count' => Server::count(),
            'subscriptions_count' => Subscription::count(),
        ];

        return view('livewire.public.portfolio-home', [
            'projects' => $projects,
            'stats' => $stats,
        ])->layout('layouts.public', ['title' => 'iCode Group | پسپۆڕی پەرەپێدانی سیستەمی پێشکەوتوو و Enterprise']);
    }
}
