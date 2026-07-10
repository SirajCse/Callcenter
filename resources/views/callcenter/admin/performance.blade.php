@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Agent Performance')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
    <div class="module-head fade-in d-flex align-items-center justify-content-between">
        <h2><i class="fas fa-trophy text-warning"></i> Agent Performance Ranking</h2>
        <a href="{{ route('callcenter.admin.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-3 fade-in">
        <div class="card-body py-2">
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small font-weight-bold text-muted text-uppercase mr-1">From:</span>
                <input type="date" name="from" class="form-control form-control-sm" style="width:150px" value="{{ $from }}">
                <span class="small font-weight-bold text-muted text-uppercase mr-1">To:</span>
                <input type="date" name="to" class="form-control form-control-sm" style="width:150px" value="{{ $to }}">
                <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                <a href="{{ route('callcenter.admin.performance') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>
    </div>

    {{-- Rankings (rank + colour bands computed in AdminCallCenterController@performance) --}}
    @forelse($stats as $stat)
        @php($successRate = $stat->avg_success_rate ?? 0)
        <div class="agent-rank-card {{ $stat->rank_border_class }} fade-in">
            <div class="agent-avatar">{{ strtoupper(substr($stat->agent?->name ?? '?', 0, 2)) }}</div>
            <div class="agent-info flex-1">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="agent-name font-weight-bold">{{ $stat->agent?->name ?? '—' }}</span>
                    <span class="agent-rank-badge {{ $stat->rank_badge_class }}">
                #{{ $stat->rank }} {{ $stat->rank_medal }}
            </span>
                </div>
                <div class="progress-sm">
                    <div class="fill" style="width:{{ min(100, $successRate) }}%;background:{{ $stat->success_rate_color }}"></div>
                </div>
                <div class="small text-muted mt-1">{{ number_format($successRate, 1) }}% success rate</div>
            </div>
            <div class="agent-stats">
                <div class="agent-stat">
                    <div class="value">{{ $stat->total_calls }}</div>
                    <div class="label">Total Calls</div>
                </div>
                <div class="agent-stat">
                    <div class="value text-success">{{ $stat->completed_tasks }}</div>
                    <div class="label">Completed</div>
                </div>
                <div class="agent-stat">
                    <div class="value text-warning">{{ $stat->transferred_tasks }}</div>
                    <div class="label">Transferred</div>
                </div>
                <div class="agent-stat">
                    <div class="value text-primary">{{ number_format($successRate, 1) }}%</div>
                    <div class="label">Avg Success</div>
                </div>
            </div>
        </div>
    @empty
        <div class="card fade-in">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-chart-bar fa-3x d-block mb-3 opacity-50"></i>
                <span>No performance data for the selected period.</span>
            </div>
        </div>
    @endforelse
@endsection