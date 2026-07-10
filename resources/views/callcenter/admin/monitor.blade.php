@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Live Monitor')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
    <div class="module-head fade-in d-flex align-items-center justify-content-between">
        <h2><i class="fas fa-satellite-dish text-success"></i> Live Monitor</h2>
        <div class="d-flex gap-2 align-items-center">
        <span class="badge badge-success" style="animation:pulse 2s infinite">
            <i class="fas fa-circle" style="font-size:8px"></i> Live
        </span>
            <a href="{{ route('callcenter.admin.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid fade-in">
        <div class="kpi-card">
            <div class="kpi-icon bg-primary-light"><i class="fas fa-users"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['online_agents'] }}</div>
                <div class="kpi-label">Online Agents</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-warning-light"><i class="fas fa-tasks"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['tasks_today'] }}</div>
                <div class="kpi-label">Tasks Today</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-success-light"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['completed_today'] }}</div>
                <div class="kpi-label">Completed</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-danger-light"><i class="fas fa-clock"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['pending_tasks'] }}</div>
                <div class="kpi-label">Pending</div>
            </div>
        </div>
    </div>

    {{-- Agent Status Table --}}
    <div class="card fade-in">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0"><i class="fas fa-users"></i> Agent Status</h5>
            <span class="text-muted small">Auto-refreshes every 30s</span>
        </div>
        <div class="monitor-table-wrap">
            <table class="monitor-table">
                <thead>
                <tr>
                    <th>Agent</th>
                    <th>Status</th>
                    <th>Today Calls</th>
                    <th>Completed</th>
                    <th>Pending</th>
                    <th>Success Rate</th>
                </tr>
                </thead>
                <tbody>
                @foreach($agents as $ag)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="agent-avatar" style="width:34px;height:34px;font-size:.75rem">
                                    {{ strtoupper(substr($ag->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-weight-bold">{{ $ag->name }}</div>
                                    <div class="small text-muted">{{ $ag->agent_code ?? 'ID:'.$ag->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="status-dot {{ $ag->is_online ? 'online' : 'offline' }}"></span>
                            <span class="small {{ $ag->is_online ? 'text-success' : 'text-muted' }}">
                            {{ $ag->is_online ? 'Online' : 'Offline' }}
                        </span>
                        </td>
                        <td><span class="font-weight-bold text-primary">{{ $ag->day_stat?->total_calls ?? 0 }}</span></td>
                        <td><span class="font-weight-bold text-success">{{ $ag->day_stat?->completed_tasks ?? 0 }}</span></td>
                        <td><span class="font-weight-bold text-warning">{{ $ag->pending_tasks }}</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress-sm" style="width:80px;">
                                    <div class="fill" style="width:{{ $ag->success_rate }}%;background:{{ $ag->success_rate_color }}"></div>
                                </div>
                                <span class="small text-muted">{{ number_format($ag->success_rate, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
    <script>
        setTimeout(function() { location.reload(); }, 30000);
    </script>
@endsection