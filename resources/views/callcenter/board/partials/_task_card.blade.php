{{-- resources/views/callcenter/board/partials/_task_card.blade.php (Frest Design) --}}
@php
    $pc = $task->priority === 'high' ? 'hp' : ($task->priority === 'medium' ? 'mp' : 'lp');
    $pillClass = $task->priority === 'high' ? 'fp-danger' : ($task->priority === 'medium' ? 'fp-warning' : 'fp-success');
@endphp
<div class="tk-card {{ $pc }}" onclick="loadPatient({{ $task->patient_id }})">
    <div class="d-flex justify-content-between align-items-start mb-1">
        <div class="tc-title">
            @if($task->is_pinned)<i class="fas fa-thumbtack" style="color:var(--cc-warning);font-size:10px;margin-right:4px"></i>@endif
            {{ $task->title }}
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0;margin-left:8px">
            <span class="fpill {{ $pillClass }}">{{ strtoupper($task->priority) }}</span>
            <span class="fpill fp-primary">{{ \App\Models\CallCenter\Task::TYPES[$task->task_type] ?? $task->task_type }}</span>
        </div>
    </div>
    <div class="tc-note">{{ Str::limit($task->note, 80) }}</div>
    <div class="tc-meta">
        @if($task->due_date)<span><i class="far fa-clock"></i> {{ $task->due_date->format('d M Y') }}</span>@endif
        @if($task->completed_at)<span style="color:var(--cc-success)"><i class="fas fa-check"></i> {{ $task->completed_at->format('d M, h:i A') }}</span>@endif
        @if($task->transferredTo?->name)<span style="color:var(--cc-info)"><i class="fas fa-exchange-alt"></i> → {{ $task->transferredTo->name }}</span>@endif
        @if($task->transfer_reason)<span style="color:var(--cc-warning)">({{ Str::limit($task->transfer_reason,30) }})</span>@endif
        <span><i class="fas fa-user"></i> {{ $task->patient?->name ?? '—' }}</span>
    </div>
    @if($task->status === 'pending')
    <div class="tc-actions">
        <button class="tca success" onclick="event.stopPropagation();completeTask({{ $task->id }})"><i class="fas fa-check"></i> Done</button>
        <button class="tca primary" onclick="event.stopPropagation();openLogCall({{ $task->patient_id }}, {{ $task->id }})"><i class="fas fa-phone"></i> Call</button>
        <button class="tca warning" onclick="event.stopPropagation();transferTask({{ $task->id }})"><i class="fas fa-exchange-alt"></i> Transfer</button>
        <button class="tca secondary" onclick="event.stopPropagation();pinTask({{ $task->id }})"><i class="fas fa-thumbtack"></i></button>
    </div>
    @endif
</div>
