{{-- resources/views/callcenter/calllogs/history_modal.blade.php --}}
<div class="modal fade" id="modalCallHistory" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="fas fa-history mr-2"></i>Full Call History</h6>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-2" id="callHistoryBody">
        {{-- Filled via AJAX --}}
      </div>
    </div>
  </div>
</div>

{{-- ── Standalone rendered content (also used for AJAX response) ── --}}
@isset($logs)
<div id="callHistoryContent">

  {{-- Other agents who called this patient --}}
  @if(isset($otherAgentCalls) && $otherAgentCalls->count())
  <div class="alert alert-info alert-sm py-2 mb-2" style="font-size:12px">
    <i class="fas fa-info-circle mr-1"></i>
    <strong>{{ $otherAgentCalls->count() }}</strong> call(s) by other agents on this patient.
    Last by <strong>{{ $otherAgentCalls->first()->caller?->name }}</strong>
    on {{ \Carbon\Carbon::parse($otherAgentCalls->first()->call_date)->format('d M Y') }}.
  </div>
  @endif

  @forelse($logs as $log)
  <div class="card mb-2 border-left-{{ $log->receive ? 'success' : 'danger' }}" style="border-left:3px solid {{ $log->receive ? '#28c76f' : '#ea5455' }}">
    <div class="card-body p-2">
      <div class="d-flex justify-content-between align-items-start mb-1">
        <strong style="font-size:12px">
          {{ \Carbon\Carbon::parse($log->call_date)->format('d M Y, h:i A') }}
        </strong>
        <div style="display:flex;gap:4px">
          <span class="badge badge-{{ $log->method === 'incoming' ? 'success' : 'primary' }}">
            {{ ucfirst($log->method ?? 'outgoing') }}
          </span>
          <span class="badge badge-{{ $log->receive ? 'success' : 'danger' }}">
            {{ $log->receive ? 'Answered' : 'No Answer' }}
          </span>
          @if($log->priority)
          <span class="badge badge-{{ $log->priority === 'high' ? 'danger' : ($log->priority === 'medium' ? 'warning' : 'secondary') }}">
            {{ ucfirst($log->priority) }}
          </span>
          @endif
        </div>
      </div>

      @if($log->call_note)
      <p class="mb-1" style="font-size:12px;color:#444">
        <i class="fas fa-sticky-note text-warning mr-1"></i>{{ $log->call_note }}
      </p>
      @endif

      @if($log->caller_opinion)
      <p class="mb-1" style="font-size:11px;color:#6e6b7b">
        <strong>Agent Opinion:</strong> {{ $log->caller_opinion }}
      </p>
      @endif

      @if($log->patient_opinion)
      <p class="mb-1" style="font-size:11px;color:#6e6b7b">
        <strong>Patient Opinion:</strong> {{ $log->patient_opinion }}
      </p>
      @endif

      @if($log->followup_target_date)
      <p class="mb-1" style="font-size:11px;color:#7367f0">
        <i class="fas fa-calendar-check mr-1"></i>
        Follow-up target: <strong>{{ \Carbon\Carbon::parse($log->followup_target_date)->format('d M Y') }}</strong>
        @if($log->followup_target_note) — {{ $log->followup_target_note }} @endif
      </p>
      @endif

      @if($log->transfer_to)
      <p class="mb-1" style="font-size:11px;color:#ff9f43">
        <i class="fas fa-exchange-alt mr-1"></i>
        Transferred to: <strong>{{ $log->transfer?->name }}</strong>
        @if($log->transfer_cause) — {{ $log->transfer_cause }} @endif
      </p>
      @endif

      @if($log->die)
      <p class="mb-1 text-danger" style="font-size:11px">
        <i class="fas fa-skull mr-1"></i> Deceased confirmed on this call
      </p>
      @endif

      <div style="font-size:10px;color:#b9b9c3;display:flex;gap:12px;margin-top:4px">
        <span><i class="fas fa-user mr-1"></i>{{ $log->caller?->name ?? '—' }}</span>
        <span>⏱ {{ gmdate('i:s', $log->duration ?? 0) }}</span>
        @if($log->sms_sent)<span class="text-info"><i class="fas fa-sms mr-1"></i>SMS Sent</span>@endif
        @if($log->letter_sent)<span class="text-warning"><i class="fas fa-envelope mr-1"></i>Letter Queued</span>@endif
      </div>
    </div>
  </div>
  @empty
  <div class="text-center text-muted p-4">
    <i class="fas fa-phone-slash fa-2x mb-2"></i><br>No call history found
  </div>
  @endforelse
</div>
@endisset
