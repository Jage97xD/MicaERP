<?php
require_once "../config/db.php";
require_once "layout.php";
$id=(int)($_GET['id']??0); requerirPermiso('empresas',$id?'editar':'crear');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function slugEmpresa($t){ $t=strtolower(trim($t)); $t=iconv('UTF-8','ASCII//TRANSLIT',$t); $t=preg_replace('/[^a-z0-9]+/','-',$t); return trim($t,'-') ?: 'empresa'; }
$empresa=['nombre'=>'','slug'=>'','ruc'=>'','responsable'=>'','whatsapp'=>'','correo'=>'','direccion'=>'','logo'=>'','color_principal'=>'#16a34a','color_secundario'=>'#0f172a','plan'=>'Estandar','activo'=>1];
$error='';
if($id){ $st=$pdo->prepare("SELECT * FROM marketplace_empresas WHERE id=?"); $st->execute([$id]); $row=$st->fetch(PDO::FETCH_ASSOC); if(!$row) die('Empresa no encontrada'); $empresa=array_merge($empresa,$row);
  $cfgVigente = erp_config_empresa($pdo, $id);
  if(!empty($cfgVigente['color_principal'])) $empresa['color_principal'] = $cfgVigente['color_principal'];
  if(!empty($cfgVigente['color_secundario'])) $empresa['color_secundario'] = $cfgVigente['color_secundario'];
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $nombre=trim($_POST['nombre']??''); if($nombre==='') throw new Exception('El nombre de la empresa es obligatorio.');
  $slug=trim($_POST['slug']??'') ?: slugEmpresa($nombre);
  $logo=$empresa['logo'] ?? '';
  if(!empty($_FILES['logo']['name'])){ $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION)); if(!in_array($ext,['jpg','jpeg','png','webp','svg'])) throw new Exception('Logo no válido.'); $dir='../uploads/empresas'; if(!is_dir($dir)) mkdir($dir,0777,true); $rel='uploads/empresas/empresa_'.time().'_'.rand(1000,9999).'.'.$ext; if(move_uploaded_file($_FILES['logo']['tmp_name'],'../'.$rel)) $logo=$rel; }
  $data=[$nombre,$slug,trim($_POST['ruc']??''),trim($_POST['responsable']??''),trim($_POST['whatsapp']??''),trim($_POST['correo']??''),trim($_POST['direccion']??''),$logo,trim($_POST['color_principal']??'#16a34a'),trim($_POST['color_secundario']??'#0f172a'),trim($_POST['plan']??'Estandar'),isset($_POST['activo'])?1:0];
  if($id){ $data[]=$id; $pdo->prepare("UPDATE marketplace_empresas SET nombre=?,slug=?,ruc=?,responsable=?,whatsapp=?,correo=?,direccion=?,logo=?,color_principal=?,color_secundario=?,plan=?,activo=? WHERE id=?")->execute($data); erp_auditoria($pdo,'empresas','editar','Editó empresa '.$nombre,'marketplace_empresas',$id); erp_clonar_sitio_para_empresa($pdo,$id); erp_set_config_empresa($pdo,$id,'color_principal',trim($_POST['color_principal']??'#16a34a')); erp_set_config_empresa($pdo,$id,'color_secundario',trim($_POST['color_secundario']??'#0f172a')); }
  else{ $pdo->prepare("INSERT INTO marketplace_empresas (nombre,slug,ruc,responsable,whatsapp,correo,direccion,logo,color_principal,color_secundario,plan,activo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute($data); $id=(int)$pdo->lastInsertId(); erp_auditoria($pdo,'empresas','crear','Creó empresa '.$nombre,'marketplace_empresas',$id); erp_clonar_sitio_para_empresa($pdo,$id); erp_set_config_empresa($pdo,$id,'color_principal',trim($_POST['color_principal']??'#16a34a')); erp_set_config_empresa($pdo,$id,'color_secundario',trim($_POST['color_secundario']??'#0f172a')); }
  header('Location: empresas.php?ok=1'); exit;
 }catch(Exception $e){ $error=$e->getMessage(); $empresa=array_merge($empresa,$_POST); }
}
admin_header($id?'Editar empresa':'Nueva empresa','empresas'); ?>
<style>.grid{display:grid;grid-template-columns:1.2fr .8fr;gap:22px}.preview{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:22px}.preview img{max-width:160px;max-height:110px;object-fit:contain;background:white;border-radius:14px;padding:8px}.error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:12px;margin-bottom:14px;font-weight:bold}.swatch{display:inline-block;width:22px;height:22px;border-radius:6px;vertical-align:middle;margin-left:8px;border:1px solid #e5e7eb}@media(max-width:900px){.grid{grid-template-columns:1fr}}</style>
<div class="grid"><div class="panel"><div class="panel-header"><h3><?= $id?'Editar empresa':'Nueva empresa' ?></h3><a class="btn gray" href="empresas.php">Volver</a></div><?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data"><div class="form-grid">
 <div class="form-group"><label>Nombre de la empresa</label><input name="nombre" required value="<?= h($empresa['nombre']) ?>" placeholder="Ej. CC Mercado La Chacra"></div>
 <div class="form-group"><label>Slug</label><input name="slug" value="<?= h($empresa['slug']) ?>" placeholder="mercado-la-chacra"></div>
 <div class="form-group"><label>RUC</label><input name="ruc" value="<?= h($empresa['ruc']) ?>"></div>
 <div class="form-group"><label>Responsable</label><input name="responsable" value="<?= h($empresa['responsable']) ?>"></div>
 <div class="form-group"><label>WhatsApp</label><input name="whatsapp" value="<?= h($empresa['whatsapp']) ?>" placeholder="51999999999"></div>
 <div class="form-group"><label>Correo</label><input type="email" name="correo" value="<?= h($empresa['correo']) ?>"></div>
 <div class="form-group full"><label>Dirección</label><input name="direccion" value="<?= h($empresa['direccion']) ?>"></div>
 <div class="form-group"><label>Color principal<span class="swatch" style="background:<?= h($empresa['color_principal']) ?>"></span></label><input type="color" name="color_principal" value="<?= h($empresa['color_principal'] ?: '#16a34a') ?>"></div>
 <div class="form-group"><label>Color secundario<span class="swatch" style="background:<?= h($empresa['color_secundario']) ?>"></span></label><input type="color" name="color_secundario" value="<?= h($empresa['color_secundario'] ?: '#0f172a') ?>"></div>
 <div class="form-group full"><p class="help">Estos colores pintan de verdad el sitio público de esta empresa (botones, precios, degradados). Se guardan junto con el resto de su Configuración — si luego los cambias desde <b>Configuración → Apariencia</b>, esos ajustes finos tienen prioridad sobre lo que pongas aquí.</p></div>
 <div class="form-group"><label>Plan</label><select name="plan"><option value="Estandar" <?= $empresa['plan']==='Estandar'?'selected':'' ?>>Estándar</option><option value="Profesional" <?= $empresa['plan']==='Profesional'?'selected':'' ?>>Profesional</option><option value="Marketplace" <?= $empresa['plan']==='Marketplace'?'selected':'' ?>>Marketplace</option></select></div>
 <div class="form-group full"><label>Logo</label><input type="file" name="logo" accept="image/*,.svg"></div>
 <div class="form-group full"><label><input type="checkbox" name="activo" <?= !empty($empresa['activo'])?'checked':'' ?>> Empresa activa</label></div>
 <div class="form-group full"><button class="btn green">Guardar empresa</button></div>
</div></form></div><div class="preview"><h3>Multiempresa</h3><p>Cada empresa puede tener varias tiendas (Tecnología, Ferretería, Belleza, etc.).</p><p>Un usuario con rol <strong>Supervisor</strong> asignado a esta empresa verá todas sus tiendas; un <strong>Vendedor</strong> solo la suya.</p><?php if(!empty($empresa['logo'])): ?><img src="../<?= h($empresa['logo']) ?>"><?php endif; ?></div></div><?php admin_footer(); ?>
