<?php
function configValor($pdo, $clave, $default=''){
    try{
        $stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = ? LIMIT 1");
        $stmt->execute([$clave]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['valor'] : $default;
    }catch(Exception $e){
        return $default;
    }
}

function configTodos($pdo){
    $config = [];
    try{
        $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){
            $config[$row['clave']] = $row['valor'];
        }
    }catch(Exception $e){}
    return $config;
}

function configCampos($pdo, $ubicacion){
    try{
        $stmt = $pdo->prepare("SELECT * FROM configuracion_campos WHERE ubicacion = ? AND activo = 1 ORDER BY orden ASC, id ASC");
        $stmt->execute([$ubicacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch(Exception $e){
        return [];
    }
}
?>