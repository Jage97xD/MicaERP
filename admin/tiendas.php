<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('tiendas','ver');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$buscar = trim($_GET['buscar'] ?? '');
$empresaFiltro = (int)($_GET['empresa_id'] ?? 0);
$sql = "SELECT t.*, c.nombre AS categoria_nombre, e.nombre AS empresa_nombre, COUNT(p.id) AS total_productos, COUNT(u.id) AS total_usuarios
        FROM marketplace_tiendas t
        LEFT JOIN categorias c ON c.id=t.categoria_id
        LEFT JOIN marketplace_empresas e ON e.id=t.empresa_id
        LEFT JOIN productos p ON p.tienda_id=t.id
        LEFT JOIN admin_usuarios u ON u.tienda_id=t.id
        WHERE 1=1";
$params=[];
if($buscar!==''){
    $sql .= " AND (t.nombre LIKE :b OR t.responsable LIKE :b OR t.whatsapp LIKE :b OR c.nombre LIKE :b)";
    $params[':b']="%$buscar%";
}
if($empresaFiltro>0){ $sql .= " AND t.empresa_id = :e"; $params[':e']=$empresaFiltro; }
$sql .= " GROUP BY t.id ORDER BY t.id DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $tiendas=$stmt->fetchAll(PDO::FETCH_ASSOC);
$empresaNombreFiltro = $empresaFiltro ? $pdo->query("SELECT nombre FROM marketplace_empresas WHERE id={$empresaFiltro}")->fetchColumn() : '';
admin_header('Tiendas / Vendedores','tiendas');
?>
<style>.filter-box{display:grid;grid-template-columns:1fr 220px 140px;gap:12px;margin-bottom:18px}.filter-box input,.filter-box select{padding:12px;border:1px solid #e5e7eb;border-radius:10px}.store-logo{width:52px;height:52px;border-radius:12px;object-fit:contain;background:#f8fafc;border:1px solid #e5e7eb;padding:4px}.muted{color:#64748b;font-size:13px}</style>
<div class="panel">
 <div class="panel-header"><div><h3>Tiendas / vendedores del marketplace</h3><p class="muted">Crea tiendas como Jaimito Tech o Pepito Store. Cada una puede tener WhatsApp y usuarios vendedores propios.<?php if($empresaFiltro): ?> Filtrando por empresa: <strong><?= h($empresaNombreFiltro) ?></strong> — <a href="tiendas.php">quitar filtro</a>.<?php endif; ?></p></div><?php if(rolPuede('tiendas','crear')): ?><a class="btn" href="tienda_form.php<?= $empresaFiltro?'?empresa_id='.$empresaFiltro:'' ?>">+ Nueva tienda</a><?php endif; ?></div>
<?php $empresasFiltroLista = $pdo->query("SELECT id,nombre FROM marketplace_empresas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC); ?>
 <form class="filter-box" method="GET">
    <input name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar tienda, responsable, WhatsApp o categoría">
    <select name="empresa_id">
        <option value="0">Todas las empresas</option>
        <?php foreach($empresasFiltroLista as $ef): ?>
            <option value="<?= (int)$ef['id'] ?>" <?= $empresaFiltro===(int)$ef['id']?'selected':'' ?>><?= h($ef['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn">Buscar</button>
 </form>
 <table class="table"><thead><tr><th>Logo</th><th>Tienda</th><th>Empresa</th><th>Categoría</th><th>Responsable</th><th>WhatsApp</th><th>Productos</th><th>Usuarios</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
 <?php foreach($tiendas as $t): ?><tr>
  <td><?php if(!empty($t['logo'])): ?><img class="store-logo" src="../<?= h($t['logo']) ?>"><?php else: ?><div class="store-logo"></div><?php endif; ?></td>
  <td><strong><?= h($t['nombre']) ?></strong><br><small><?= h($t['slug']) ?></small></td>
  <td><?= h($t['empresa_nombre'] ?? '-') ?></td>
  <td><?= h($t['categoria_nombre'] ?? 'Sin categoría') ?></td><td><?= h($t['responsable'] ?? '-') ?></td><td><?= h($t['whatsapp'] ?? '-') ?></td>
  <td><?= (int)$t['total_productos'] ?></td><td><?= (int)$t['total_usuarios'] ?></td><td><span class="badge <?= $t['activo']?'ok':'warn' ?>"><?= $t['activo']?'Activo':'Inactivo' ?></span></td>
  <td class="actions"><a class="btn gray" href="tienda_form.php?id=<?= (int)$t['id'] ?>">Editar</a> <a class="btn" href="../tienda_publica.php?slug=<?= h($t['slug']) ?>" target="_blank">🔗 Ver tienda</a></td>
 </tr><?php endforeach; ?><?php if(!$tiendas): ?><tr><td colspan="10">No hay tiendas registradas.</td></tr><?php endif; ?></tbody></table>
</div><?php admin_footer(); ?>