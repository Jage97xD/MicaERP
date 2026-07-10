<?php
require_once "config/db.php";
require_once "config/erp_core.php";
require_once "includes/v3/common.php";
require_once "includes/v3/empresa_context.php";
$buscar = $_GET['buscar'] ?? '';
$categoria = '';
$config = micaConfigTodos($pdo);
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM rrhh_puestos WHERE id=? AND estado='Activo' LIMIT 1"); $st->execute([$id]); $puesto=$st->fetch(PDO::FETCH_ASSOC);
if(!$puesto){ http_response_code(404); die('Puesto no encontrado.'); }
$categorias = $pdo->query("SELECT * FROM categorias WHERE activo=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$ok=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $nombre=trim($_POST['nombre']??''); $correo=trim($_POST['correo']??''); $celular=trim($_POST['celular']??'');
        if($nombre==='' || ($correo==='' && $celular==='')) throw new Exception('Ingresa tu nombre y al menos un dato de contacto.');
        $cv='';
        if(isset($_FILES['cv']) && $_FILES['cv']['error']===UPLOAD_ERR_OK){
            $ext=strtolower(pathinfo($_FILES['cv']['name'],PATHINFO_EXTENSION));
            if(!in_array($ext,['pdf','doc','docx'])) throw new Exception('El CV debe ser PDF, DOC o DOCX.');
            if($_FILES['cv']['size'] > 5*1024*1024) throw new Exception('El CV no debe superar 5 MB.');
            $dir='uploads/cv'; if(!is_dir($dir)) mkdir($dir,0777,true);
            $file='cv_'.$id.'_'.date('Ymd_His').'_'.rand(1000,9999).'.'.$ext;
            if(move_uploaded_file($_FILES['cv']['tmp_name'],$dir.'/'.$file)) $cv=$dir.'/'.$file;
        }
        $stmt=$pdo->prepare("INSERT INTO rrhh_postulantes (puesto_id,nombre,correo,celular,documento,experiencia,mensaje,cv_archivo,estado) VALUES (?,?,?,?,?,?,?,?, 'Nuevo')");
        $stmt->execute([$id,$nombre,$correo,$celular,trim($_POST['documento']??''),trim($_POST['experiencia']??''),trim($_POST['mensaje']??''),$cv]);
        $ok='Postulación enviada correctamente. Te contactaremos si avanzas en el proceso.';
    }catch(Exception $e){ $error=$e->getMessage(); }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= h($puesto['titulo']) ?> - <?= h($config['nombre_comercial'] ?? 'Mica Store') ?></title><link rel="stylesheet" href="includes/v3/store_v3.css"><link rel="stylesheet" href="includes/v3/login_modal.css"><link rel="stylesheet" href="includes/v3/header_cliente.css"></head><body>
<?php require "includes/v3/topbar.php"; require "includes/v3/header.php"; require "includes/v3/menu.php"; ?>
<section class="v3-page-hero"><div class="v3-page-hero-inner"><h1><?= h($puesto['titulo']) ?></h1><p><?= h($puesto['area']) ?> · <?= h($puesto['modalidad']) ?> · <?= h($puesto['ubicacion']) ?></p></div></section>
<main class="v3-page-wrap v3-job-detail">
 <section class="v3-legal-card"><h2>Descripción</h2><p><?= nl2br(h($puesto['descripcion'])) ?></p><h2>Requisitos</h2><p><?= nl2br(h($puesto['requisitos'])) ?></p><h2>Beneficios</h2><p><?= nl2br(h($puesto['beneficios'])) ?></p><?php if($puesto['salario']): ?><p><strong>Salario:</strong> <?= h($puesto['salario']) ?></p><?php endif; ?></section>
 <section class="v3-form-card"><h2>Postular</h2><?php if($ok): ?><div class="v3-alert-ok"><?= h($ok) ?></div><?php endif; ?><?php if($error): ?><div class="v3-alert-error"><?= h($error) ?></div><?php endif; ?><form method="POST" enctype="multipart/form-data" class="v3-claim-form"><div class="full"><label>Nombre completo *</label><input name="nombre" required></div><div><label>Correo</label><input type="email" name="correo"></div><div><label>Celular</label><input name="celular"></div><div><label>DNI/Documento</label><input name="documento"></div><div><label>CV PDF/DOC</label><input type="file" name="cv" accept=".pdf,.doc,.docx"></div><div class="full"><label>Experiencia</label><textarea name="experiencia"></textarea></div><div class="full"><label>Mensaje</label><textarea name="mensaje"></textarea></div><button>Enviar postulación</button></form></section>
</main>
<?php require "includes/v3/footer.php"; require "includes/v3/login_modal.php"; ?><script src="includes/v3/login_modal.js"></script><script src="includes/v3/header_cliente.js"></script></body></html>
