<?php
// TopBar dinámica de MicaStore.
// Se alimenta desde admin/topbar.php y usa configuracion.maps_url cuando el destino es Google Maps.
$topbarItems = [];
try{
    $pdo->exec("CREATE TABLE IF NOT EXISTS topbar_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grupo ENUM('izquierda','derecha') DEFAULT 'izquierda',
        icono VARCHAR(20) NULL,
        texto VARCHAR(180) NOT NULL,
        tipo_enlace ENUM('ninguno','url','maps','contacto','interno') DEFAULT 'ninguno',
        url VARCHAR(255) NULL,
        visible TINYINT(1) DEFAULT 1,
        nueva_pestana TINYINT(1) DEFAULT 0,
        orden INT DEFAULT 0,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $countTopbar = (int)$pdo->query("SELECT COUNT(*) FROM topbar_items")->fetchColumn();
    if($countTopbar === 0){
        $ins = $pdo->prepare("INSERT INTO topbar_items (grupo,icono,texto,tipo_enlace,url,visible,nueva_pestana,orden) VALUES (?,?,?,?,?,?,?,?)");
        $ins->execute(['izquierda','🚚','Envíos a todo el Perú','ninguno','',1,0,1]);
        $ins->execute(['izquierda','🛡️','Garantía en productos','ninguno','',1,0,2]);
        $ins->execute(['izquierda','📍','Mercado La Chacra - Lurigancho','maps','',1,1,3]);
        $ins->execute(['derecha','','Facebook','url','',1,1,10]);
        $ins->execute(['derecha','','Instagram','url','',1,1,11]);
        $ins->execute(['derecha','','TikTok','url','',1,1,12]);
    }

    $topbarItems = $pdo->query("SELECT * FROM topbar_items WHERE visible=1 ORDER BY grupo ASC, orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){
    $topbarItems = [
        ['grupo'=>'izquierda','icono'=>'🚚','texto'=>'Envíos a todo el Perú','tipo_enlace'=>'ninguno','url'=>'','nueva_pestana'=>0],
        ['grupo'=>'izquierda','icono'=>'🛡️','texto'=>'Garantía en productos','tipo_enlace'=>'ninguno','url'=>'','nueva_pestana'=>0],
        ['grupo'=>'izquierda','icono'=>'📍','texto'=>'Mercado La Chacra - Lurigancho','tipo_enlace'=>'maps','url'=>'','nueva_pestana'=>1],
        ['grupo'=>'derecha','icono'=>'','texto'=>'Facebook','tipo_enlace'=>'url','url'=>'','nueva_pestana'=>1],
        ['grupo'=>'derecha','icono'=>'','texto'=>'Instagram','tipo_enlace'=>'url','url'=>'','nueva_pestana'=>1],
        ['grupo'=>'derecha','icono'=>'','texto'=>'TikTok','tipo_enlace'=>'url','url'=>'','nueva_pestana'=>1],
    ];
}

function micaTopbarConfigValor($pdo, $clave, $default=''){
    try{
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave=? LIMIT 1");
        $stmt->execute([$clave]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    }catch(Exception $e){ return $default; }
}

function micaTopbarUrl($pdo, $item){
    $tipo = $item['tipo_enlace'] ?? 'ninguno';
    $url = trim((string)($item['url'] ?? ''));
    if($tipo === 'ninguno') return '';
    if($tipo === 'maps') return micaTopbarConfigValor($pdo, 'maps_url', $url);
    if($tipo === 'contacto') return 'contacto.php';
    if($tipo === 'interno') return $url ?: 'tienda_visual_v3.php';
    return $url;
}

function micaTopbarRenderItem($pdo, $item){
    $icono = trim((string)($item['icono'] ?? ''));
    $texto = trim((string)($item['texto'] ?? ''));
    $contenido = ($icono !== '' ? '<span class="v3-topbar-icon">'.h($icono).'</span> ' : '') . '<span>'.h($texto).'</span>';
    $href = micaTopbarUrl($pdo, $item);
    if($href === ''){
        return '<span class="v3-topbar-item">'.$contenido.'</span>';
    }
    $target = !empty($item['nueva_pestana']) ? ' target="_blank" rel="noopener"' : '';
    return '<a class="v3-topbar-item v3-topbar-link" href="'.h($href).'"'.$target.'>'.$contenido.'</a>';
}

if(micaTopbarConfigValor($pdo, 'header_mostrar_topbar', '1') !== '1') { return; }

$topbarLeft = array_filter($topbarItems, fn($i)=>($i['grupo'] ?? 'izquierda') === 'izquierda');
$topbarRight = array_filter($topbarItems, fn($i)=>($i['grupo'] ?? 'izquierda') === 'derecha');
?>
<div class="v3-topbar">
    <div>
        <?php foreach($topbarLeft as $item): ?>
            <?= micaTopbarRenderItem($pdo, $item) ?>
        <?php endforeach; ?>
    </div>
    <div>
        <?php foreach($topbarRight as $item): ?>
            <?= micaTopbarRenderItem($pdo, $item) ?>
        <?php endforeach; ?>
    </div>
</div>
