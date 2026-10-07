@php
    $badge = match ($invoice->display_status) {
        'paid' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'partial' => 'bg-cyan-50 text-cyan-700 border border-cyan-200',
        'overdue' => 'bg-rose-50 text-rose-700 border border-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-500 line-through',
        'draft' => 'bg-slate-100 text-slate-600',
        default => 'bg-blue-50 text-blue-700 border border-blue-200',
    };
@endphp
<span class="px-2.5 py-1 text-[11px] font-bold rounded-full {{ $badge }}">{{ $invoice->display_status_label }}</span>
