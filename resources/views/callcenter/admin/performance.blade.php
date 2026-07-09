@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Agent Performance')

@section('page-styles')
    @include('callcenter.partials._frest_css')
    <style>
        .agent-rank-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: .875rem 1.125rem;
            background: #fff;
            border-radius: .5rem;
            border: 1px solid #e5e7eb;
            transition: all .2s;
            margin-bottom: .625rem;
        }
        .agent-rank-card:hover { box-shadow: 0 .125rem .5rem rgba(0,0,0,.06); }
        .agent-rank-card.gold { border-color: #fbbf24; background: #fffbeb; }
        .agent-rank-card.silver { border-color: #d1d5db; background: #f9fafb; }
        .agent-rank-card.bronze { border-color: #d97706; background: #fffbeb; }
        .agent-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            background: #1967d2;
            color: #fff;
            flex-shrink: 0;
        }
        .agent-rank-badge {
            font-size: .75rem;
            font-weight: 600;
            padding: .125rem .75rem;
            border-radius: 50rem;
        }
        .agent-rank-badge.gold { background: #fbbf24; color: #1a1a2e; }
        .agent-rank-badge.silver { background: #d1d5db; color: #1a1a2e; }
        .agent-rank-badge.bronze { background: #d97706; color: #fff; }
        .agent-rank-badge.default { background: #e5e7eb; color: #6b7280; }
        .agent-stats {
            display: flex;
            gap: 1.5rem;
            margin-top: .375rem;
            flex-wrap: wrap;
        }
        .agent-stat { text-align: center; }
        .agent-stat .value { font-size: 1.125rem; font-weight: 700; color: #1a1a2e; }
        .agent-stat .label { font-size: .625rem; color: #6b7280; text-transform: uppercase; letter-spacing: .3px; }
        .progress-sm {
            height: 5px;
            background: #e5e7eb;
            border-radius: .25rem;
            overflow: hidden;
            margin-top: .5rem;
        }
        .progress-sm .fill { height: 100%; border-radius: .25rem; transition: width .6s; }
    </style>
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

    {{-- Rankings --}}
    @forelse($stats as $i => $stat)
        @php
            $rank = $i + 1;
            $rankClass = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
            $rankBadge = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : 'default'));
            $successRate = $stat->avg_success_rate ?? 0;
        @endphp
        <div class="agent-rank-card {{ $rankClass }} fade-in">
            <div class="agent-avatar">{{ strtoupper(substr($stat->agent?->name ?? '?', 0, 2)) }}</div>
            <div class="agent-info flex-1">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="agent-name font-weight-bold">{{ $stat->agent?->name ?? '—' }}</span>
                    <span class="agent-rank-badge {{ $rankBadge }}">
                #{{ $rank }} {{ $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':'')) }}
            </span>
                </div>
                <div class="progress-sm">
                    <div class="fill" style="width:{{ min(100, $successRate) }}%;background:{{ $successRate >= 80 ? '#22c55e' : ($successRate >= 50 ? '#fbbf24' : '#ef4444') }}"></div>
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