{{-- _tab_lab.blade.php (Frest Design) --}}
@forelse($labGroups ?? [] as $group)
<div class="tl-item">
    <div class="tl-top" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;gap:8px">
        <div class="tl-title">Barcode: {{ $group->barcode ?? $group->sl_no ?? '—' }}</div>
        <span class="fpill {{ $group->done ? 'fp-success' : 'fp-warning' }}">{{ $group->done ? 'Completed' : 'Pending' }}</span>
    </div>
    @foreach($group->items->take(3) as $item)
    <div class="tl-sub">{{ $item->test?->name ?? $item->name ?? '—' }}</div>
    @endforeach
    <div class="tl-meta"><i class="far fa-calendar"></i> {{ \Carbon\Carbon::parse($group->created_at)->format('d M Y') }} &nbsp; Total: {{ number_format($group->total,2) }}</div>
</div>
@empty
<div style="text-align:center;padding:40px 20px;color:var(--cc-text-light)">
    <i class="fas fa-flask" style="font-size:30px;margin-bottom:10px;opacity:.3;display:block"></i>
    <span style="font-size:13px">No lab reports</span>
</div>
@endforelse
