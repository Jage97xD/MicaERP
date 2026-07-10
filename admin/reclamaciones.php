<?php
require_once "../config/db.php";
require_once "layout.php";
erp_requerir_permiso($pdo,'reclamaciones','ver');
$estado = $_GET['estado'] ?? '';
$params=[];
$sql="SELECT * FROM libro_reclamaciones WHERE 1=1";
if($estado!==''){ $sql.=" AND estado=:estado"; $params[':estado']=$estado; }
$sql.=" ORDER BY id DESC";
try{ $stmt=$pdo->prepare($sql); $stmt->execute($params); $items=$stmt->fetchAll(PDO::FETCH_ASSOC); }catch(Exception $e){ $items=[]; }
if(isset($_GET['atender'])){ $id=(int)$_GET['atender']; $pdo->prepare("UPDATE libro_reclamaciones SET estado='Atendido' WHERE id=?")->execute([$id]); erp_auditoria($pdo,'reclamaciones','atender','Marcó reclamo como atendido','libro_reclamaciones',$id); header('Location: reclamaciones.php'); exit; }
admin_header('Libro de reclamaciones','reclamaciones');
?>
<div class="panel"><div class="panel-header"><h3>Registros recibidos</h3><a class="btn gray" target="_blank" href="../libro_reclamaciones.php">Ver página pública</a></div>
<form method="GET" class="filter-box" style="display:grid;grid-template-columns:1fr 130px;gap:12px;margin-bottom:18px"><select name="estado"><option value="">Todos</option><option <?= $estado==='Nuevo'?'selected':'' ?>>Nuevo</option><option <?= $estado==='Atendido'?'selected':'' ?>>Atendido</option></select><button class="btn">Filtrar</button></form>
<table class="table"><thead><tr><th>Código</th><th>Tipo</th><th>Cliente</th><th>Contacto</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
<?php foreach($items as $r): ?><tr><td><strong><?= htmlspecialchars($r['codigo']??'') ?></strong></td><td><?= htmlspecialchars($r['tipo']??'') ?></td><td><?= htmlspecialchars($r['nombre']??'') ?><br><small><?= htmlspecialchars($r['documento']??'') ?></small></td><td><?= htmlspecialchars($r['correo']??'') ?><br><?= htmlspecialchars($r['celular']??'') ?></td><td><span class="badge <?= ($r['estado']??'Nuevo')==='Atendido'?'ok':'warn' ?>"><?= htmlspecialchars($r['estado']??'Nuevo') ?></span></td><td><?= htmlspecialchars($r['creado_en']??'') ?></td><td><details><summary class="btn gray">Ver detalle</summary><div style="max-width:520px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-top:8px"><p><strong>Producto/servicio:</strong> <?= htmlspecialchars($r['producto_servicio']??'') ?></p><p><strong>Detalle:</strong><br><?= nl2br(htmlspecialchars($r['detalle']??'')) ?></p><p><strong>Pedido:</strong><br><?= nl2br(htmlspecialchars($r['pedido']??'')) ?></p><?php if(($r['estado']??'Nuevo')!=='Atendido'): ?><a class="btn green" href="reclamaciones.php?atender=<?= (int)$r['id'] ?>">Marcar atendido</a><?php endif; ?></div></details></td></tr><?php endforeach; ?>
<?php if(count($items)===0): ?><tr><td colspan="7">No hay registros.</td></tr><?php endif; ?>
</tbody></table></div><?php admin_footer(); ?>
