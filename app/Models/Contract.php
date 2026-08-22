<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_number',
        'client_id',
        'project_id',
        'title',
        'terms',
        'total_amount',
        'currency',
        'exchange_rate',
        'start_date',
        'end_date',
        'status',
        'signed_by_client',
        'client_signature_name',
        'signed_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_by_client' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
