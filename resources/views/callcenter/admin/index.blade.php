@extends('lab.layouts.contentLayoutCallCenterNav')

@section('title', 'Call Center Admin Dashboard')

@section('page-styles')
@include('callcenter.partials._frest_css')
@endsection

@section('content')
    <div class="module-head fade-in d-flex align-items-center justify-content-between">
        <h2><i class="fas fa-crown text-warning"></i> Call Center Admin</h2>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge badge-primary"><i class="fas fa-users"></i> {{ $agents->count() }} Agents</span>
            <span class="badge badge-success"><i class="fas fa-circle" style="font-size:8px"></i> {{ $kpi['online_agents'] ?? 0 }} Online</span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid fade-in">
        <div class="kpi-card">
            <div class="kpi-icon bg-primary-light"><i class="fas fa-users"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['total_agents'] ?? 0 }}</div>
                <div class="kpi-label">Total Agents</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-warning-light"><i class="fas fa-tasks"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['tasks_today'] ?? 0 }}</div>
                <div class="kpi-label">Tasks Today</div>
                <small class="text-muted">{{ $kpi['pending_tasks'] ?? 0 }} pending</small>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-success-light"><i class="fas fa-check-circle"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['completed_today'] ?? 0 }}</div>
                <div class="kpi-label">Completed Today</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon bg-danger-light"><i class="fas fa-clock"></i></div>
            <div>
                <div class="kpi-value">{{ $kpi['overdue_tasks'] ?? 0 }}</div>
                <div class="kpi-label">Overdue Tasks</div>
                <small class="{{ ($kpi['overdue_tasks'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                    {{ ($kpi['overdue_tasks'] ?? 0) > 0 ? '⚠️ Needs attention' : '✓ All good' }}
                </small>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="adm-tabs fade-in">
        <button class="adm-tab active" data-tab="assign">
            <i class="fas fa-user-plus"></i> Assign Tasks
            <span class="badge-pill">{{ $agents->count() }}</span>
        </button>
        <button class="adm-tab" data-tab="performance">
            <i class="fas fa-trophy"></i> Performance
        </button>
        <button class="adm-tab" data-tab="monitor">
            <i class="fas fa-satellite-dish"></i> Live Monitor
            <span class="badge-pill bg-success">{{ $kpi['online_agents'] ?? 0 }}</span>
        </button>
    </div>

    {{-- TAB 1: ASSIGN TASKS --}}
    <div id="tab-assign" class="adm-panel active">
        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filter Patients</h5>
                        <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <span class="filter-label">District</span>
                                <input type="text" id="f_district" class="form-control form-control-sm" placeholder="e.g. Dhaka">
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Thana / Area</span>
                                <input type="text" id="f_thana" class="form-control form-control-sm" placeholder="e.g. Gulshan">
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Gender</span>
                                <select id="f_gender" class="form-control form-control-sm">
                                    <option value="">All</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Age From</span>
                                <input type="number" id="f_age_from" class="form-control form-control-sm" placeholder="18" min="0">
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Age To</span>
                                <input type="number" id="f_age_to" class="form-control form-control-sm" placeholder="100" max="150">
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">{{__('Patient Type')}} <sup class="text-danger">*</sup></label>
                                <select class="form-control form-control-sm dynamic-select" data-placeholder="Patient Type"
                                        data-category="patient_type" data-value="name" data-key="name" data-url="ajax/search-dynamic-option" name="patient_type" id="patient_type">
                                    <option value="" selected>All</option>
                                    <option value="htn">HTN</option>
                                    <option value="dm">DM</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Register ID</span>
                                <input type="text" id="f_register_id" class="form-control form-control-sm" placeholder="e.g. 100-200 or >100">
                            </div>

                            <div class="filter-group">
                                <span class="filter-label">Last Visit</span>
                                <select id="f_last_visit" class="form-control form-control-sm">
                                    <option value="">Any</option>
                                    <option value="3">3+ months ago</option>
                                    <option value="6">6+ months ago</option>
                                    <option value="9">9+ months ago</option>
                                    <option value="12">12+ months ago</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Not Called</span>
                                <select id="f_not_called" class="form-control form-control-sm">
                                    <option value="">Any</option>
                                    <option value="7">7+ days</option>
                                    <option value="30">30+ days</option>
                                    <option value="90">90+ days</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Missed Follow-up</span>
                                <select id="f_missed_fu" class="form-control form-control-sm">
                                    <option value="">Any</option>
                                    <option value="yes">Overdue Only</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <span class="filter-label">Max Results</span>
                                <input type="number" id="f_count" class="form-control form-control-sm" value="100" min="1" max="1000">
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm w-100" onclick="runFilter()">
                            <i class="fas fa-search"></i> Preview Matching Patients
                        </button>
                        <div id="filterResult" class="mt-2 small"></div>
                        <input type="hidden" id="filteredPatientIds" value="">

                        {{-- Patient Preview Table --}}
                        <div id="patientPreview" style="display:none;">
                            <div class="patient-preview-wrap">
                                <table class="table table-sm table-hover">
                                    <thead>
                                    <tr>
                                        <th width="3%">
                                            <input type="checkbox" id="chkAllPreview" onchange="toggleAllPreview(this)" class="table-checkbox">
                                        </th>
                                        <th>Register ID</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Gender</th>
                                    </tr>
                                    </thead>
                                    <tbody id="patientListBody"></tbody>
                                </table>
                            </div>

                            {{-- Selection Controls --}}
                            <div class="selection-controls">
                            <span class="small text-muted">
                                <span id="selectedCount">0</span> selected
                            </span>
                                <span class="divider"></span>
                                <button class="btn btn-link btn-sm p-0" onclick="selectAllPreview()">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <span class="divider"></span>
                                <button class="btn btn-link btn-sm p-0 text-danger" onclick="deselectAllPreview()">
                                    <i class="fas fa-times"></i> Deselect All
                                </button>
                                <span class="divider"></span>
                                <span class="small text-muted" id="previewCount"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-user-check"></i> Assign to Agent</h5>
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
                            <textarea id="a_note" class="form-control form-control-sm" rows="2" placeholder="Agent instructions..."></textarea>
                        </div>
                        <button class="btn btn-success btn-sm w-100" onclick="assignTasks()">
                            <i class="fas fa-paper-plane"></i> Assign Tasks
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: PERFORMANCE --}}
    <div id="tab-performance" class="adm-panel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="fas fa-trophy text-warning"></i> Agent Performance Ranking</h5>
                <form method="GET" class="d-flex gap-2 align-items-center" action="{{ route('callcenter.admin.performance') }}">
                    <input type="date" name="from" class="form-control form-control-sm" style="width:140px" value="{{ request('from', now()->startOfMonth()->toDateString()) }}">
                    <input type="date" name="to" class="form-control form-control-sm" style="width:140px" value="{{ request('to', today()->toDateString()) }}">
                    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
                </form>
            </div>
            <div class="card-body">
                {{-- Rank + colour classes computed in AdminCallCenterController@index --}}
                @forelse($rankedStats ?? [] as $stat)
                    @php($successRate = $stat->success_rate ?? 0)
                    <div class="agent-rank-card {{ $stat->rank_border_class ?? '' }}">
                        <div class="agent-avatar">{{ strtoupper(substr($stat->agent?->name ?? '?', 0, 2)) }}</div>
                        <div class="agent-info">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="agent-name">{{ $stat->agent?->name ?? '—' }}</span>
                                <span class="agent-rank-badge {{ $stat->rank_badge_class ?? '' }}">#{{ $stat->rank ?? 0 }} {{ $stat->rank_medal ?? '' }}</span>
                            </div>
                            <div class="progress-sm">
                                <div class="fill" style="width:{{ min(100, $successRate) }}%;background:{{ $stat->success_rate_color ?? 'var(--cc-primary)' }}"></div>
                            </div>
                        </div>
                        <div class="agent-stats">
                            <div class="agent-stat"><div class="value">{{ $stat->total_calls ?? 0 }}</div><div class="label">Calls</div></div>
                            <div class="agent-stat"><div class="value text-success">{{ $stat->completed_tasks ?? 0 }}</div><div class="label">Done</div></div>
                            <div class="agent-stat"><div class="value text-warning">{{ $stat->transferred_tasks ?? 0 }}</div><div class="label">Xfer</div></div>
                            <div class="agent-stat"><div class="value">{{ number_format($successRate, 1) }}%</div><div class="label">Success</div></div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <span>No stats yet for this period</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB 3: LIVE MONITOR --}}
    <div id="tab-monitor" class="adm-panel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="fas fa-satellite-dish"></i> Live Agent Status</h5>
                <span class="badge badge-success" style="animation:pulse 2s infinite"><i class="fas fa-circle" style="font-size:8px"></i> Live</span>
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
                                    <div class="agent-avatar" style="width:32px;height:32px;font-size:.6875rem">{{ strtoupper(substr($ag->name, 0, 2)) }}</div>
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
                            <td><span class="font-weight-bold text-warning">{{ $ag->pending_tasks ?? 0 }}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress-sm" style="width:70px;margin:0">
                                        <div class="fill" style="width:{{ min(100, $ag->success_rate ?? 0) }}%;background:{{ $ag->success_rate_color ?? 'var(--cc-primary)' }}"></div>
                                    </div>
                                    <span class="small text-muted">{{ number_format($ag->success_rate ?? 0, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('page-scripts')
@include('callcenter.partials._frest_js_init')
    <script>
        // Tab switching
        document.querySelectorAll('.adm-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.adm-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.adm-panel').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            });
        });

        // Reset filters
        function resetFilters() {
            document.querySelectorAll('#tab-assign .form-control').forEach(el => {
                if (el.tagName === 'SELECT') el.selectedIndex = 0;
                else el.value = '';
            });
            document.getElementById('f_count').value = '100';
            document.getElementById('filterResult').innerHTML = '';
            document.getElementById('filteredPatientIds').value = '';
            document.getElementById('patientPreview').style.display = 'none';
        }

        // Run filter
        function runFilter() {
            var data = {
                district: $('#f_district').val(),
                thana: $('#f_thana').val(),
                gender: $('#f_gender').val(),
                age_from: $('#f_age_from').val(),
                age_to: $('#f_age_to').val(),
                patient_type: $('#patient_type').val(),
                register_id: $('#f_register_id').val(),
                last_visit_months: $('#f_last_visit').val(),
                not_called_days: $('#f_not_called').val(),
                missed_followup: $('#f_missed_fu').val(),
                count: $('#f_count').val() || 100,
            };

            $('#filterResult').html('<i class="fas fa-spinner fa-spin"></i> Filtering...');
            $('#patientPreview').hide();

            $.get('{{ route("callcenter.admin.filter") }}', data, function(res) {
                var ids = res.patients.map(p => p.id);
                $('#filteredPatientIds').val(JSON.stringify(ids));

                if (res.patients.length > 0) {
                    var html = '';
                    res.patients.forEach(function(p, index) {
                        html += '<tr>' +
                            '<td><input type="checkbox" class="preview-chk" value="' + p.id + '" onchange="updatePreviewCount()"></td>' +
                            '<td>' + (p.register_id || p.id) + '</td>' +
                            '<td>' + p.name + '</td>' +
                            '<td>' + (p.phone || '—') + '</td>' +
                            '<td>' + (p.gender || '—') + '</td>' +
                            '</tr>';
                    });
                    $('#patientListBody').html(html);
                    $('#previewCount').text('Showing ' + res.patients.length + ' of ' + res.total + ' patients');
                    $('#patientPreview').show();
                    updatePreviewCount();

                    $('#filterResult').html(
                        '<span class="text-success font-weight-bold">' + res.total +
                        ' patients matched.</span> <span class="text-muted">(' + ids.length + ' total)</span> ' +
                        '<a href="{{ route("callcenter.admin.assign") }}" class="btn btn-link btn-sm p-0 ml-2">View All »</a>'
                    );
                } else {
                    $('#patientPreview').hide();
                    $('#filterResult').html('<span class="text-warning">No patients found matching your criteria.</span>');
                }
            }).fail(function() {
                $('#filterResult').html('<span class="text-danger">Filter failed. Please try again.</span>');
                $('#patientPreview').hide();
            });
        }

        // Preview table selection functions
        function updatePreviewCount() {
            var checked = document.querySelectorAll('.preview-chk:checked').length;
            var total = document.querySelectorAll('.preview-chk').length;
            document.getElementById('selectedCount').textContent = checked;

            // Update select all checkbox
            var chkAll = document.getElementById('chkAllPreview');
            if (chkAll) {
                chkAll.checked = checked === total && total > 0;
                chkAll.indeterminate = checked > 0 && checked < total;
            }
        }

        function toggleAllPreview(cb) {
            document.querySelectorAll('.preview-chk').forEach(c => c.checked = cb.checked);
            updatePreviewCount();
        }

        function selectAllPreview() {
            document.querySelectorAll('.preview-chk').forEach(c => c.checked = true);
            var chkAll = document.getElementById('chkAllPreview');
            if (chkAll) chkAll.checked = true;
            updatePreviewCount();
        }

        function deselectAllPreview() {
            document.querySelectorAll('.preview-chk').forEach(c => c.checked = false);
            var chkAll = document.getElementById('chkAllPreview');
            if (chkAll) chkAll.checked = false;
            updatePreviewCount();
        }

        // Assign tasks - uses only checked patients from preview
        function assignTasks() {
            var checkedIds = Array.from(document.querySelectorAll('.preview-chk:checked')).map(c => parseInt(c.value));
            var allIds = JSON.parse($('#filteredPatientIds').val() || '[]');

            // If no specific selections, use all filtered patients
            var ids = checkedIds.length > 0 ? checkedIds : allIds;

            if (!ids.length) {
                toastr.warning('Please select patients or run the filter first.');
                return;
            }

            $.post('{{ route("callcenter.admin.assign") }}', {
                _token: '{{ csrf_token() }}',
                patient_ids: ids,
                agent_id: $('#a_agent').val() || null,
                task_type: $('#a_task_type').val(),
                priority: $('#a_priority').val(),
                due_date: $('#a_due_date').val(),
                note: $('#a_note').val(),
                distribute: !$('#a_agent').val() ? 1 : 0,
            }, function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#filteredPatientIds').val('');
                    $('#filterResult').html('');
                    $('#patientPreview').hide();
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Assignment failed.');
            });
        }

        // Auto-refresh monitor every 30s
        setInterval(function() {
            if (document.getElementById('tab-monitor').classList.contains('active')) {
                location.reload();
            }
        }, 30000);
    </script>
@endsection