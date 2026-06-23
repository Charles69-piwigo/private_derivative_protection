<div class="pdp-status">

  <!-- ── Serveur ───────────────────────────────────────────────────────────── -->
  <h3>{'Serveur'|@translate}</h3>

  {if $SERVER_IS_NGINX}
  <div class="pdp-nginx-warn">
    <strong>&#9888; {'pdp_nginx_config_warning'|@translate}</strong><br>
    <a href="{$PLUGINS_URL|escape}" style="color:#856404;">
      &#8594; {'Gérer les plugins'|@translate}
    </a>
  </div>
  {else}
  <table>
    <tr>
      <td><span class="pdp-ok">&#10003;</span></td>
      <td>{'Serveur compatible (Apache)'|@translate}</td>
    </tr>
  </table>
  {/if}

  <!-- ── Protection des fichiers originaux ─────────────────────────────────── -->
  <h3>{'Protection des fichiers originaux'|@translate}</h3>

  <table>
    <tr>
      <td>
        {if $HTACCESS_GALLERIES_OK}
          <span class="pdp-ok">&#10003;</span>
        {else}
          <span class="pdp-warn">&#10007;</span>
        {/if}
      </td>
      <td>galleries/.htaccess</td>
      <td style="padding-left:12px;color:#666;font-size:0.9em;">
        {if $HTACCESS_GALLERIES_OK}{'Actif'|@translate}{else}{'Absent ou incorrect'|@translate}{/if}
      </td>
    </tr>
    <tr>
      <td>
        {if $HTACCESS_UPLOAD_OK}
          <span class="pdp-ok">&#10003;</span>
        {else}
          <span class="pdp-warn">&#10007;</span>
        {/if}
      </td>
      <td>upload/.htaccess</td>
      <td style="padding-left:12px;color:#666;font-size:0.9em;">
        {if $HTACCESS_UPLOAD_OK}{'Actif'|@translate}{else}{'Absent ou incorrect'|@translate}{/if}
      </td>
    </tr>
  </table>

  <!-- ── Albums ────────────────────────────────────────────────────────────── -->
  <h3>{'Albums privés protégés'|@translate}</h3>

  <table>
    <tr>
      <td><strong>{$PRIVATE_ALBUMS_COUNT}</strong></td>
      <td style="padding-left:12px;">{'Albums privés protégés'|@translate}</td>
    </tr>
  </table>

  {if $PRIVATE_ALBUMS_COUNT == 0}
  <p style="color:#777;font-size:0.9em;margin-top:8px;">
    {'Aucun album privé, pdp n\'a rien à protéger.'|@translate}
  </p>
  {/if}

</div>
