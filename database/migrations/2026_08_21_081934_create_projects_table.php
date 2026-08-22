<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('other'); // medical, education, finance, pos, commercial, web, other
            $table->string('client_name')->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->longText('case_study')->nullable();
            $table->json('features')->nullable();
            $table->json('tech_stack')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('gallery')->nullable();
            $table->string('demo_url')->nullable();
            $table->string('live_url')->nullable();
            $table->string('github_url')->nullable();
            $table->enum('status', ['completed', 'in_progress', 'maintenance', 'planned'])->default('completed');
            $table->boolean('is_featured')->default(false);
            $table->integer('order_index')->default(0);
            $table->date('completion_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
