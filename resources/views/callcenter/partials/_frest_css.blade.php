{{-- Call Center Design System — single consolidated stylesheet + inline overrides. --}}
{{-- The external CSS file loads first, then inline <style> below overrides AdminLTE/Bootstrap. --}}
<link rel="stylesheet" href="{{ asset('css/callcenter/callcenter.css') }}?v={{ filemtime(public_path('css/callcenter/callcenter.css')) }}">
<style>
/* ═══════════════════════════════════════════════════════════════
   FREST CALL CENTER — INLINE OVERRIDES (wins over AdminLTE/Bootstrap)
   These !important rules ensure Frest styling always applies.
   ═══════════════════════════════════════════════════════════════ */

/* ── Design Tokens (declared once) ── */
:root{
  --cc-primary:#5a8dee;--cc-primary-light:rgba(90,141,238,0.12);--cc-primary-mid:rgba(90,141,238,0.2);
  --cc-secondary:#475f7b;
  --cc-success:#39da8a;--cc-success-light:rgba(57,218,138,0.12);
  --cc-danger:#ff5b5b;--cc-danger-light:rgba(255,91,91,0.12);
  --cc-warning:#fdac41;--cc-warning-light:rgba(253,172,65,0.12);
  --cc-info:#00cfdd;--cc-info-light:rgba(0,207,221,0.12);
  --cc-purple:#7367f0;--cc-purple-light:rgba(115,103,240,0.12);
  --cc-body:#f8f7fa;--cc-card:#fff;
  --cc-border:#ebebeb;--cc-border2:#dde3ec;
  --cc-shadow:0 4px 24px 0 rgba(34,41,47,0.08);
  --cc-shadow-sm:0 2px 8px 0 rgba(34,41,47,0.08);
  --cc-text:#475f7b;--cc-text-dark:#2c3e5d;
  --cc-text-muted:#828d99;--cc-text-light:#adb5bd;
  --cc-r:0.357rem;--cc-r2:0.5rem;--cc-r3:0.75rem;
  --cc-font:'IBM Plex Sans',sans-serif;--cc-font2:'Rubik',sans-serif;
}

/* ── Body font override ── */
.content-wrapper{font-family:var(--cc-font)!important;background:var(--cc-body)!important;color:var(--cc-text)!important}
.content-wrapper .content{font-family:var(--cc-font)!important}

/* ── .cc-topbar (compact KPI bar — used on tasks, calllogs, etc.) ── */
.cc-topbar{display:flex!important;align-items:center!important;gap:10px!important;margin-bottom:14px!important;flex-wrap:wrap!important}
.cc-actions{display:flex!important;gap:8px!important;margin-left:auto!important}

/* ── .kpi-chip (compact stat chip for topbar) ── */
.kpi-chip{display:flex!important;align-items:center!important;gap:7px!important;
  background:#fff!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;padding:6px 12px!important;
  font-size:12px!important;font-weight:500!important;color:var(--cc-text)!important;
  cursor:pointer!important;transition:all .2s!important;white-space:nowrap!important;
}
.kpi-chip:hover{border-color:var(--cc-primary)!important;background:var(--cc-primary-light)!important}
.kpi-chip .kn{font-weight:700!important;font-size:14px!important;font-family:var(--cc-font2)!important}
.kpi-chip.success .kn{color:var(--cc-success)!important}
.kpi-chip.warning .kn{color:var(--cc-warning)!important}
.kpi-chip.primary .kn{color:var(--cc-primary)!important}
.kpi-chip.danger .kn{color:var(--cc-danger)!important}
.kpi-chip.info .kn{color:var(--cc-info)!important}

/* ── .fcard (override AdminLTE .card) ── */
.fcard,div.fcard{
  background:var(--cc-card)!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r3)!important;box-shadow:var(--cc-shadow)!important;
  overflow:hidden!important;margin-bottom:0!important;
}
.fcard .fcard-head,.fcard .card-header{
  background:#fafafa!important;border-bottom:1px solid var(--cc-border)!important;
  padding:14px 18px!important;border-radius:var(--cc-r3) var(--cc-r3) 0 0!important;
}
.fcard .fcard-head h3,.fcard .card-title{
  font-size:14px!important;font-weight:600!important;color:var(--cc-text-dark)!important;
  margin:0!important;display:flex!important;align-items:center!important;gap:8px!important;
}
.fcard .fcard-head h3 i{color:var(--cc-primary)!important}
.fcard .fcard-body,.fcard .card-body{padding:16px!important}

/* ── .module-head ── */
.module-head{display:flex!important;justify-content:space-between!important;align-items:center!important;
  margin-bottom:16px!important;gap:10px!important;flex-wrap:wrap!important}
.module-head h2{font-size:17px!important;font-weight:600!important;color:var(--cc-text-dark)!important;
  margin:0!important;display:flex!important;align-items:center!important;gap:8px!important;font-family:var(--cc-font2)!important}
.module-head h2 i{color:var(--cc-primary)!important}

/* ── .btn-frest (override Bootstrap .btn) ── */
.btn-frest,button.btn-frest,a.btn-frest{
  padding:8px 16px!important;border-radius:var(--cc-r2)!important;border:none!important;
  cursor:pointer!important;font-size:13px!important;font-weight:500!important;
  font-family:var(--cc-font)!important;display:inline-flex!important;align-items:center!important;
  gap:6px!important;transition:all .2s!important;line-height:1!important;
  text-decoration:none!important;letter-spacing:0!important;text-transform:none!important;
}
.btn-frest.primary{background:var(--cc-primary)!important;color:#fff!important;box-shadow:0 4px 14px rgba(90,141,238,0.4)!important}
.btn-frest.primary:hover{background:#4a7fe0!important;color:#fff!important;text-decoration:none!important}
.btn-frest.success{background:var(--cc-success)!important;color:#fff!important;box-shadow:0 4px 14px rgba(57,218,138,0.35)!important}
.btn-frest.success:hover{background:#2ec97a!important;color:#fff!important}
.btn-frest.warning{background:var(--cc-warning)!important;color:#fff!important}
.btn-frest.warning:hover{background:#f09c2a!important;color:#fff!important}
.btn-frest.danger{background:var(--cc-danger-light)!important;border:1px solid rgba(255,91,91,0.2)!important;color:var(--cc-danger)!important}
.btn-frest.danger:hover{background:var(--cc-danger)!important;color:#fff!important}
.btn-frest.outline{background:#fff!important;border:1px solid var(--cc-border2)!important;color:var(--cc-text)!important}
.btn-frest.outline:hover{background:var(--cc-primary-light)!important;border-color:var(--cc-primary)!important;color:var(--cc-primary)!important;text-decoration:none!important}
.btn-frest.info{background:var(--cc-info-light)!important;border:1px solid rgba(0,207,221,0.2)!important;color:var(--cc-info)!important}
.btn-frest.info:hover{background:var(--cc-info)!important;color:#fff!important}
.btn-frest.sm{padding:5px 12px!important;font-size:12px!important}
.btn-frest.xs{padding:4px 10px!important;font-size:11px!important}
.btn-frest i{font-size:11px!important}

/* ── .fpill (override Bootstrap .badge) ── */
.fpill,.badge.fpill,span.fpill{
  padding:3px 10px!important;border-radius:20px!important;font-size:11px!important;
  font-weight:600!important;display:inline-flex!important;align-items:center!important;
  gap:3px!important;white-space:nowrap!important;line-height:1.5!important;text-decoration:none!important;
}
.fp-success,.badge.fp-success{background:var(--cc-success-light)!important;color:var(--cc-success)!important}
.fp-danger,.badge.fp-danger{background:var(--cc-danger-light)!important;color:var(--cc-danger)!important}
.fp-warning,.badge.fp-warning{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important}
.fp-primary,.badge.fp-primary{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.fp-info,.badge.fp-info{background:var(--cc-info-light)!important;color:var(--cc-info)!important}
.fp-secondary,.badge.fp-secondary{background:rgba(71,95,123,0.1)!important;color:var(--cc-secondary)!important}
.fp-purple,.badge.fp-purple{background:var(--cc-purple-light)!important;color:var(--cc-purple)!important}

/* ── .cc-stat-card ── */
.cc-stat-card,div.cc-stat-card{
  background:var(--cc-card)!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;padding:16px!important;cursor:pointer!important;
  transition:all .2s!important;box-shadow:var(--cc-shadow-sm)!important;text-align:center!important;position:relative!important;
}
.cc-stat-card:hover{box-shadow:var(--cc-shadow)!important;transform:translateY(-2px)!important}
.cc-stat-card .sc-icon{width:42px!important;height:42px!important;border-radius:var(--cc-r2)!important;
  display:flex!important;align-items:center!important;justify-content:center!important;font-size:18px!important;margin:0 auto 10px!important}
.cc-stat-card .sc-num{font-size:26px!important;font-weight:700!important;line-height:1!important;font-family:var(--cc-font2)!important}
.cc-stat-card .sc-label{font-size:11px!important;color:var(--cc-text-muted)!important;margin-top:4px!important;font-weight:500!important}
.cc-stat-card.success .sc-icon{background:var(--cc-success-light)!important;color:var(--cc-success)!important}
.cc-stat-card.success .sc-num{color:var(--cc-success)!important}
.cc-stat-card.warning .sc-icon{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important}
.cc-stat-card.warning .sc-num{color:var(--cc-warning)!important}
.cc-stat-card.primary .sc-icon{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.cc-stat-card.primary .sc-num{color:var(--cc-primary)!important}
.cc-stat-card.danger .sc-icon{background:var(--cc-danger-light)!important;color:var(--cc-danger)!important}
.cc-stat-card.danger .sc-num{color:var(--cc-danger)!important}
.cc-stat-card.info .sc-icon{background:var(--cc-info-light)!important;color:var(--cc-info)!important}
.cc-stat-card.info .sc-num{color:var(--cc-info)!important}

/* ── .stat-row grid ── */
.stat-row{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))!important;gap:10px!important;margin-bottom:14px!important}
@media(max-width:768px){.stat-row{grid-template-columns:repeat(2,1fr)!important}}
@media(max-width:480px){.stat-row{grid-template-columns:1fr!important}}

/* ── .filters-card + .filters-grid ── */
.filters-card{background:#fff!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;padding:14px 16px!important;margin-bottom:12px!important;box-shadow:var(--cc-shadow-sm)!important}
.filters-grid{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))!important;gap:10px!important;align-items:end!important}
.filters-grid .form-control,.filters-grid select,.filters-grid input{
  height:34px!important;font-size:12px!important;border-radius:var(--cc-r2)!important;
  border:1px solid var(--cc-border2)!important;padding:6px 10px!important;background:#fff!important;
  color:var(--cc-text-dark)!important;outline:none!important;transition:border-color .2s,box-shadow .2s!important;
}
.filters-grid .form-control:focus,.filters-grid select:focus,.filters-grid input:focus{
  border-color:var(--cc-primary)!important;box-shadow:0 0 0 3px rgba(90,141,238,.12)!important;outline:none!important;
}
.filter-label{font-size:11px!important;font-weight:600!important;color:var(--cc-text-muted)!important;
  margin-bottom:3px!important;display:block!important;text-transform:uppercase!important;letter-spacing:.3px!important}

/* ── Tables (override AdminLTE .table) ── */
#tasksTable,#calllogsTable,#followupTable,#smsTable,#lettersTable,#missingTable,.dtable{
  font-size:12px!important;width:100%!important;border-collapse:separate!important;border-spacing:0!important;
}
#tasksTable thead th,#calllogsTable thead th,#followupTable thead th,
#smsTable thead th,#lettersTable thead th,#missingTable thead th,.dtable th{
  background:#fafafa!important;color:var(--cc-text-muted)!important;font-size:11px!important;
  font-weight:600!important;text-transform:uppercase!important;letter-spacing:.5px!important;
  padding:10px 12px!important;border-bottom:2px solid var(--cc-border)!important;border-top:none!important;
  text-align:left!important;white-space:nowrap!important;
}
#tasksTable tbody td,#calllogsTable tbody td,#followupTable tbody td,
#smsTable tbody td,#lettersTable tbody td,#missingTable tbody td,.dtable td{
  padding:10px 12px!important;vertical-align:middle!important;color:var(--cc-text)!important;
  border-top:1px solid var(--cc-border)!important;border-bottom:none!important;
}
#tasksTable tbody tr:hover td,#calllogsTable tbody tr:hover td,
#followupTable tbody tr:hover td,#smsTable tbody tr:hover td,
#lettersTable tbody tr:hover td,#missingTable tbody tr:hover td,.dtable tr:hover td{
  background:rgba(90,141,238,.04)!important;
}
.dtable .td-name{font-weight:600!important;font-size:13px!important;color:var(--cc-text-dark)!important}
.dtable .td-sub{font-size:11px!important;color:var(--cc-text-muted)!important;margin-top:2px!important}
.dtable a{color:var(--cc-primary)!important;text-decoration:none!important;font-weight:600!important;cursor:pointer!important}
.dtable a:hover{text-decoration:underline!important}

/* ── .btn-icon ── */
.btn-icon{width:28px!important;height:28px!important;padding:0!important;
  display:inline-flex!important;align-items:center!important;justify-content:center!important;
  border-radius:6px!important;border:none!important;cursor:pointer!important;transition:all .2s!important;
  font-size:11px!important;text-decoration:none!important;
}
.btn-icon.outline{background:rgba(71,95,123,.08)!important;color:var(--cc-text-muted)!important}
.btn-icon.outline:hover{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.btn-icon.primary{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.btn-icon.primary:hover{background:var(--cc-primary)!important;color:#fff!important}
.btn-icon.success{background:var(--cc-success-light)!important;color:var(--cc-success)!important}
.btn-icon.success:hover{background:var(--cc-success)!important;color:#fff!important}
.btn-icon.warning{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important}
.btn-icon.warning:hover{background:var(--cc-warning)!important;color:#fff!important}
.btn-icon.danger{background:var(--cc-danger-light)!important;color:var(--cc-danger)!important}
.btn-icon.danger:hover{background:var(--cc-danger)!important;color:#fff!important}
.btn-icon.info{background:var(--cc-info-light)!important;color:var(--cc-info)!important}
.btn-icon.info:hover{background:var(--cc-info)!important;color:#fff!important}

/* ── .cc-empty ── */
.cc-empty{text-align:center!important;padding:60px 20px!important;color:var(--cc-text-light)!important}
.cc-empty i{font-size:40px!important;margin-bottom:12px!important;opacity:.2!important;display:block!important}
.cc-empty span{font-size:13px!important;color:var(--cc-text-muted)!important}

/* ── .tca ── */
.tca{padding:5px 12px!important;border-radius:var(--cc-r)!important;border:1px solid!important;
  cursor:pointer!important;font-size:11px!important;font-weight:500!important;transition:all .2s!important;
  display:inline-flex!important;align-items:center!important;gap:4px!important;background:none!important;text-decoration:none!important;
}
.tca.success{background:var(--cc-success-light)!important;color:var(--cc-success)!important;border-color:rgba(57,218,138,0.2)!important}
.tca.success:hover{background:var(--cc-success)!important;color:#fff!important}
.tca.primary{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important;border-color:rgba(90,141,238,0.2)!important}
.tca.primary:hover{background:var(--cc-primary)!important;color:#fff!important}
.tca.warning{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important;border-color:rgba(253,172,65,0.2)!important}
.tca.warning:hover{background:var(--cc-warning)!important;color:#fff!important}
.tca.secondary{background:var(--cc-body)!important;color:var(--cc-text)!important;border-color:var(--cc-border)!important}
.tca.secondary:hover{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}

/* ── .pac ── */
.pac{padding:7px 14px!important;border-radius:var(--cc-r2)!important;border:1px solid!important;
  cursor:pointer!important;font-size:12px!important;font-weight:500!important;
  display:inline-flex!important;align-items:center!important;gap:6px!important;transition:all .2s!important;text-decoration:none!important;
}
.pac.success{background:var(--cc-success-light)!important;color:var(--cc-success)!important;border-color:rgba(57,218,138,0.2)!important}
.pac.success:hover{background:var(--cc-success)!important;color:#fff!important}
.pac.primary{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important;border-color:rgba(90,141,238,0.2)!important}
.pac.primary:hover{background:var(--cc-primary)!important;color:#fff!important}
.pac.warning{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important;border-color:rgba(253,172,65,0.2)!important}
.pac.warning:hover{background:var(--cc-warning)!important;color:#fff!important}
.pac.danger{background:var(--cc-danger-light)!important;color:var(--cc-danger)!important;border-color:rgba(255,91,91,0.2)!important}
.pac.danger:hover{background:var(--cc-danger)!important;color:#fff!important}
.pac.secondary{background:var(--cc-body)!important;color:var(--cc-text)!important;border-color:var(--cc-border2)!important}
.pac.secondary:hover{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important;border-color:rgba(90,141,238,0.2)!important}

/* ── Admin: KPI cards ── */
.kpi-grid{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))!important;gap:12px!important;margin-bottom:16px!important}
.kpi-card{background:var(--cc-card)!important;border-radius:var(--cc-r2)!important;padding:16px!important;
  border:1px solid var(--cc-border)!important;display:flex!important;align-items:center!important;gap:14px!important;
  transition:all .2s!important;box-shadow:var(--cc-shadow-sm)!important;
}
.kpi-card:hover{box-shadow:var(--cc-shadow)!important;transform:translateY(-2px)!important}
.kpi-icon{width:44px!important;height:44px!important;border-radius:var(--cc-r2)!important;
  display:flex!important;align-items:center!important;justify-content:center!important;font-size:18px!important;flex-shrink:0!important;
}
.kpi-icon.bg-primary-light{background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.kpi-icon.bg-success-light{background:var(--cc-success-light)!important;color:var(--cc-success)!important}
.kpi-icon.bg-warning-light{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important}
.kpi-icon.bg-danger-light{background:var(--cc-danger-light)!important;color:var(--cc-danger)!important}
.kpi-value{font-size:24px!important;font-weight:700!important;color:var(--cc-text-dark)!important;line-height:1.2!important;font-family:var(--cc-font2)!important}
.kpi-label{font-size:11px!important;color:var(--cc-text-muted)!important;margin-top:2px!important;font-weight:500!important}

/* ── Admin tabs ── */
.adm-tabs{display:flex!important;gap:6px!important;background:#fff!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;padding:4px!important;margin-bottom:16px!important;flex-wrap:wrap!important;
}
.adm-tab{padding:7px 14px!important;border:none!important;border-radius:var(--cc-r)!important;background:transparent!important;
  color:var(--cc-text-muted)!important;font-weight:500!important;font-size:12px!important;font-family:var(--cc-font)!important;
  cursor:pointer!important;transition:all .2s!important;display:flex!important;align-items:center!important;gap:6px!important;
}
.adm-tab:hover:not(.active){background:var(--cc-primary-light)!important;color:var(--cc-primary)!important}
.adm-tab.active{background:var(--cc-primary)!important;color:#fff!important;box-shadow:0 4px 12px rgba(90,141,238,.35)!important}
.adm-tab .badge-pill{background:var(--cc-danger)!important;color:#fff!important;font-size:10px!important;
  padding:2px 8px!important;border-radius:20px!important;font-weight:700!important;min-width:18px!important;text-align:center!important;
}
.adm-tab .badge-pill.bg-success{background:var(--cc-success)!important}
.adm-tab.active .badge-pill{background:rgba(255,255,255,.25)!important}

/* ── Admin filter-grid ── */
.filter-grid{display:grid!important;grid-template-columns:repeat(3,1fr)!important;gap:10px!important;margin-bottom:14px!important}
@media(max-width:768px){.filter-grid{grid-template-columns:1fr 1fr!important}}
@media(max-width:480px){.filter-grid{grid-template-columns:1fr!important}}
.filter-group{display:flex!important;flex-direction:column!important;gap:3px!important}

/* ── Admin agent-rank-card ── */
.agent-rank-card{display:flex!important;align-items:center!important;gap:12px!important;padding:12px 14px!important;
  border:1px solid var(--cc-border)!important;border-left:3px solid var(--cc-border2)!important;
  border-radius:var(--cc-r2)!important;margin-bottom:8px!important;background:#fff!important;transition:all .2s!important;
}
.agent-rank-card:hover{box-shadow:var(--cc-shadow-sm)!important}
.agent-rank-card.border-warning{border-left-color:var(--cc-warning)!important}
.agent-rank-card.border-secondary{border-left-color:var(--cc-text-light)!important}
.agent-rank-card.border-info{border-left-color:var(--cc-info)!important}
.agent-rank-card.border-light{border-left-color:var(--cc-border2)!important}
.agent-avatar{width:42px!important;height:42px!important;border-radius:50%!important;
  background:linear-gradient(135deg,var(--cc-primary),var(--cc-purple))!important;
  display:flex!important;align-items:center!important;justify-content:center!important;
  font-weight:700!important;font-size:14px!important;color:#fff!important;flex-shrink:0!important;
}
.agent-info{flex:1!important;min-width:0!important}
.agent-name{font-weight:600!important;font-size:13px!important;color:var(--cc-text-dark)!important}
.agent-rank-badge{font-size:10px!important;font-weight:700!important;padding:2px 8px!important;border-radius:20px!important}
.agent-rank-badge.badge-warning{background:var(--cc-warning-light)!important;color:var(--cc-warning)!important}
.agent-rank-badge.badge-secondary{background:rgba(130,141,153,.12)!important;color:var(--cc-text-muted)!important}
.agent-rank-badge.badge-info{background:var(--cc-info-light)!important;color:var(--cc-info)!important}
.agent-rank-badge.badge-light{background:var(--cc-body)!important;color:var(--cc-text-muted)!important;border:1px solid var(--cc-border)!important}
.agent-stats{display:flex!important;gap:12px!important;margin-top:8px!important}
.agent-stat{text-align:center!important}
.agent-stat .value{font-size:15px!important;font-weight:700!important;color:var(--cc-text-dark)!important;font-family:var(--cc-font2)!important}
.agent-stat .label{font-size:10px!important;color:var(--cc-text-muted)!important;margin-top:2px!important}
.progress-sm{height:4px!important;background:var(--cc-border)!important;border-radius:4px!important;overflow:hidden!important;margin-top:6px!important}
.progress-sm .fill{height:100%!important;background:linear-gradient(90deg,var(--cc-primary),var(--cc-purple))!important;border-radius:4px!important;transition:width .6s ease!important}

/* ── my_stats month-bar ── */
.month-bar-row{display:flex!important;align-items:center!important;gap:10px!important;padding:8px 0!important;
  border-bottom:1px solid var(--cc-border)!important;font-size:12px!important}
.month-bar-row:last-child{border-bottom:none!important}
.month-bar-bg{flex:1!important;background:var(--cc-border)!important;border-radius:3px!important;overflow:hidden!important;height:8px!important}
.month-bar-fill{height:100%!important;border-radius:3px!important;
  background:linear-gradient(90deg,var(--cc-primary),var(--cc-purple))!important;transition:width .6s ease!important}

/* ── .ttab ── */
.ttab{padding:7px 16px!important;border-radius:var(--cc-r2)!important;border:1px solid var(--cc-border)!important;
  background:#fff!important;font-size:12px!important;font-weight:600!important;color:var(--cc-text-muted)!important;
  cursor:pointer!important;transition:all .2s!important;text-decoration:none!important;white-space:nowrap!important;
  display:inline-flex!important;align-items:center!important;gap:5px!important;
}
.ttab:hover{border-color:var(--cc-primary)!important;color:var(--cc-primary)!important;
  background:var(--cc-primary-light)!important;text-decoration:none!important}
.ttab.active{background:var(--cc-primary)!important;color:#fff!important;border-color:var(--cc-primary)!important;
  box-shadow:0 4px 12px rgba(90,141,238,.35)!important}

/* ── .tk-card ── */
.tk-card{background:#fff!important;border:1px solid var(--cc-border)!important;border-radius:var(--cc-r2)!important;
  padding:12px 14px!important;margin-bottom:8px!important;cursor:pointer!important;transition:all .2s!important;
  border-left:3px solid!important;position:relative!important}
.tk-card:hover{box-shadow:var(--cc-shadow-sm)!important;transform:translateX(2px)!important}
.tk-card.hp{border-left-color:var(--cc-danger)!important}
.tk-card.mp{border-left-color:var(--cc-warning)!important}
.tk-card.lp{border-left-color:var(--cc-success)!important}

/* ── .status-dot-cc ── */
.status-dot-cc{width:8px!important;height:8px!important;border-radius:50%!important;display:inline-block!important;margin-right:5px!important}
.status-dot-cc.online{background:var(--cc-success)!important;box-shadow:0 0 0 2px rgba(57,218,138,0.25)!important;animation:ccpulse 2s infinite!important}
.status-dot-cc.offline{background:var(--cc-text-light)!important}

/* ── .select-bar / .bulk-bar ── */
.select-bar{display:flex!important;align-items:center!important;gap:10px!important;padding:10px 14px!important;
  background:var(--cc-primary-light)!important;border:1px solid rgba(90,141,238,0.2)!important;
  border-radius:var(--cc-r2)!important;margin-bottom:10px!important;font-size:12px!important}
.bulk-bar{background:var(--cc-primary-light)!important;border:1px solid rgba(90,141,238,.2)!important;
  border-radius:var(--cc-r2)!important;padding:10px 14px!important;margin-bottom:10px!important;
  display:flex!important;justify-content:space-between!important;align-items:center!important;font-size:12px!important}

/* ── .prio-dot ── */
.prio-dot{width:8px!important;height:8px!important;border-radius:50%!important;display:inline-block!important;flex-shrink:0!important}
.prio-dot.high{background:var(--cc-danger)!important}
.prio-dot.medium{background:var(--cc-warning)!important}
.prio-dot.low{background:var(--cc-success)!important}

/* ── .overdue-row ── */
.overdue-row td{background:rgba(255,91,91,.03)!important}

/* ── .ch-item ── */
.ch-item{padding:12px 14px!important;background:#fff!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;margin-bottom:8px!important;transition:all .2s!important}
.ch-item:hover{box-shadow:var(--cc-shadow-sm)!important}
.ch-item .ch-top{display:flex!important;justify-content:space-between!important;align-items:center!important;margin-bottom:6px!important}
.ch-item .ch-note{font-size:12px!important;color:var(--cc-text-muted)!important;line-height:1.5!important}
.ch-item .ch-meta{font-size:11px!important;color:var(--cc-text-light)!important;display:flex!important;gap:12px!important;margin-top:6px!important;flex-wrap:wrap!important}

/* ── .tl-item ── */
.tl-item{padding:12px 14px!important;background:#fff!important;border:1px solid var(--cc-border)!important;
  border-radius:var(--cc-r2)!important;margin-bottom:8px!important;border-left:3px solid var(--cc-primary)!important;transition:all .2s!important}
.tl-item:hover{box-shadow:var(--cc-shadow-sm)!important}
.tl-item.critical{border-left-color:var(--cc-danger)!important;background:rgba(255,91,91,0.02)!important}
.tl-item.warning{border-left-color:var(--cc-warning)!important}
.tl-title{font-weight:600!important;font-size:13px!important;color:var(--cc-text-dark)!important}
.tl-sub{font-size:12px!important;color:var(--cc-text-muted)!important;line-height:1.5!important;margin-top:4px!important}
.tl-meta{font-size:11px!important;color:var(--cc-text-light)!important;margin-top:6px!important;display:flex!important;gap:12px!important;flex-wrap:wrap!important}

/* ── Modals ── */
.modal-content{border:none!important;border-radius:var(--cc-r3)!important;box-shadow:0 24px 48px rgba(0,0,0,.18)!important;overflow:hidden!important}
.modal-header{border-bottom:1px solid var(--cc-border)!important;padding:16px 20px!important;border-radius:var(--cc-r3) var(--cc-r3) 0 0!important}
.modal-header.bg-primary{background:var(--cc-primary)!important;color:#fff!important}
.modal-header.bg-success{background:var(--cc-success)!important;color:#fff!important}
.modal-header.bg-warning{background:var(--cc-warning)!important;color:#fff!important}
.modal-header.bg-info{background:var(--cc-info)!important;color:#fff!important}
.modal-header .modal-title{font-size:15px!important;font-weight:600!important;display:flex!important;align-items:center!important;gap:8px!important;font-family:var(--cc-font2)!important}
.modal-header .close{color:#fff!important;opacity:.8!important;text-shadow:none!important;font-size:22px!important}
.modal-header .close:hover{opacity:1!important}
.modal-body{padding:20px!important}
.modal-footer{padding:12px 20px!important;border-top:1px solid var(--cc-border)!important;
  border-radius:0 0 var(--cc-r3) var(--cc-r3)!important;background:#fafafa!important}
.modal .form-control,.modal select,.modal input,.modal textarea{
  border:1px solid var(--cc-border2)!important;border-radius:var(--cc-r2)!important;padding:8px 12px!important;
  font-size:13px!important;color:var(--cc-text-dark)!important;font-family:var(--cc-font)!important;
  outline:none!important;transition:border-color .2s,box-shadow .2s!important;
}
.modal .form-control:focus,.modal select:focus,.modal input:focus,.modal textarea:focus{
  border-color:var(--cc-primary)!important;box-shadow:0 0 0 3px rgba(90,141,238,.12)!important}
.modal label{font-size:12px!important;font-weight:600!important;color:var(--cc-text)!important;margin-bottom:4px!important}

/* ── DataTables ── */
.dataTables_wrapper .dataTables_length select,.dataTables_wrapper .dataTables_filter input{
  height:32px!important;border-radius:var(--cc-r2)!important;border:1px solid var(--cc-border2)!important;
  padding:4px 10px!important;font-size:12px!important;outline:none!important;
}
.dataTables_wrapper .dataTables_filter input:focus{border-color:var(--cc-primary)!important;box-shadow:0 0 0 3px rgba(90,141,238,.12)!important}
.dataTables_wrapper .dataTables_paginate .paginate_button{padding:5px 10px!important;border-radius:var(--cc-r)!important;
  font-size:12px!important;border:1px solid transparent!important;margin:0 2px!important}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:var(--cc-primary-light)!important;
  border-color:transparent!important;color:var(--cc-primary)!important}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover{
  background:var(--cc-primary)!important;border-color:var(--cc-primary)!important;color:#fff!important}
.dataTables_wrapper .dataTables_info,.dataTables_wrapper label{font-size:11px!important;color:var(--cc-text-muted)!important}

/* ── .fade-in ── */
.fade-in{animation:ccFadeIn .25s ease!important}
@keyframes ccFadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
@keyframes ccpulse{0%,100%{opacity:1}50%{opacity:.4}}
</style>
