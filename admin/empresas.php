<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('empresas','ver');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT e.*, COUNT(DISTINCT t.id) AS total_tiendas, COUNT(DISTINCT u.id) AS total_usuarios
        FROM marketplace_empresas e
        LEFT JOIN marketplace_tiendas t ON t.empresa_id=e.id
        LEFT JOIN admin_usuarios u ON u.empresa_id=e.id
        WHERE 1=1";
$params=[];
if($buscar!==''){
    $sql .= " AND (e.nombre LIKE :b OR e.responsable LIKE :b OR e.ruc LIKE :b OR e.correo LIKE :b)";
    $params[':b']="%$buscar%";
}
$sql .= " GROUP BY e.id ORDER BY e.id DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $empresas=$stmt->fetchAll(PDO::FETCH_ASSOC);
admin_header('Empresas','empresas');
?>
<style>.filter-box{display:grid;grid-template-columns:1fr 140px;gap:12px;margin-bottom:18px}.filter-box input{padding:12px;border:1px solid #e5e7eb;border-radius:10px}.store-logo{width:52px;height:52px;border-radius:12px;object-fit:contain;background:#f8fafc;border:1px solid #e5e7eb;padding:4px}.muted{color:#64748b;font-size:13px}</style>
<div class="panel">
 <div class="panel-header"><div><h3>Empresas del marketplace</h3><p class="muted">Cada empresa agrupa varias tiendas. Útil para vender MicaERP a distintos clientes (multiempresa).</p></div><?php if(rolPuede('empresas','crear')): ?><a class="btn" href="empresa_form.php">+ Nueva empresa</a><?php endif; ?></div>
 <form class="filter-box" method="GET"><input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar empresa, responsable, RUC o correo"><button class="btn">Buscar</button></form>
 <table class="table"><thead><tr><th>Logo</th><th>Empresa</th><th>RUC</th><th>Responsable</th><th>Plan</th><th>Tiendas</th><th>Usuarios</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
 <?php foreach($empresas as $e): ?><tr>
  <td><?php if(!empty($e['logo'])): ?><img class="store-logo" src="../<?= h($e['logo']) ?>"><?php else: ?><div class="store-logo"></div><?php endif; ?></td>
  <td><strong><?= h($e['nombre']) ?></strong><br><small><?= h($e['slug']) ?></small></td>
  <td><?= h($e['ruc'] ?? '-') ?></td><td><?= h($e['responsable'] ?? '-') ?></td><td><?= h($e['plan'] ?? 'Estandar') ?></td>
  <td><a href="tiendas.php?empresa_id=<?= (int)$e['id'] ?>"><?= (int)$e['total_tiendas'] ?></a></td><td><?= (int)$e['total_usuarios'] ?></td>
  <td><span class="badge <?= $e['activo']?'ok':'warn' ?>"><?= $e['activo']?'Activo':'Inactivo' ?></span></td>
  <td class="actions"><a class="btn gray" href="empresa_form.php?id=<?= (int)$e['id'] ?>">Editar</a> <a class="btn" href="configuracion.php?empresa_id=<?= (int)$e['id'] ?>">🎨 Sitio</a> <a class="btn gray" href="../<?= h($e['slug']) ?>/" target="_blank">🔗 Ver</a></td>
 </tr><?php endforeach; ?><?php if(!$empresas): ?><tr><td colspan="9">No hay empresas registradas.</td></tr><?php endif; ?></tbody></table>
</div><?php admin_footer(); ?>
