<?php
require_once "config/db.php";
if(session_status() === PHP_SESSION_NONE){ session_start(); }
header("Content-Type: application/json; charset=utf-8");

function columnas($pdo, $tabla){
    $cols = [];
    $stmt = $pdo->query("DESCRIBE $tabla");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
        $cols[$r['Field']] = true;
    }
    return $cols;
}

function insertarDinamico($pdo, $tabla, $datos){
    $cols = columnas($pdo, $tabla);
    $insert = [];
    foreach($datos as $k=>$v){
        if(isset($cols[$k])){
            $insert[$k] = $v;
        }
    }

    if(count($insert) === 0){
        throw new Exception("No hay columnas compatibles para $tabla");
    }

    $campos = array_keys($insert);
    $sql = "INSERT INTO $tabla (" . implode(",", $campos) . ") VALUES (" . implode(",", array_fill(0, count($campos), "?")) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($insert));
    return (int)$pdo->lastInsertId();
}

function actualizarDinamico($pdo, $tabla, $datos, $id){
    $cols = columnas($pdo, $tabla);
    $update = [];
    $values = [];

    foreach($datos as $k=>$v){
        if(isset($cols[$k])){
            $update[] = "$k=?";
            $values[] = $v;
        }
    }

    if(count($update) === 0) return;

    $values[] = $id;
    $sql = "UPDATE $tabla SET " . implode(",", $update) . " WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

$input = json_decode(file_get_contents("php://input"), true);

if(!$input){
    echo json_encode(["ok"=>false, "mensaje"=>"No se recibieron datos."]);
    exit;
}

$cliente = $input['cliente'] ?? [];
$items = $input['items'] ?? [];

if(count($items) === 0){
    echo json_encode(["ok"=>false, "mensaje"=>"La cotización está vacía."]);
    exit;
}

$nombre = trim($cliente['nombre'] ?? '');
$documento = trim($cliente['documento'] ?? '');
$celular = trim($cliente['celular'] ?? '');

if($nombre === '' || $documento === '' || $celular === ''){
    echo json_encode(["ok"=>false, "mensaje"=>"Nombre, documento y celular son obligatorios."]);
    exit;
}

try{
    $pdo->beginTransaction();

    // Cliente: crear o actualizar por documento
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE documento=? LIMIT 1");
    $stmt->execute([$documento]);
    $clienteId = (int)$stmt->fetchColumn();

    $datosCliente = [
        'nombre'=>$nombre,
        'documento'=>$documento,
        'celular'=>$celular,
        'correo'=>trim($cliente['correo'] ?? ''),
        'direccion'=>trim($cliente['direccion'] ?? ''),
        'distrito'=>trim($cliente['distrito'] ?? ''),
        'provincia'=>trim($cliente['provincia'] ?? ''),
        'acepta_ofertas'=>!empty($cliente['acepta_ofertas']) ? 1 : 0,
        'acepta_contacto'=>!empty($cliente['acepta_contacto']) ? 1 : 0,
        'fecha_consentimiento'=>(!empty($cliente['acepta_ofertas']) || !empty($cliente['acepta_contacto'])) ? date('Y-m-d H:i:s') : null,
        'activo'=>1
    ];

    if($clienteId > 0){
        actualizarDinamico($pdo, "clientes", $datosCliente, $clienteId);
    }else{
        $clienteId = insertarDinamico($pdo, "clientes", $datosCliente);
    }

    if(isset($_SESSION['cliente_web_id']) && (int)$_SESSION['cliente_web_id'] > 0){
        actualizarDinamico($pdo, "clientes_web", [
            'nombre'=>$nombre,
            'celular'=>$celular,
            'documento'=>$documento,
            'acepta_ofertas'=>!empty($cliente['acepta_ofertas']) ? 1 : 0,
            'acepta_contacto'=>!empty($cliente['acepta_contacto']) ? 1 : 0,
            'fecha_consentimiento'=>(!empty($cliente['acepta_ofertas']) || !empty($cliente['acepta_contacto'])) ? date('Y-m-d H:i:s') : null
        ], (int)$_SESSION['cliente_web_id']);
    }

    // Recalcular desde BD
    $ids = [];
    foreach($items as $it){
        $ids[] = (int)$it['id'];
    }
    $ids = array_values(array_unique(array_filter($ids)));

    if(count($ids) === 0){
        throw new Exception("No hay productos válidos.");
    }

    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $pdo->prepare("SELECT id,nombre,precio,precio_oferta,stock FROM productos WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productos = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $p){
        $productos[(int)$p['id']] = $p;
    }

    $total = 0;
    $detalleFinal = [];

    foreach($items as $it){
        $pid = (int)$it['id'];
        $qty = max(1, (int)($it['qty'] ?? 1));

        if(!isset($productos[$pid])) continue;

        $p = $productos[$pid];
        $precio = ((float)$p['precio_oferta'] > 0) ? (float)$p['precio_oferta'] : (float)$p['precio'];
        $subtotal = $precio * $qty;
        $total += $subtotal;

        $detalleFinal[] = [
            'producto_id'=>$pid,
            'nombre'=>$p['nombre'],
            'cantidad'=>$qty,
            'precio'=>$precio,
            'subtotal'=>$subtotal
        ];
    }

    if(count($detalleFinal) === 0){
        throw new Exception("No hay productos disponibles.");
    }

    $numero = "COT-" . date("Ymd") . "-" . rand(1000,9999);

    $datosCot = [
        'numero'=>$numero,
        'cliente_id'=>$clienteId,
        'nombre_cliente'=>$nombre,
        'nombre_completo'=>$nombre,
        'nombre'=>$nombre,
        'documento'=>$documento,
        'dni_ruc'=>$documento,
        'celular'=>$celular,
        'telefono'=>$celular,
        'correo'=>trim($cliente['correo'] ?? ''),
        'tipo_entrega'=>trim($cliente['tipo_entrega'] ?? ''),
        'entrega'=>trim($cliente['tipo_entrega'] ?? ''),
        'direccion'=>trim($cliente['direccion'] ?? ''),
        'distrito'=>trim($cliente['distrito'] ?? ''),
        'provincia'=>trim($cliente['provincia'] ?? ''),
        'observaciones'=>trim($cliente['observaciones'] ?? ''),
        'total'=>$total,
        'estado'=>'Pendiente de revisión',
        'cliente_web_id'=>isset($_SESSION['cliente_web_id']) ? (int)$_SESSION['cliente_web_id'] : null,
        'pago_validado'=>0,
        'tracking_actualizado_en'=>date("Y-m-d H:i:s"),
        'creado_en'=>date("Y-m-d H:i:s")
    ];

    $cotizacionId = insertarDinamico($pdo, "cotizaciones", $datosCot);

    foreach($detalleFinal as $d){
        $datosDet = [
            'cotizacion_id'=>$cotizacionId,
            'producto_id'=>$d['producto_id'],
            'producto_nombre'=>$d['nombre'],
            'cantidad'=>$d['cantidad'],
            'precio'=>$d['precio'],
            'precio_unitario'=>$d['precio'],
            'subtotal'=>$d['subtotal'],
            'creado_en'=>date("Y-m-d H:i:s")
        ];

        insertarDinamico($pdo, "cotizacion_detalle", $datosDet);
    }

    $pdo->commit();

    $lineas = [];
    $lineas[] = "Hola, deseo cotizar:";
    $lineas[] = "Cotización: ".$numero;
    $lineas[] = "Cliente: ".$nombre;
    $lineas[] = "Documento: ".$documento;
    $lineas[] = "Celular: ".$celular;
    $lineas[] = "";

    foreach($detalleFinal as $d){
        $lineas[] = "- ".$d['nombre']." x ".$d['cantidad']." = S/ ".number_format($d['subtotal'], 2);
    }

    $lineas[] = "";
    $lineas[] = "Total: S/ ".number_format($total, 2);
    $wa = "https://wa.me/51920137707?text=" . urlencode(implode("\n", $lineas));

    echo json_encode([
        "ok"=>true,
        "mensaje"=>"Cotización guardada correctamente.",
        "id"=>$cotizacionId,
        "numero"=>$numero,
        "total"=>$total,
        "whatsapp"=>$wa
    ], JSON_UNESCAPED_UNICODE);

}catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode([
        "ok"=>false,
        "mensaje"=>$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
