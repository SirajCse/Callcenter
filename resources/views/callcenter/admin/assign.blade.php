@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Assign Tasks')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
    <div class="module-head fade-in d-flex align-items-center justify-content-between">
        <h2><i class="fas fa-user-check text-primary"></i> Assign Tasks to Agents</h2>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge badge-light">{{ $patients->count() }} patients</span>
            <a href="{{ route('callcenter.admin.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <div class="row fade-in">
        {{-- Patient List --}}
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users"></i> Patient List
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-primary" id="selectedCount">0 selected</span>
                        <label class="mb-0 d-flex align-items-center gap-1 small cursor-pointer">
                            <input type="checkbox" id="chkAll" onchange="toggleAllPatients(this)">
                            Select All
                        </label>
                    </div>
                </div>
                <div class="patient-scroll">
                    <table class="table table-hover table-sm">
                        <thead>
                        <tr>
                            <th width="3%"></th>
                            <th>Register ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Gender</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($patients as $p)
                            <tr>
                                <td><input type="checkbox" class="pat-chk" value="{{ $p->id }}"></td>
                                <td><span class="font-weight-bold">{{ $p->register_id ?? '—' }}</span></td>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->phone ?? '—' }}</td>
                                <td>{{ $p->gender ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-users fa-2x d-block mb-2 opacity-50"></i>
                                    <span>No patients found</span>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Assignment Form --}}
        <div class="col-lg-4 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-paper-plane"></i> Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Assign To</label>
                        <select id="a_agent" class="form-control form-control-sm">
                            <option value="">— Distribute to All Agents —</option>
                            @foreach($agents as $ag)
                                <option value="{{ $ag->id }}">{{ $ag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Task Type</label>
                        <select id="a_task_type" class="form-control form-control-sm">
                            @foreach(\App\Models\CallCenter\Task::TYPES as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted text-uppercase">Priority</label>
                                <select id="a_priority" class="form-control form-control-sm">
                                    <option value="high">High</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="small font-weight-bold text-muted text-uppercase">Due Date</label>
                                <input type="date" id="a_due_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold text-muted text-uppercase">Note</label>
                        <textarea id="a_note" class="form-control form-control-sm" rows="2" placeholder="Instructions for agent..."></textarea>
                    </div>
                    <button class="btn btn-success btn-sm w-100" onclick="assignTasks()">
                        <i class="fas fa-paper-plane"></i> Assign Tasks
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
    <script>
        function toggleAllPatients(cb) {
            document.querySelectorAll('.pat-chk').forEach(c => c.checked = cb.checked);
            updateSelectedCount();
        }

        document.querySelectorAll('.pat-chk').forEach(c => c.addEventListener('change', updateSelectedCount));

        function updateSelectedCount() {
            var count = document.querySelectorAll('.pat-chk:checked').length;
            document.getElementById('selectedCount').textContent = count + ' selected';
        }

        function assignTasks() {
            var ids = Array.from(document.querySelectorAll('.pat-chk:checked')).map(c => parseInt(c.value));
            if (!ids.length) {
                toastr.warning('Please select at least one patient.');
                return;
            }

            var agentId = $('#a_agent').val();
            var distribute = !agentId ? 1 : 0;

            $.post('{{ route("callcenter.admin.assign") }}', {
                _token: '{{ csrf_token() }}',
                patient_ids: ids,
                agent_id: agentId || null,
                task_type: $('#a_task_type').val(),
                priority: $('#a_priority').val(),
                due_date: $('#a_due_date').val(),
                note: $('#a_note').val(),
                distribute: distribute,
            }, function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    setTimeout(function() { location.reload(); }, 1500);
                }
            }).fail(function(xhr) {
                var msg = xhr.responseJSON?.message || 'Assignment failed. Please try again.';
                toastr.error(msg);
            });
        }
    </script>
@endsection