<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('accesos','ver');
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$rows=$pdo->query("SELECT * FROM admin_accesos ORDER BY id DESC LIMIT 300")->fetchAll(PDO::FETCH_ASSOC);
admin_header('Historial de accesos','accesos');
?>
<style>.access-ok{background:#dcfce7;color:#166534;border-radius:999px;padding:6px 10px;font-weight:900}.access-bad{background:#fee2e2;color:#991b1b;border-radius:999px;padding:6px 10px;font-weight:900}.ua{max-width:360px;color:#64748b;font-size:12px;white-space:normal}</style>
<div class="panel"><div class="panel-header"><div><h3>Historial de accesos</h3><p style="color:#64748b;margin:4px 0 0">Login correctos, errores de contraseña, bloqueos e intentos fallidos.</p></div></div><table class="table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Resultado</th><th>Mensaje</th><th>IP</th><th>Navegador</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= h($r['creado_en']) ?></td><td><?= h($r['usuario'] ?: '-') ?></td><td><span class="<?= $r['exito']?'access-ok':'access-bad' ?>"><?= $r['exito']?'Correcto':'Fallido' ?></span></td><td><?= h($r['mensaje']) ?></td><td><?= h($r['ip']) ?></td><td class="ua"><?= h($r['user_agent']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php admin_footer(); ?>
