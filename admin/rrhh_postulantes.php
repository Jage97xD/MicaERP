<?php
require_once "../config/db.php";
require_once "layout.php";
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0); $estado=$_POST['estado']??'Nuevo'; $nota=trim($_POST['nota_interna']??'');
    if($id>0){ $pdo->prepare("UPDATE rrhh_postulantes SET estado=?, nota_interna=? WHERE id=?")->execute([$estado,$nota,$id]); erp_auditoria($pdo,'rrhh','editar','Actualizó postulante','rrhh_postulantes',$id); }
    header('Location: rrhh_postulantes.php'.(!empty($_GET['puesto_id'])?'?puesto_id='.(int)$_GET['puesto_id']:'')); exit;
}
$puestoId=(int)($_GET['puesto_id']??0); $estado=trim($_GET['estado']??'');
$sql="SELECT po.*, p.titulo puesto FROM rrhh_postulantes po INNER JOIN rrhh_puestos p ON p.id=po.puesto_id WHERE 1=1"; $params=[];
if($puestoId>0){ $sql.=" AND po.puesto_id=:pid"; $params[':pid']=$puestoId; }
if($estado!==''){ $sql.=" AND po.estado=:estado"; $params[':estado']=$estado; }
$sql.=" ORDER BY po.id DESC"; $st=$pdo->prepare($sql); $st->execute($params); $postulantes=$st->fetchAll(PDO::FETCH_ASSOC);
admin_header('Postulantes','rrhh');
?>
<style>.filter-box{display:grid;grid-template-columns:220px 130px;gap:12px;margin-bottom:18px}.status{display:inline-block;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:#e0f2fe;color:#075985}.mini-form{display:flex;gap:8px;flex-wrap:wrap}.mini-form select,.mini-form input{padding:9px;border:1px solid #d8dee9;border-radius:9px}@media(max-width:800px){.filter-box{grid-template-columns:1fr}.mini-form{display:block}.mini-form>*{width:100%;margin-bottom:8px}}</style>
<div class="panel"><div class="panel-header"><div><h3>Postulantes</h3><p style="margin:4px 0 0;color:#64748b">Gestiona postulaciones recibidas desde la web.</p></div><a class="btn gray" href="rrhh_puestos.php">Puestos</a></div><form class="filter-box"><select name="estado"><option value="">Todos</option><?php foreach(['Nuevo','Revisado','Entrevista','Contratado','Descartado'] as $e): ?><option value="<?= h($e) ?>" <?= $estado===$e?'selected':'' ?>><?= h($e) ?></option><?php endforeach; ?></select><?php if($puestoId): ?><input type="hidden" name="puesto_id" value="<?= $puestoId ?>"><?php endif; ?><button class="btn">Filtrar</button></form><table class="table"><thead><tr><th>Fecha</th><th>Postulante</th><th>Puesto</th><th>Contacto</th><th>CV</th><th>Estado / Nota</th></tr></thead><tbody><?php foreach($postulantes as $p): ?><tr><td><?= h($p['creado_en']) ?></td><td><strong><?= h($p['nombre']) ?></strong><br><small><?= h($p['documento']) ?></small></td><td><?= h($p['puesto']) ?></td><td><?= h($p['correo']) ?><br><?= h($p['celular']) ?></td><td><?php if($p['cv_archivo']): ?><a class="btn gray" target="_blank" href="../<?= h($p['cv_archivo']) ?>">Ver CV</a><?php else: ?>-<?php endif; ?></td><td><form method="POST" class="mini-form"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><select name="estado"><?php foreach(['Nuevo','Revisado','Entrevista','Contratado','Descartado'] as $e): ?><option <?= $p['estado']===$e?'selected':'' ?>><?= h($e) ?></option><?php endforeach; ?></select><input name="nota_interna" value="<?= h($p['nota_interna']) ?>" placeholder="Nota interna"><button class="btn green">Guardar</button></form></td></tr><?php endforeach; ?><?php if(!$postulantes): ?><tr><td colspan="6">No hay postulantes.</td></tr><?php endif; ?></tbody></table></div>
<?php admin_footer(); ?>
