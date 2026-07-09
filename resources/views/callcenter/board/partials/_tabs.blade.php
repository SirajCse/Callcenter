{{-- Combined tabs content for AJAX patient loading --}}
<div class="tab-pane fade show active cc-tab-body" id="tab-appt">
    @include('callcenter.board.partials._tab_appointments')
</div>
<div class="tab-pane fade cc-tab-body" id="tab-calls">
    @include('callcenter.board.partials._tab_calllogs')
</div>
<div class="tab-pane fade cc-tab-body" id="tab-lab">
    @include('callcenter.board.partials._tab_lab')
</div>
<div class="tab-pane fade cc-tab-body" id="tab-therapy">
    @include('callcenter.board.partials._tab_therapy')
</div>
<div class="tab-pane fade cc-tab-body" id="tab-neb">
    @include('callcenter.board.partials._tab_nebulize')
</div>
<div class="tab-pane fade cc-tab-body" id="tab-vac">
    @include('callcenter.board.partials._tab_vaccination')
</div>
