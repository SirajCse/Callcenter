{{-- _tab_nebulize.blade.php (Frest Design) --}}
@forelse($nebulizes ?? [] as $n)
<div class="tl-item">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">Invoice: {{ $n->invoice_id ?? '—' }}</div>
        <span class="fpill fp-info">Nebulize</span>
    </div>
    @foreach($n->services->take(3) as $s)
    <div class="tl-sub">{{ $s->service?->name ?? '—' }}</div>
    @endforeach
    <div class="tl-meta"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($n->date)->format('d M Y') }}</div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="fas fa-wind" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No nebulizer records</span>
</div>
@endforelse
