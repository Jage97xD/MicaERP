<?php
require_once "../config/db.php";
require_once "layout.php";
$id=(int)($_GET['id']??0); requerirPermiso('tiendas',$id?'editar':'crear');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function slugStore($t){ $t=strtolower(trim($t)); $t=iconv('UTF-8','ASCII//TRANSLIT',$t); $t=preg_replace('/[^a-z0-9]+/','-',$t); return trim($t,'-') ?: 'tienda'; }
$categorias=$pdo->query("SELECT id,nombre FROM categorias WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$empresas=$pdo->query("SELECT id,nombre FROM marketplace_empresas WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$tienda=['nombre'=>'','slug'=>'','categoria_id'=>'','empresa_id'=>(int)($_GET['empresa_id']??0),'responsable'=>'','whatsapp'=>'','telefono'=>'','correo'=>'','direccion'=>'','logo'=>'','descripcion'=>'','activo'=>1];
$error='';
if($id){ $st=$pdo->prepare("SELECT * FROM marketplace_tiendas WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC); if(!$row) die('Tienda no encontrada'); $tienda=array_merge($tienda,$row); }
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $nombre=trim($_POST['nombre']??''); if($nombre==='') throw new Exception('El nombre de la tienda es obligatorio.');
  $slug=trim($_POST['slug']??'') ?: slugStore($nombre);
  $logo=$tienda['logo'] ?? '';
  if(!empty($_FILES['logo']['name'])){ $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['jpg','jpeg','png','webp','svg'])) throw new Exception('Logo no válido.'); $dir='../uploads/tiendas'; if(!is_dir($dir)) mkdir($dir,0777,true); $rel='uploads/tiendas/tienda_'.time().'_'.rand(1000,9999).'.'.$ext; if(move_uploaded_file($_FILES['logo']['tmp_name'],'../'.$rel)) $logo=$rel; }
  $data=[$nombre,$slug,(int)($_POST['categoria_id']??0)?:null,(int)($_POST['empresa_id']??0)?:null,trim($_POST['responsable']??''),trim($_POST['whatsapp']??''),trim($_POST['telefono']??''),trim($_POST['correo']??''),trim($_POST['direccion']??''),$logo,trim($_POST['descripcion']??''),isset($_POST['activo'])?1:0];
  if($id){ $data[]=$id; $pdo->prepare("UPDATE marketplace_tiendas SET nombre=?,slug=?,categoria_id=?,empresa_id=?,responsable=?,whatsapp=?,telefono=?,correo=?,direccion=?,logo=?,descripcion=?,activo=? WHERE id=?")->execute($data); erp_auditoria($pdo,'tiendas','editar','Editó tienda '.$nombre,'marketplace_tiendas',$id); }
  else{ $pdo->prepare("INSERT INTO marketplace_tiendas (nombre,slug,categoria_id,empresa_id,responsable,whatsapp,telefono,correo,direccion,logo,descripcion,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute($data); $id=(int)$pdo->lastInsertId(); erp_auditoria($pdo,'tiendas','crear','Creó tienda '.$nombre,'marketplace_tiendas',$id); }
  header('Location: tiendas.php?ok=1'); exit;
 }catch(Exception $e){ $error=$e->getMessage(); $tienda=array_merge($tienda,$_POST); }
}
admin_header($id?'Editar tienda':'Nueva tienda','tiendas'); ?>
<style>.grid{display:grid;grid-template-columns:1.2fr .8fr;gap:22px}.preview{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:22px}.preview img{max-width:160px;max-height:110px;object-fit:contain;background:white;border-radius:14px;padding:8px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:12px;margin-bottom:14px;font-weight:bold}@media(max-width:900px){.grid{grid-template-columns:1fr}}</style>
<div class="grid"><div class="panel"><div class="panel-header"><h3><?= $id?'Editar tienda':'Nueva tienda' ?></h3><a class="btn gray" href="tiendas.php">Volver</a></div><?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data"><div class="form-grid">
 <div class="form-group"><label>Nombre de tienda</label><input name="nombre" required value="<?= h($tienda['nombre']) ?>" placeholder="Ej. Jaimito Tech"></div>
 <div class="form-group"><label>Slug</label><input name="slug" value="<?= h($tienda['slug']) ?>" placeholder="jaimito-tech"></div>
 <div class="form-group"><label>Empresa</label><select name="empresa_id"><option value="">Sin empresa</option><?php foreach($empresas as $e): ?><option value="<?= (int)$e['id'] ?>" <?= (int)$tienda['empresa_id']===(int)$e['id']?'selected':'' ?>><?= h($e['nombre']) ?></option><?php endforeach; ?></select></div>
 <div class="form-group"><label>Categoría principal</label><select name="categoria_id"><option value="">Sin categoría</option><?php foreach($categorias as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$tienda['categoria_id']===(int)$c['id']?'selected':'' ?>><?= h($c['nombre']) ?></option><?php endforeach; ?></select></div>
 <div class="form-group"><label>Responsable</label><input name="responsable" value="<?= h($tienda['responsable']) ?>"></div>
 <div class="form-group"><label>WhatsApp propio</label><input name="whatsapp" value="<?= h($tienda['whatsapp']) ?>" placeholder="51999999999"></div>
 <div class="form-group"><label>Teléfono</label><input name="telefono" value="<?= h($tienda['telefono']) ?>"></div>
 <div class="form-group"><label>Correo</label><input type="email" name="correo" value="<?= h($tienda['correo']) ?>"></div>
 <div class="form-group"><label>Dirección / referencia</label><input name="direccion" value="<?= h($tienda['direccion']) ?>"></div>
 <div class="form-group full"><label>Logo</label><input type="file" name="logo" accept="image/*,.svg"></div>
 <div class="form-group full"><label>Descripción</label><textarea name="descripcion"><?= h($tienda['descripcion']) ?></textarea></div>
 <div class="form-group full"><label><input type="checkbox" name="activo" <?= !empty($tienda['activo'])?'checked':'' ?>> Tienda activa</label></div>
 <div class="form-group full"><button class="btn green">Guardar tienda</button></div>
</div></form></div><div class="preview"><h3>Uso del marketplace</h3><p>Asigna productos y usuarios vendedores a esta tienda.</p><p>Cuando un cliente cotice un producto de esta tienda, el WhatsApp puede ir al número propio del vendedor.</p><?php if(!empty($tienda['logo'])): ?><img src="../<?= h($tienda['logo']) ?>"><?php endif; ?></div></div><?php admin_footer(); ?>