@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Live Monitor')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
<div class="fade-in">

  {{-- ★ Compact Topbar --}}
  <div class="cc-topbar">
    <div class="kpi-chip primary"><span class="kn">{{ $pbxAgents->where('status','online')->count() ?? 0 }}</span> Online</div>
    <div class="kpi-chip warning"><span class="kn">{{ $pbxAgents->where('status','busy')->count() ?? 0 }}</span> Busy</div>
    <div class="kpi-chip danger"><span class="kn">{{ $pbxAgents->where('status','dnd')->count() ?? 0 }}</span> DND</div>
    <div class="kpi-chip info"><span class="kn">{{ $pbxAgents->where('status','offline')->count() ?? 0 }}</span> Offline</div>
    <div class="kpi-chip success"><span class="kn">{{ $activeCallCount ?? 0 }}</span> Active Calls</div>
    <div class="cc-actions">
      <span class="fpill fp-success" style="animation:ccpulse 2s infinite"><i class="fas fa-circle" style="font-size:8px"></i> Live</span>
      <a href="{{ route('callcenter.admin.index') }}" class="btn-frest outline sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
  </div>

  {{-- ★ MikoPBX System Health (Frest-styled, data from HealthCheckService) --}}
  <div class="fcard" style="margin-bottom:12px">
    <div class="fcard-head">
      <h3><i class="fas fa-heartbeat"></i> MikoPBX System Health</h3>
      <span class="fpill {{ ($health['overall'] ?? 'unknown') === 'ok' ? 'fp-success' : 'fp-danger' }}">
        {{ $health['overall'] ?? 'Unknown' }}
      </span>
    </div>
    <div class="fcard-body" style="padding:12px">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px">
        @foreach(['ami' => 'AMI Connection', 'rest' => 'REST API', 'sip' => 'SIP Trunk'] as $key => $label)
          @php($status = $health[$key] ?? 'unknown')
          <div style="display:flex;align-items:center;gap:10px;padding:12px;border:1px solid var(--cc-border);border-radius:var(--cc-r2);background:#fff">
            <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                 background:{{ $status === 'ok' ? 'var(--cc-success-light)' : 'var(--cc-danger-light)' }};
                 color:{{ $status === 'ok' ? 'var(--cc-success)' : 'var(--cc-danger)' }}">
              <i class="fas fa-{{ $status === 'ok' ? 'check-circle' : 'times-circle' }}" style="font-size:16px"></i>
            </div>
            <div>
              <div style="font-size:12px;font-weight:600;color:var(--cc-text-dark)">{{ $label }}</div>
              <div style="font-size:11px;color:{{ $status === 'ok' ? 'var(--cc-success)' : 'var(--cc-danger)' }}">{{ ucfirst($status) }}</div>
            </div>
          </div>
        @endforeach
      </div>
      <div style="margin-top:10px;font-size:11px;color:var(--cc-text-muted);display:flex;justify-content:space-between">
        <span>MikoPBX URL: {{ $pbxConfig['url'] ?? '—' }}</span>
        <span>AMI: {{ $pbxConfig['ami_host'] ?? '—' }}:{{ $pbxConfig['ami_port'] ?? '—' }}</span>
        <span>Auto-refresh every 60s</span>
      </div>
    </div>
  </div>

  {{-- ★ Active Calls (Frest-styled, data from RestApiService) --}}
  <div class="fcard" style="margin-bottom:12px">
    <div class="fcard-head">
      <h3><i class="fas fa-phone-volume"></i> Active Calls (MikoPBX Live)</h3>
      <span class="fpill fp-success"><i class="fas fa-circle" style="font-size:8px;animation:ccpulse 1.5s infinite"></i> Real-time</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      @forelse($activeCalls as $call)
        @php
          $caller  = $call['src_num'] ?? $call['src'] ?? $call['caller'] ?? 'Unknown';
          $exten   = $call['dst_num'] ?? $call['dst'] ?? $call['extension'] ?? '—';
          $dur     = $call['duration'] ?? '0:00';
          $state   = $call['state'] ?? $call['status'] ?? 'Active';
          $channel = $call['channel'] ?? $call['src_chan'] ?? '';
        @endphp
        <div class="ch-item" style="border-left:3px solid var(--cc-success)">
          <div class="ch-top">
            <div>
              <strong style="font-size:13px;color:var(--cc-text-dark)">
                <i class="fas fa-phone-volume" style="color:var(--cc-success);font-size:11px;margin-right:4px"></i>
                {{ $caller }}
                <i class="fas fa-arrow-right" style="font-size:10px;margin:0 6px;color:var(--cc-text-light)"></i>
                {{ $exten }}
              </strong>
            </div>
            <div style="display:flex;gap:6px">
              <span class="fpill fp-success"><i class="fas fa-circle" style="font-size:8px;animation:ccpulse 1.5s infinite"></i> {{ $state }}</span>
              @if($dur !== '0:00')
              <span class="fpill fp-secondary">⏱ {{ $dur }}</span>
              @endif
            </div>
          </div>
          <div class="ch-meta">
            @if($channel)<span><i class="fas fa-broadcast-tower" style="margin-right:3px"></i> {{ substr($channel, 0, 30) }}</span>@endif
          </div>
        </div>
      @empty
        <div class="cc-empty">
          <i class="fas fa-phone-slash"></i>
          <span>No active calls right now</span>
        </div>
      @endforelse
    </div>
  </div>

  {{-- ★ PBX Agent Extensions (Frest-styled, data from AgentService) --}}
  <div class="fcard" style="margin-bottom:12px">
    <div class="fcard-head">
      <h3><i class="fas fa-users"></i> PBX Agent Extensions (Live)</h3>
      <span class="fpill fp-secondary">Auto-refreshes every 10s</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="dtable" width="100%">
          <thead>
            <tr>
              <th>Agent</th>
              <th>Extension</th>
              <th>Status</th>
              <th>Last Seen</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($pbxAgents as $agent)
              @php
                $dotColor = match($agent->status ?? 'offline') {
                  'online'  => 'var(--cc-success)',
                  'busy'    => 'var(--cc-warning)',
                  'dnd'     => 'var(--cc-danger)',
                  'away'    => 'var(--cc-warning)',
                  default   => 'var(--cc-text-light)',
                };
              @endphp
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="agent-avatar" style="width:32px;height:32px;font-size:12px">
                    {{ strtoupper(substr($agent->name ?? $agent->extension ?? '?', 0, 2)) }}
                  </div>
                  <div>
                    <div class="td-name">{{ $agent->name ?? '—' }}</div>
                    <div class="td-sub">{{ $agent->email ?? '' }}</div>
                  </div>
                </div>
              </td>
              <td>
                <span class="fpill fp-primary"><i class="fas fa-phone" style="font-size:9px"></i> {{ $agent->extension ?? '—' }}</span>
              </td>
              <td>
                <span class="status-dot-cc" style="background:{{ $dotColor }};box-shadow:0 0 0 2px {{ $dotColor }}25"></span>
                <span style="font-size:12px;color:{{ $dotColor }}">{{ ucfirst($agent->status ?? 'offline') }}</span>
              </td>
              <td>
                <span style="font-size:11px;color:var(--cc-text-muted)">
                  {{ $agent->last_seen_at ? $agent->last_seen_at->diffForHumans() : 'Never' }}
                </span>
              </td>
              <td>
                @if($agent->extension)
                <button class="btn-icon success" onclick="window.mikopbxDial && window.mikopbxDial('{{ $agent->extension }}')" title="Call {{ $agent->extension }}">
                  <i class="fas fa-phone"></i>
                </button>
                @else
                <span style="color:var(--cc-text-light);font-size:11px">—</span>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5">
                <div class="cc-empty">
                  <i class="fas fa-user-slash"></i>
                  <span>No PBX agents synced. Visit <a href="{{ url('/pbx/agents') }}" style="color:var(--cc-primary)">/pbx/agents</a> to sync.</span>
                </div>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ★ Call Center Agents (from DB — always works) --}}
  <div class="fcard">
    <div class="fcard-head">
      <h3><i class="fas fa-headset"></i> Call Center Agents (DB)</h3>
      <span class="fpill fp-secondary">From call center tasks</span>
    </div>
    <div class="fcard-body" style="padding:8px 12px">
      <div class="table-responsive">
        <table class="dtable" width="100%">
          <thead>
            <tr>
              <th>Agent</th>
              <th>PBX Ext</th>
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
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="agent-avatar" style="width:32px;height:32px;font-size:12px">
                    {{ strtoupper(substr($ag->name ?? '?', 0, 2)) }}
                  </div>
                  <div>
                    <div class="td-name">{{ $ag->name ?? '—' }}</div>
                    <div class="td-sub">{{ $ag->email ?? '' }}</div>
                  </div>
                </div>
              </td>
              <td>
                @if($ag->pbx_extension)
                  <span class="fpill fp-primary"><i class="fas fa-phone" style="font-size:9px"></i> {{ $ag->pbx_extension }}</span>
                @else
                  <span style="color:var(--cc-text-light);font-size:11px">Not assigned</span>
                @endif
              </td>
              <td>
                <span class="status-dot-cc {{ $ag->is_online ? 'online' : 'offline' }}"></span>
                <span style="font-size:12px;color:{{ $ag->is_online ? 'var(--cc-success)' : 'var(--cc-text-muted)' }}">
                  {{ $ag->is_online ? 'Online' : 'Offline' }}
                </span>
              </td>
              <td><span style="font-weight:700;color:var(--cc-primary)">{{ $ag->total_calls ?? 0 }}</span></td>
              <td><span style="font-weight:700;color:var(--cc-success)">{{ $ag->completed_tasks ?? 0 }}</span></td>
              <td><span style="font-weight:700;color:var(--cc-warning)">{{ $ag->pending_tasks ?? 0 }}</span></td>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="progress-sm" style="width:80px">
                    <div class="fill" style="width:{{ min(100, $ag->success_rate ?? 0) }}%;background:{{ $ag->success_rate_color ?? 'var(--cc-primary)' }}"></div>
                  </div>
                  <span style="font-size:11px;color:var(--cc-text-muted)">{{ number_format($ag->success_rate ?? 0, 1) }}%</span>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
<script>
// Auto-refresh every 30s
setTimeout(function() { location.reload(); }, 30000);
</script>
@endsection
