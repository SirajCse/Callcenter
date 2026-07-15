@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Agent Performance')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $stats->count() }}</span> Agents</div>
    <div class="kpi-chip success"><span class="kn">{{ $stats->sum('total_calls') }}</span> Total Calls</div>
    <div class="kpi-chip warning"><span class="kn">{{ $stats->sum('completed_tasks') }}</span> Completed</div>
    <div class="kpi-chip info"><span class="kn">{{ number_format($stats->avg('avg_success_rate'), 1) }}%</span> Avg Success</div>
    <div class="cc-actions">
      <a href="{{ route('callcenter.admin.index') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
  </div>

  {{-- ★ Date Filter (Frest styled) --}}
  <div class="filters-card">
    <form method="GET" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
      <div>
        <label class="filter-label">From</label>
        <input type="date" name="from" value="{{ $from }}" style="height:34px;font-size:12px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:6px 10px">
      </div>
      <div>
        <label class="filter-label">To</label>
        <input type="date" name="to" value="{{ $to }}" style="height:34px;font-size:12px;border-radius:var(--cc-r2);border:1px solid var(--cc-border2);padding:6px 10px">
      </div>
      <button class="btn-frest primary sm"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('callcenter.admin.performance') }}" class="btn-frest outline sm"><i class="fas fa-undo"></i> Reset</a>
    </form>
  </div>

  {{-- ★ Agent Rankings (Frest styled) --}}
  @forelse($stats as $stat)
    @php($successRate = $stat->avg_success_rate ?? 0)
    <div class="agent-rank-card {{ $stat->rank_border_class ?? '' }}" style="display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--cc-border);border-left:3px solid var(--cc-border2);border-radius:var(--cc-r2);margin-bottom:10px;background:#fff;transition:all .2s">
      <div class="agent-avatar" style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--cc-primary),var(--cc-purple));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;color:#fff;flex-shrink:0">
        {{ strtoupper(substr($stat->agent?->name ?? '?', 0, 2)) }}
      </div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
          <span class="agent-name" style="font-weight:600;font-size:14px;color:var(--cc-text-dark)">{{ $stat->agent?->name ?? '—' }}</span>
          <span class="agent-rank-badge {{ $stat->rank_badge_class ?? '' }}" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px">
            #{{ $stat->rank ?? 0 }} {{ $stat->rank_medal ?? '' }}
          </span>
        </div>
        <div class="progress-sm" style="height:4px;background:var(--cc-border);border-radius:4px;overflow:hidden;margin-top:6px">
          <div class="fill" style="height:100%;width:{{ min(100, $successRate) }}%;background:{{ $stat->success_rate_color ?? 'var(--cc-primary)' }};border-radius:4px;transition:width .6s ease"></div>
        </div>
        <div style="font-size:11px;color:var(--cc-text-muted);margin-top:4px">{{ number_format($successRate, 1) }}% success rate</div>
      </div>
      <div class="agent-stats" style="display:flex;gap:16px">
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:var(--cc-text-dark);font-family:var(--cc-font2)">{{ $stat->total_calls ?? 0 }}</div>
          <div style="font-size:10px;color:var(--cc-text-muted)">Calls</div>
        </div>
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:var(--cc-success)">{{ $stat->completed_tasks ?? 0 }}</div>
          <div style="font-size:10px;color:var(--cc-text-muted)">Done</div>
        </div>
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:var(--cc-warning)">{{ $stat->transferred_tasks ?? 0 }}</div>
          <div style="font-size:10px;color:var(--cc-text-muted)">Xfer</div>
        </div>
        <div style="text-align:center">
          <div style="font-size:18px;font-weight:700;color:var(--cc-primary)">{{ number_format($successRate, 1) }}%</div>
          <div style="font-size:10px;color:var(--cc-text-muted)">Success</div>
        </div>
      </div>
    </div>
  @empty
    <div class="fcard">
      <div class="cc-empty">
        <i class="fas fa-chart-bar"></i>
        <span>No performance data for the selected period.</span>
      </div>
    </div>
  @endforelse

</div>
@endsection
