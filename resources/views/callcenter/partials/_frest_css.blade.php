{{-- Shared Frest Design System CSS for all CallCenter views --}}
<style>
/* ══════════════════════════════════════════════════════════
   FREST CALL CENTER — Shared Design Tokens
   Primary: #5a8dee | Pixinvent Frest style
   ══════════════════════════════════════════════════════════ */
:root {
  --cc-primary:#5a8dee; --cc-primary-light:rgba(90,141,238,0.12); --cc-primary-mid:rgba(90,141,238,0.2);
  --cc-secondary:#475f7b;
  --cc-success:#39da8a; --cc-success-light:rgba(57,218,138,0.12);
  --cc-danger:#ff5b5b;  --cc-danger-light:rgba(255,91,91,0.12);
  --cc-warning:#fdac41; --cc-warning-light:rgba(253,172,65,0.12);
  --cc-info:#00cfdd;    --cc-info-light:rgba(0,207,221,0.12);
  --cc-purple:#7367f0;  --cc-purple-light:rgba(115,103,240,0.12);
  --cc-body:#f8f7fa; --cc-card:#fff;
  --cc-border:#ebebeb; --cc-border2:#dde3ec;
  --cc-shadow:0 4px 24px 0 rgba(34,41,47,0.08);
  --cc-shadow-sm:0 2px 8px 0 rgba(34,41,47,0.08);
  --cc-text:#475f7b; --cc-text-dark:#2c3e5d;
  --cc-text-muted:#828d99; --cc-text-light:#adb5bd;
  --cc-r:0.357rem; --cc-r2:0.5rem; --cc-r3:0.75rem;
}

/* ── Cards ─────────────────────────────────────────────── */
.fcard{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r3);box-shadow:var(--cc-shadow);overflow:hidden}
.fcard-head{padding:14px 18px;border-bottom:1px solid var(--cc-border);display:flex;justify-content:space-between;align-items:center;background:#fafafa}
.fcard-head h3{font-size:14px;font-weight:600;color:var(--cc-text-dark);margin:0;display:flex;align-items:center;gap:8px}
.fcard-head h3 i{color:var(--cc-primary)}
.fcard-body{padding:16px}

/* ── Module header ─────────────────────────────────────── */
.module-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.module-head h2{font-size:17px;font-weight:600;color:var(--cc-text-dark);margin:0;display:flex;align-items:center;gap:8px}
.module-head h2 i{color:var(--cc-primary)}

/* ── Buttons ───────────────────────────────────────────── */
.btn-frest{padding:8px 16px;border-radius:var(--cc-r2);border:none;cursor:pointer;font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px;transition:all .2s;line-height:1;text-decoration:none}
.btn-frest.primary{background:var(--cc-primary);color:#fff;box-shadow:0 4px 14px rgba(90,141,238,0.4)}
.btn-frest.primary:hover{background:#4a7fe0;color:#fff;text-decoration:none}
.btn-frest.success{background:var(--cc-success);color:#fff}
.btn-frest.success:hover{background:#2ec97a;color:#fff;text-decoration:none}
.btn-frest.warning{background:var(--cc-warning);color:#fff}
.btn-frest.warning:hover{background:#f09c2a;color:#fff;text-decoration:none}
.btn-frest.danger{background:var(--cc-danger-light);border:1px solid rgba(255,91,91,0.2);color:var(--cc-danger)}
.btn-frest.danger:hover{background:var(--cc-danger);color:#fff;text-decoration:none}
.btn-frest.outline{background:#fff;border:1px solid var(--cc-border2);color:var(--cc-text)}
.btn-frest.outline:hover{background:var(--cc-primary-light);border-color:var(--cc-primary);color:var(--cc-primary);text-decoration:none}
.btn-frest.info{background:var(--cc-info-light);border:1px solid rgba(0,207,221,0.2);color:var(--cc-info)}
.btn-frest.info:hover{background:var(--cc-info);color:#fff;text-decoration:none}
.btn-frest i{font-size:11px}
.btn-frest.sm{padding:5px 12px;font-size:12px}
.btn-frest.xs{padding:4px 10px;font-size:11px}

/* ── Pill badges ───────────────────────────────────────── */
.fpill{padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px;white-space:nowrap}
.fp-success{background:var(--cc-success-light);color:var(--cc-success)}
.fp-danger{background:var(--cc-danger-light);color:var(--cc-danger)}
.fp-warning{background:var(--cc-warning-light);color:var(--cc-warning)}
.fp-primary{background:var(--cc-primary-light);color:var(--cc-primary)}
.fp-info{background:var(--cc-info-light);color:var(--cc-info)}
.fp-secondary{background:rgba(71,95,123,0.1);color:var(--cc-secondary)}
.fp-purple{background:var(--cc-purple-light);color:var(--cc-purple)}

/* ── Data table ────────────────────────────────────────── */
.dtable{width:100%;border-collapse:separate;border-spacing:0;font-size:12px}
.dtable th{background:#fafafa;padding:10px 14px;text-align:left;font-weight:600;color:var(--cc-text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--cc-border)}
.dtable td{padding:12px 14px;border-bottom:1px solid var(--cc-border);color:var(--cc-text);vertical-align:middle}
.dtable tr:last-child td{border-bottom:none}
.dtable tr:hover td{background:rgba(90,141,238,0.03)}
.dtable .td-name{font-weight:600;font-size:13px;color:var(--cc-text-dark)}
.dtable .td-sub{font-size:11px;color:var(--cc-text-muted);margin-top:2px}

/* ── Filter bar ────────────────────────────────────────── */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;padding:12px 16px;background:#fafafa;border-bottom:1px solid var(--cc-border)}
.filter-bar .form-control,.filter-bar select,.filter-bar input{background:#fff;border:1px solid var(--cc-border2);border-radius:var(--cc-r2);padding:7px 12px;color:var(--cc-text);font-size:12px;outline:none;height:34px}
.filter-bar .form-control:focus,.filter-bar select:focus,.filter-bar input:focus{border-color:var(--cc-primary);box-shadow:0 0 0 3px rgba(90,141,238,0.12)}
.filter-label{font-size:11px;font-weight:600;color:var(--cc-text-muted);margin-bottom:3px;display:block}

/* ── Select bar (bulk actions) ─────────────────────────── */
.select-bar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--cc-primary-light);border:1px solid rgba(90,141,238,0.2);border-radius:var(--cc-r2);margin-bottom:10px;font-size:12px}
.select-bar input[type=checkbox]{accent-color:var(--cc-primary);width:14px;height:14px}

/* ── Follow-up row ─────────────────────────────────────── */
.fu-row{transition:background .15s}
.fu-row:hover td{background:rgba(90,141,238,0.04)!important}
.fu-name{font-weight:600;font-size:13px;color:var(--cc-text-dark)}
.fu-sub{font-size:11px;color:var(--cc-text-muted);margin-top:2px}

/* ── Call history item ─────────────────────────────────── */
.ch-item{padding:12px 14px;background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);margin-bottom:8px;transition:all .2s}
.ch-item:hover{box-shadow:var(--cc-shadow-sm)}
.ch-item .ch-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.ch-item .ch-note{font-size:12px;color:var(--cc-text-muted);line-height:1.5}
.ch-item .ch-meta{font-size:11px;color:var(--cc-text-light);display:flex;gap:12px;margin-top:6px;flex-wrap:wrap}

/* ── Stat card ─────────────────────────────────────────── */
.cc-stat-card{background:var(--cc-card);border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:16px;cursor:pointer;transition:all .2s;box-shadow:var(--cc-shadow-sm);text-align:center}
.cc-stat-card:hover{box-shadow:var(--cc-shadow);transform:translateY(-1px)}
.cc-stat-card .sc-icon{width:42px;height:42px;border-radius:var(--cc-r2);display:flex;align-items:center;justify-content:center;font-size:18px;margin:0 auto 10px}
.cc-stat-card .sc-num{font-size:26px;font-weight:700;line-height:1}
.cc-stat-card .sc-label{font-size:11px;color:var(--cc-text-muted);margin-top:4px;font-weight:500}
.cc-stat-card.success .sc-icon{background:var(--cc-success-light);color:var(--cc-success)}
.cc-stat-card.success .sc-num{color:var(--cc-success)}
.cc-stat-card.warning .sc-icon{background:var(--cc-warning-light);color:var(--cc-warning)}
.cc-stat-card.warning .sc-num{color:var(--cc-warning)}
.cc-stat-card.primary .sc-icon{background:var(--cc-primary-light);color:var(--cc-primary)}
.cc-stat-card.primary .sc-num{color:var(--cc-primary)}
.cc-stat-card.danger .sc-icon{background:var(--cc-danger-light);color:var(--cc-danger)}
.cc-stat-card.danger .sc-num{color:var(--cc-danger)}
.cc-stat-card.info .sc-icon{background:var(--cc-info-light);color:var(--cc-info)}
.cc-stat-card.info .sc-num{color:var(--cc-info)}

/* ── Agent card ────────────────────────────────────────── */
.agent-card{background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:16px;transition:all .2s;box-shadow:var(--cc-shadow-sm)}
.agent-card:hover{box-shadow:var(--cc-shadow)}
.ag-av{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#fff;flex-shrink:0;background:linear-gradient(135deg,var(--cc-primary),var(--cc-purple))}
.ag-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:10px}
.ag-stat{text-align:center;background:var(--cc-body);padding:8px 4px;border-radius:var(--cc-r)}
.ag-stat .v{font-size:15px;font-weight:700;color:var(--cc-primary)}
.ag-stat .k{font-size:10px;color:var(--cc-text-muted);margin-top:2px;font-weight:500}
.rank-badge{padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700}
.rank-1{background:var(--cc-warning-light);color:var(--cc-warning)}
.rank-2{background:rgba(130,141,153,0.12);color:var(--cc-text-muted)}
.rank-3{background:rgba(180,140,100,0.12);color:#b48c64}
.progress-bar-cc{height:4px;background:var(--cc-border);border-radius:4px;margin-top:10px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--cc-primary),var(--cc-purple));border-radius:4px;transition:width .6s ease}

/* ── Admin tabs ────────────────────────────────────────── */
.adm-tabs{display:flex;gap:6px;margin-bottom:16px;background:#fff;border:1px solid var(--cc-border);border-radius:var(--cc-r2);padding:4px}
.adm-tab{padding:7px 14px;border-radius:var(--cc-r);background:transparent;border:none;color:var(--cc-text-muted);cursor:pointer;font-size:12px;font-weight:500;transition:all .2s}
.adm-tab.active{background:var(--cc-primary);color:#fff;box-shadow:0 4px 12px rgba(90,141,238,0.35)}
.adm-tab:hover:not(.active){background:var(--cc-primary-light);color:var(--cc-primary)}
.adm-panel{display:none}
.adm-panel.active{display:block}

/* ── Status dot ────────────────────────────────────────── */
.status-dot-cc{width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px}
.status-dot-cc.online{background:var(--cc-success);box-shadow:0 0 0 2px rgba(57,218,138,0.25);animation:ccpulse 2s infinite}
.status-dot-cc.offline{background:var(--cc-text-light)}
@keyframes ccpulse{0%,100%{opacity:1}50%{opacity:.4}}

/* ── Empty state ───────────────────────────────────────── */
.cc-empty{text-align:center;padding:40px 20px;color:var(--cc-text-light)}
.cc-empty i{font-size:32px;margin-bottom:10px;opacity:.25;display:block}
.cc-empty span{font-size:13px}

/* ── Scrollbar ─────────────────────────────────────────── */
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#dee2e6;border-radius:10px}
::-webkit-scrollbar-thumb:hover{background:#adb5bd}

/* ── Fade in ───────────────────────────────────────────── */
.fade-in{animation:ccFadeIn .25s ease}
@keyframes ccFadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
</style>
