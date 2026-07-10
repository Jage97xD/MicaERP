<?php
require_once "../config/db.php";
require_once "layout.php";
requerirPermiso('roles','ver');
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}

if($_SERVER['REQUEST_METHOD']==='POST'){
    requerirPermiso('roles','editar');
    $rolId=(int)($_POST['rol_id'] ?? 0);
    if($rolId>0){
        $pdo->prepare("DELETE FROM admin_rol_permisos WHERE rol_id=?")->execute([$rolId]);
        $ins=$pdo->prepare("INSERT INTO admin_rol_permisos (rol_id,permiso_id,permitido) VALUES (?,?,1)");
        foreach(($_POST['permisos'] ?? []) as $permisoId){ $ins->execute([$rolId,(int)$permisoId]); }
        erp_auditoria($pdo,'roles','editar','Actualizó permisos del rol','admin_roles',$rolId);
        header('Location: roles.php?rol_id='.$rolId.'&ok=1'); exit;
    }
}

$roles=$pdo->query("SELECT * FROM admin_roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$rolId=(int)($_GET['rol_id'] ?? ($roles[0]['id'] ?? 0));
$stmt=$pdo->prepare("SELECT * FROM admin_roles WHERE id=?"); $stmt->execute([$rolId]); $rol=$stmt->fetch(PDO::FETCH_ASSOC);
$permisos=$pdo->query("SELECT * FROM admin_permisos ORDER BY modulo ASC, FIELD(accion,'ver','crear','editar','eliminar','exportar') ASC")->fetchAll(PDO::FETCH_ASSOC);
$asignados=[]; if($rolId){ $st=$pdo->prepare("SELECT permiso_id FROM admin_rol_permisos WHERE rol_id=? AND permitido=1"); $st->execute([$rolId]); $asignados=array_flip($st->fetchAll(PDO::FETCH_COLUMN)); }
$grupos=[]; foreach($permisos as $p){ $grupos[$p['modulo']][]=$p; }
admin_header('Roles y permisos','roles');
?>
<style>.roles-layout{display:grid;grid-template-columns:280px 1fr;gap:22px}.role-link{display:block;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:10px;font-weight:900}.role-link.active{background:#2563eb;color:white}.perm-table{width:100%;border-collapse:collapse}.perm-table th,.perm-table td{padding:12px;border-bottom:1px solid #e5e7eb;text-align:center}.perm-table th:first-child,.perm-table td:first-child{text-align:left}.module-name{font-weight:900;text-transform:capitalize}.notice-ok{background:#dcfce7;color:#166534;padding:12px;border-radius:12px;margin-bottom:14px;font-weight:bold}@media(max-width:900px){.roles-layout{grid-template-columns:1fr}}</style>
<div class="roles-layout">
  <div class="panel"><div class="panel-header"><h3>Roles</h3></div><?php foreach($roles as $r): ?><a class="role-link <?= (int)$r['id']===$rolId?'active':'' ?>" href="roles.php?rol_id=<?= (int)$r['id'] ?>"><?= h($r['nombre']) ?><br><small><?= h($r['descripcion'] ?? '') ?></small></a><?php endforeach; ?></div>
  <div class="panel"><div class="panel-header"><div><h3>Permisos: <?= h($rol['nombre'] ?? '') ?></h3><p style="color:#64748b;margin:4px 0 0">Define qué puede ver, crear, editar, eliminar o exportar cada rol.</p></div><button class="btn green" form="formPermisos">Guardar permisos</button></div>
  <?php if(isset($_GET['ok'])): ?><div class="notice-ok">Permisos guardados correctamente.</div><?php endif; ?>
  <form method="POST" id="formPermisos"><input type="hidden" name="rol_id" value="<?= $rolId ?>"><table class="perm-table"><thead><tr><th>Módulo</th><th>Ver</th><th>Crear</th><th>Editar</th><th>Eliminar</th><th>Exportar</th></tr></thead><tbody>
  <?php foreach($grupos as $mod=>$items): ?><tr><td class="module-name"><?= h($mod) ?></td><?php foreach(['ver','crear','editar','eliminar','exportar'] as $accion): $found=null; foreach($items as $it){ if($it['accion']===$accion){$found=$it;break;} } ?><td><?php if($found): ?><input type="checkbox" name="permisos[]" value="<?= (int)$found['id'] ?>" <?= isset($asignados[$found['id']])?'checked':'' ?> <?= ($rol['nombre']??'')==='Administrador'?'checked':'' ?>><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; ?>
  </tbody></table></form></div>
</div>
<?php admin_footer(); ?>
