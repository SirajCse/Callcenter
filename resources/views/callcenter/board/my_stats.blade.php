@extends('lab.layouts.contentLayoutCallCenterNav')
@section('title', 'My Performance')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="module-head fade-in">
  <h2><i class="fas fa-chart-line"></i> My Performance</h2>
  <a href="{{ route('callcenter.board') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Board</a>
</div>

{{-- ── Today Stats (computed in CallBoardController@myStats) ──────── --}}
<div class="row mb-3 fade-in">
  @foreach($todayItems as $item)
  <div class="col-6 col-md-3 mb-3">
    <div class="cc-stat-card {{ $item['color'] }}">
      <div class="sc-icon"><i class="fas fa-{{ $item['icon'] }}"></i></div>
      <div class="sc-num">{{ $item['value'] }}</div>
      <div class="sc-label">{{ $item['label'] }} (Today)</div>
    </div>
  </div>
  @endforeach
</div>

<div class="row fade-in">

  {{-- ── Monthly Breakdown ───────────────────────────────── --}}
  <div class="col-lg-7 mb-3">
    <div class="fcard">
      <div class="fcard-head">
        <h3><i class="fas fa-calendar-alt"></i> This Month — Daily Breakdown</h3>
        <span class="fpill fp-primary">{{ now()->format('F Y') }}</span>
      </div>
      <div class="fcard-body">
        @if($monthStats->isEmpty())
          <div class="cc-empty"><i class="fas fa-chart-bar"></i><span>No stats recorded this month yet.</span></div>
        @else
          @foreach($monthStats as $stat)
          <div class="month-bar-row">
            <div style="width:60px;font-weight:600;color:var(--cc-text-dark)">{{ $stat->stat_date->format('d M') }}</div>
            <div class="month-bar-bg">
              <div class="month-bar-fill" style="width:{{ min(100, ($stat->total_calls / $maxCalls) * 100) }}%"></div>
            </div>
            <div style="width:40px;text-align:right;font-weight:700;color:var(--cc-primary)">{{ $stat->total_calls }}</div>
            <div style="width:40px;text-align:right;color:var(--cc-success)">{{ $stat->completed_tasks }}</div>
            <div style="width:50px;text-align:right;color:var(--cc-text-muted)">{{ number_format($stat->success_rate,1) }}%</div>
          </div>
          @endforeach
          <div class="month-bar-row" style="font-weight:700;border-top:2px solid var(--cc-border);padding-top:10px;margin-top:4px">
            <div style="width:60px;color:var(--cc-text-dark)">TOTAL</div>
            <div class="month-bar-bg"></div>
            <div style="width:40px;text-align:right;color:var(--cc-primary)">{{ $monthStats->sum('total_calls') }}</div>
            <div style="width:40px;text-align:right;color:var(--cc-success)">{{ $monthStats->sum('completed_tasks') }}</div>
            <div style="width:50px;text-align:right;color:var(--cc-text-muted)">{{ number_format($monthStats->avg('success_rate'),1) }}%</div>
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── Pending Tasks ────────────────────────────────────── --}}
  <div class="col-lg-5 mb-3">
    <div class="fcard">
      <div class="fcard-head">
        <h3><i class="fas fa-tasks"></i> Pending Tasks</h3>
        <span class="fpill fp-danger">{{ $tasks->count() }}</span>
      </div>
      <div class="fcard-body" style="max-height:400px;overflow-y:auto">
        @forelse($tasks as $task)
        <div style="padding:10px 12px;background:var(--cc-body);border-left:3px solid {{ $task->priority_border_var }};border-radius:var(--cc-r2);margin-bottom:8px;cursor:pointer;transition:all .2s"
             onclick="window.location='{{ route('callcenter.board') }}?pid={{ $task->patient_id }}'">
          <div style="font-size:12px;font-weight:600;color:var(--cc-text-dark)">{{ $task->title }}</div>
          <div style="font-size:11px;color:var(--cc-text-muted);margin-top:3px;display:flex;gap:10px">
            <span><i class="fas fa-user" style="font-size:9px"></i> {{ $task->patient?->name ?? '—' }}</span>
            @if($task->due_date)<span><i class="far fa-clock" style="font-size:9px"></i> {{ $task->due_date->format('d M') }}</span>@endif
          </div>
        </div>
        @empty
        <div class="cc-empty"><i class="fas fa-check-double"></i><span>All clear! No pending tasks.</span></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
