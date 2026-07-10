<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('auditoria','ver');
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$buscar=trim($_GET['buscar'] ?? '');
$sql="SELECT * FROM admin_auditoria WHERE 1=1"; $params=[];
if($buscar!==''){ $sql.=" AND (usuario_nombre LIKE :b OR modulo LIKE :b OR accion LIKE :b OR descripcion LIKE :b OR referencia_tabla LIKE :b)"; $params[':b']="%$buscar%"; }
$sql.=" ORDER BY id DESC LIMIT 300";
$st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll(PDO::FETCH_ASSOC);
admin_header('Auditoría','auditoria');
?>
<style>.filter-box{display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:18px}.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px}.muted{color:#64748b;font-size:12px}.audit-desc{max-width:420px;white-space:normal}</style>
<div class="panel"><div class="panel-header"><div><h3>Auditoría del ERP</h3><p style="color:#64748b;margin:4px 0 0">Registro de cambios importantes: usuarios, roles, accesos y operaciones críticas.</p></div></div><form class="filter-box" method="GET"><input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar usuario, módulo, acción o descripción"><button class="btn">Buscar</button></form><table class="table"><thead><tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Descripción</th><th>Referencia</th><th>IP</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><?= h($r['creado_en']) ?></td><td><?= h($r['usuario_nombre'] ?: '-') ?></td><td><?= h($r['modulo']) ?></td><td><strong><?= h($r['accion']) ?></strong></td><td class="audit-desc"><?= h($r['descripcion']) ?></td><td><span class="muted"><?= h($r['referencia_tabla']) ?> #<?= h($r['referencia_id']) ?></span></td><td><?= h($r['ip']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php admin_footer(); ?>
