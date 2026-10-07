{{--
    The one invoice layout used by the admin print view and the client portal, so both always show
    the same lines, periods and totals. Client details come from the issue-time snapshot.
    @param \App\Models\Invoice $invoice  (items and payments loaded)
--}}
@php
    $money = fn ($v) => \App\Support\Money::format((float) $v, $invoice->currency);
    $billTo = $invoice->bill_to;
@endphp
<div class="bg-white p-6 sm:p-8 rounded-2xl text-slate-900 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b-2 border-slate-100">
        <div class="flex items-center gap-4">
            <img src="{{ asset('images/logo.png') }}" alt="iCode Group" class="h-16 w-auto object-contain">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">iCode Group</h2>
                <p class="text-xs text-slate-500 font-medium">Software Development & IT Solutions</p>
                <p class="text-xs text-slate-400 font-mono" dir="ltr">info@icode.com | 0750 445 1234</p>
            </div>
        </div>
        <div class="text-start sm:text-end">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">وەسڵی فەرمی / INVOICE</div>
            <div class="text-xl font-black font-mono text-indigo-600 mt-1" dir="ltr">{{ $invoice->invoice_number }}</div>
            <div class="text-xs text-slate-500 mt-1 font-mono">بەروار: {{ $invoice->issue_date->format('Y-m-d') }}</div>
            <div class="text-xs text-slate-500 font-mono">کاتی پارەدان: {{ $invoice->due_date->format('Y-m-d') }}</div>
            <div class="text-xs text-slate-500">دراو: <span class="font-mono font-bold">{{ $invoice->currency }}</span></div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl text-xs">
        <div>
            <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">وەسڵ بۆ / Bill To:</span>
            <strong class="text-sm font-extrabold text-slate-900 block">{{ $billTo['business_name'] ?: $billTo['name'] }}</strong>
            @if($billTo['business_name'])<div class="text-slate-600 mt-1">{{ $billTo['name'] }}</div>@endif
            @if($billTo['phone'])<div class="text-slate-600 font-mono mt-0.5" dir="ltr">{{ $billTo['phone'] }}</div>@endif
            <div class="text-slate-500 mt-0.5">{{ collect([$billTo['city'], $billTo['address']])->filter()->implode(' - ') }}</div>
        </div>
        <div class="sm:text-end">
            <span class="font-bold text-slate-400 uppercase tracking-wider block mb-1">دۆخی وەسڵ / Status:</span>
            @include('livewire.admin.partials.invoice-status', ['invoice' => $invoice])
            @if($invoice->project)<div class="text-slate-500 text-xs mt-2">پڕۆژە: {{ $invoice->project->title }}</div>@endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-xs text-start">
            <thead class="bg-slate-100/80 text-slate-700 font-bold border-y border-slate-200">
                <tr>
                    <th class="p-3 text-start">#</th>
                    <th class="p-3 text-start">وەسف و خزمەتگوزاری</th>
                    <th class="p-3 text-center">ژمارە / ماوە</th>
                    <th class="p-3 text-end">نرخ</th>
                    <th class="p-3 text-end">کۆ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoice->items as $idx => $item)
                    <tr>
                        <td class="p-3 font-mono text-slate-400">{{ $idx + 1 }}</td>
                        <td class="p-3 font-semibold text-slate-900">{{ $item->description }}
                            @if($item->start_date && $item->expiry_date)
                                <div class="text-[11px] text-slate-500 font-normal">ماوە: <span dir="ltr">{{ $item->start_date->format('Y-m-d') }} → {{ $item->expiry_date->format('Y-m-d') }}</span></div>
                            @endif
                        </td>
                        <td class="p-3 text-center font-bold">{{ $item->quantity_label }}</td>
                        <td class="p-3 text-end font-mono" dir="ltr">{{ $money($item->unit_price) }}<div class="text-[10px] text-slate-400 font-sans">{{ $item->price_basis_label }}</div></td>
                        <td class="p-3 text-end font-mono font-extrabold text-slate-900" dir="ltr">{{ $money($item->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex flex-col sm:flex-row justify-between gap-6 pt-4 border-t-2 border-slate-100">
        <div class="text-xs text-slate-500 max-w-sm space-y-1">
            <strong class="text-slate-800 block mb-1">تێبینی و مەرجەکان:</strong>
            <p>{{ $invoice->terms ?: 'سوپاس بۆ متمانەکردنتان بە iCode Group.' }}</p>
            @if($invoice->notes)<p class="text-indigo-600 font-medium">{{ $invoice->notes }}</p>@endif
        </div>
        <div class="w-full sm:w-64 space-y-2 text-xs">
            <div class="flex justify-between text-slate-600"><span>کۆی سەرەتایی:</span><span class="font-mono font-bold" dir="ltr">{{ $money($invoice->subtotal) }}</span></div>
            @if($invoice->discount > 0)
                <div class="flex justify-between text-rose-600"><span>داشکاندن:</span><span class="font-mono font-bold" dir="ltr">{{ \App\Support\Money::format(-(float) $invoice->discount, $invoice->currency) }}</span></div>
            @endif
            @if($invoice->tax > 0)
                <div class="flex justify-between text-slate-600"><span>باج:</span><span class="font-mono font-bold" dir="ltr">{{ $money($invoice->tax) }}</span></div>
            @endif
            <div class="flex justify-between pt-2 border-t border-slate-200 text-sm font-black text-slate-900"><span>کۆی گشتی:</span><span class="font-mono font-black text-indigo-600" dir="ltr">{{ $money($invoice->total) }}</span></div>
            @if($invoice->status !== 'cancelled')
                <div class="flex justify-between text-emerald-600 font-bold pt-1"><span>بڕی دراو:</span><span class="font-mono" dir="ltr">{{ $money($invoice->paid_amount) }}</span></div>
                <div class="flex justify-between {{ $invoice->remaining_balance > 0 ? 'text-amber-700' : 'text-emerald-700' }} font-extrabold pt-1"><span>ماوە:</span><span class="font-mono" dir="ltr">{{ $money(max(0, $invoice->remaining_balance)) }}</span></div>
            @endif
        </div>
    </div>

    @if(($showPayments ?? false) && $invoice->payments->isNotEmpty())
        <div class="text-xs space-y-1 border-t border-slate-100 pt-3">
            <div class="font-bold text-slate-700">پارەدانەکان</div>
            @foreach($invoice->payments as $payment)
                <div class="flex justify-between">
                    <span class="font-mono text-slate-500" dir="ltr">{{ $payment->paid_on?->format('Y-m-d') ?? '—' }}</span>
                    <span class="font-mono font-bold {{ $payment->amount < 0 ? 'text-rose-600' : 'text-emerald-700' }}" dir="ltr">{{ \App\Support\Money::format((float) $payment->amount, $payment->currency) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="p-4 bg-slate-900 text-white rounded-xl text-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <span class="text-slate-400 block text-[11px]">شێوازی پارەدان و هەژمارەکان:</span>
            <span class="font-mono text-cyan-400 font-bold">FIB Account / FastPay: 0750 445 1234</span>
        </div>
        <div class="text-start sm:text-end text-[11px] text-slate-400 font-mono">iCode Group | Hawraz Khaled</div>
    </div>
</div>
