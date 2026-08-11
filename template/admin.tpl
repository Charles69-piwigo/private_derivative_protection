<style>
  .pdp-tabs { margin:0 0 0 10px; border-bottom:2px solid #ccc; text-align:left; }
  .pdp-tabs a { display:inline-block; padding:6px 16px; text-decoration:none; color:#555; border:1px solid transparent; border-bottom:none; border-radius:4px 4px 0 0; margin-bottom:-2px; font-size:0.95em; }
  .pdp-tabs a.active { background:#fff; border-color:#ccc; color:#000; font-weight:bold; border-bottom-color:#fff; }
  .pdp-tabs a:hover:not(.active) { background:#f0f0f0; }
  .pdp-status { margin:0 0 2em 20px; text-align:left; }
  .pdp-status table { border-collapse:collapse; }
  .pdp-status td { padding:5px 16px 5px 0; vertical-align:middle; }
  .pdp-status h3 { margin:1.4em 0 0.4em 0; color:#333; border-bottom:1px solid #eee; padding-bottom:2px; }
  .pdp-ok    { color:#2a7; font-weight:bold; font-size:1.1em; }
  .pdp-warn  { color:#c00; font-weight:bold; font-size:1.1em; }
  .pdp-nginx-warn { background:#fff3cd; border:1px solid #ffc107; border-radius:4px; padding:10px 14px; margin:10px 0 14px 0; color:#856404; }
  .pdp-help { margin:0 0 2em 20px; text-align:left; max-width:900px; }
  .pdp-help h3 { margin:1.6em 0 0.5em 0; color:#333; border-bottom:1px solid #eee; padding-bottom:2px; }
  .pdp-help h4 { margin:1.2em 0 0.4em 0; color:#444; }
  .pdp-help p, .pdp-help li { line-height:1.5; }
  .pdp-help ul { margin:0.3em 0 0.8em 0; padding-left:1.4em; }
  .pdp-help li { margin-bottom:0.3em; }
  .pdp-help code { background:#f4f4f4; border:1px solid #e0e0e0; border-radius:3px; padding:1px 5px; font-size:0.92em; }
  .pdp-help .pdp-note { background:#f0f7ff; border:1px solid #cfe3fb; border-radius:4px; padding:10px 14px; margin:0.8em 0; color:#2c5a8c; }
</style>

<div class="titrePage">
  <h2>{'Private Derivative Protection'|@translate}</h2>
</div>

<!-- ── Onglets ─────────────────────────────────────────────────────────────── -->
<div class="pdp-tabs">
  <a href="{$BASE_URL|escape}" {if $TAB eq 'status'}class="active"{/if}>{'État'|@translate}</a>
  <a href="{$BASE_URL|escape}&amp;tab=help" {if $TAB eq 'help'}class="active"{/if}>{'Aide'|@translate}</a>
</div>

{$TAB_CONTENT}
