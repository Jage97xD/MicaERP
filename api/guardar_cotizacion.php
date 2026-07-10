<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/db.php";
if(session_status() === PHP_SESSION_NONE){ session_start(); }

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) { echo json_encode(["ok"=>false,"mensaje"=>"Datos inválidos"]); exit; }

$cliente = $data["cliente"] ?? [];
$items = $data["items"] ?? [];
if (count($items) === 0) { echo json_encode(["ok"=>false,"mensaje"=>"La cotización está vacía"]); exit; }

try {
    $pdo->beginTransaction();

    $nombre = trim($cliente["nombre"] ?? "");
    $documento = trim($cliente["documento"] ?? "");
    $celular = trim($cliente["celular"] ?? "");

    if ($nombre === "" || $documento === "") {
        throw new Exception("Debe ingresar nombre y documento.");
    }

    $stmtCliente = $pdo->prepare("SELECT id FROM clientes WHERE documento = ? LIMIT 1");
    $stmtCliente->execute([$documento]);
    $clienteExistente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if ($clienteExistente) {
        $clienteId = $clienteExistente["id"];
        $upd = $pdo->prepare("UPDATE clientes SET nombre = ?, celular = ? WHERE id = ?");
        $upd->execute([$nombre, $celular, $clienteId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO clientes (nombre, documento, celular) VALUES (?, ?, ?)");
        $ins->execute([$nombre, $documento, $celular]);
        $clienteId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("
        INSERT INTO cotizaciones
        (cliente_id, cliente_web_id, nombre_cliente, documento, celular, correo, tipo_entrega, destino, subtotal, envio, total, comentarios, estado, pago_validado, tracking_actualizado_en)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente de revisión', 0, NOW())
    ");

    $stmt->execute([
        $clienteId,
        $_SESSION['cliente_web_id'] ?? null,
        $nombre,
        $documento,
        $celular,
        $cliente["correo"] ?? ($_SESSION['cliente_web_correo'] ?? ""),
        $cliente["tipo_entrega"] ?? "",
        $cliente["destino"] ?? "",
        $data["subtotal"] ?? 0,
        $data["envio"] ?? 0,
        $data["total"] ?? 0,
        $cliente["comentarios"] ?? ""
    ]);

    $cotizacionId = $pdo->lastInsertId();

    $detalle = $pdo->prepare("
        INSERT INTO cotizacion_detalle
        (cotizacion_id, producto_id, producto_nombre, cantidad, precio, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $detalle->execute([
            $cotizacionId,
            $item["id"],
            $item["nombre"],
            $item["qty"],
            $item["precio"],
            $item["subtotal"]
        ]);
    }

    $pdo->commit();
    echo json_encode(["ok"=>true,"mensaje"=>"Cotización guardada","cotizacion_id"=>$cotizacionId,"cliente_id"=>$clienteId], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["ok"=>false,"mensaje"=>"Error al guardar cotización: ".$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>