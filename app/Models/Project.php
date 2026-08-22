<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'slug',
        'category',
        'client_name',
        'summary',
        'description',
        'case_study',
        'features',
        'tech_stack',
        'thumbnail',
        'gallery',
        'demo_url',
        'live_url',
        'github_url',
        'status',
        'is_featured',
        'order_index',
        'completion_date',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'gallery' => 'array',
        'is_featured' => 'boolean',
        'completion_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected function getLocalizedValue(?string $value)
    {
        if (!$value) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $locale = app()->getLocale();
            return $decoded[$locale] ?? $decoded['en'] ?? $decoded['ku'] ?? reset($decoded);
        }

        return $value;
    }

    public function getTitleAttribute($value)
    {
        return $this->getLocalizedValue($value);
    }

    public function getSummaryAttribute($value)
    {
        return $this->getLocalizedValue($value);
    }

    public function getDescriptionAttribute($value)
    {
        return $this->getLocalizedValue($value);
    }

    public function getCaseStudyAttribute($value)
    {
        return $this->getLocalizedValue($value);
    }

    public function getFeaturesAttribute($value)
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $locale = app()->getLocale();
            if (isset($decoded[$locale]) && is_array($decoded[$locale])) {
                return $decoded[$locale];
            }
            if (isset($decoded['en']) && is_array($decoded['en'])) {
                return $decoded['en'];
            }
            if (isset($decoded['ku']) && is_array($decoded['ku'])) {
                return $decoded['ku'];
            }
            if (isset($decoded['ar']) && is_array($decoded['ar'])) {
                return $decoded['ar'];
            }
            return array_is_list($decoded) ? $decoded : [];
        }

        return [];
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'medical' => __('site.work.medical'),
            'education' => __('site.work.education'),
            'finance' => __('site.work.finance'),
            'pos' => __('site.work.pos'),
            'commercial' => __('site.work.commercial'),
            'web' => 'Web & Cloud',
            default => 'Enterprise System',
        };
    }
}
