<?php
require_once "../config/db.php";
require_once "layout.php";
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

if(isset($_GET['delete'])){
    erp_requerir_permiso($pdo,'rrhh','eliminar');
    $id=(int)$_GET['delete'];
    if($id>0){ $pdo->prepare("DELETE FROM rrhh_puestos WHERE id=?")->execute([$id]); erp_auditoria($pdo,'rrhh','eliminar','Eliminó puesto','rrhh_puestos',$id); }
    header("Location: rrhh_puestos.php"); exit;
}
if(isset($_GET['toggle'])){
    erp_requerir_permiso($pdo,'rrhh','editar');
    $id=(int)$_GET['toggle'];
    $pdo->prepare("UPDATE rrhh_puestos SET estado=IF(estado='Activo','Inactivo','Activo') WHERE id=?")->execute([$id]);
    header("Location: rrhh_puestos.php"); exit;
}
$buscar=trim($_GET['buscar']??'');
$sql="SELECT p.*, COUNT(po.id) postulantes FROM rrhh_puestos p LEFT JOIN rrhh_postulantes po ON po.puesto_id=p.id WHERE 1=1";
$params=[];
if($buscar!==''){ $sql.=" AND (p.titulo LIKE :b OR p.area LIKE :b OR p.ubicacion LIKE :b)"; $params[':b']="%$buscar%"; }
$sql.=" GROUP BY p.id ORDER BY p.estado='Activo' DESC, p.orden ASC, p.id DESC";
$st=$pdo->prepare($sql); $st->execute($params); $puestos=$st->fetchAll(PDO::FETCH_ASSOC);
$totalActivos=$pdo->query("SELECT COUNT(*) FROM rrhh_puestos WHERE estado='Activo'")->fetchColumn();
$totalPost=$pdo->query("SELECT COUNT(*) FROM rrhh_postulantes")->fetchColumn();
$nuevos=$pdo->query("SELECT COUNT(*) FROM rrhh_postulantes WHERE estado='Nuevo'")->fetchColumn();
admin_header("Trabaja con nosotros", "rrhh");
?>
<style>.filter-box{display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:18px}.status{display:inline-block;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900}.status.on{background:#dcfce7;color:#166534}.status.off{background:#fee2e2;color:#991b1b}@media(max-width:800px){.filter-box{grid-template-columns:1fr}}</style>
<div class="cards"><div class="card"><small>Puestos activos</small><strong><?= (int)$totalActivos ?></strong></div><div class="card"><small>Postulantes</small><strong><?= (int)$totalPost ?></strong></div><div class="card"><small>Nuevos</small><strong><?= (int)$nuevos ?></strong></div><div class="card"><small>Módulo</small><strong>RRHH</strong></div></div>
<div class="panel">
 <div class="panel-header"><div><h3>Puestos publicados</h3><p style="margin:4px 0 0;color:#64748b">Publica ofertas laborales y revisa postulantes desde el admin.</p></div><div><a class="btn green" href="rrhh_puesto_form.php">+ Nuevo puesto</a> <a class="btn gray" href="rrhh_postulantes.php">Postulantes</a></div></div>
 <form class="filter-box"><input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar puesto, área o ubicación"><button class="btn">Buscar</button></form>
 <table class="table"><thead><tr><th>ID</th><th>Puesto</th><th>Área</th><th>Modalidad</th><th>Postulantes</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
 <?php foreach($puestos as $p): ?><tr><td><?= (int)$p['id'] ?></td><td><strong><?= h($p['titulo']) ?></strong><br><small><?= h($p['ubicacion']??'') ?> <?= !empty($p['fecha_limite'])?'· hasta '.h($p['fecha_limite']):'' ?></small></td><td><?= h($p['area']??'') ?></td><td><?= h($p['modalidad']??'') ?></td><td><?= (int)$p['postulantes'] ?></td><td><span class="status <?= $p['estado']==='Activo'?'on':'off' ?>"><?= h($p['estado']) ?></span></td><td class="actions"><a class="btn gray" href="rrhh_puesto_form.php?id=<?= (int)$p['id'] ?>">Editar</a> <a class="btn" href="rrhh_postulantes.php?puesto_id=<?= (int)$p['id'] ?>">Postulantes</a> <a class="btn" href="rrhh_puestos.php?toggle=<?= (int)$p['id'] ?>">On/Off</a> <a class="btn red" onclick="return confirm('¿Eliminar puesto?')" href="rrhh_puestos.php?delete=<?= (int)$p['id'] ?>">Eliminar</a></td></tr><?php endforeach; ?>
 <?php if(!$puestos): ?><tr><td colspan="7">No hay puestos registrados.</td></tr><?php endif; ?>
 </tbody></table>
</div>
<?php admin_footer(); ?>
