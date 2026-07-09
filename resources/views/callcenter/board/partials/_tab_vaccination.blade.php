{{-- _tab_vaccination.blade.php (Frest Design) --}}
@forelse($vaccinations ?? [] as $v)
<div class="tl-item">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">Invoice: {{ $v->invoice_id ?? '—' }}</div>
        <span class="fpill fp-success">Vaccination</span>
    </div>
    @foreach($v->products->take(3) as $p)
    <div class="tl-sub">{{ $p->product?->name ?? '—' }}</div>
    @endforeach
    <div class="tl-meta"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($v->date)->format('d M Y') }}</div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="fas fa-syringe" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No vaccination records</span>
</div>
@endforelse
