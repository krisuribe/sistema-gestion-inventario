<?php
// api_productos.php

require 'db.php'; // Incluir la conexión

/**
 * Función para generar el nuevo código de producto (ej: TOR001)
 */
function generarCodigoProducto($pdo, $nombreProducto) {
    $prefijo = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nombreProducto), 0, 3));
    if (strlen($prefijo) < 3) {
        $prefijo = str_pad($prefijo, 3, 'X'); 
    }

    $sql = "SELECT codigo_producto FROM productos 
            WHERE codigo_producto LIKE ? 
            ORDER BY codigo_producto DESC 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["$prefijo%"]);
    $ultimoCodigo = $stmt->fetchColumn();

    $siguienteNumero = 1;
    if ($ultimoCodigo) {
        $numero = (int)substr($ultimoCodigo, 3);
        $siguienteNumero = $numero + 1;
    }

    $nuevoCodigo = $prefijo . str_pad($siguienteNumero, 3, '0', STR_PAD_LEFT);
    return $nuevoCodigo;
}


// --- Lógica principal de la API ---

$accion = $_GET['accion'] ?? 'listar';
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($accion) {
        
        case 'listar':
            $busqueda = $_GET['busqueda'] ?? '';
            $categoriaId = $_GET['categoriaId'] ?? '';
            $proveedorId = $_GET['proveedorId'] ?? '';

            $sql = "SELECT p.id, p.codigo_producto, p.nombre, p.descripcion, p.categoria_id, p.proveedor_id, p.existencia, p.precio_costo,
               c.nombre AS categoria_nombre, pr.nombre AS proveedor_nombre 
                    FROM productos p
                    JOIN categorias c ON p.categoria_id = c.id
                    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
                    WHERE 1=1"; 
            $params = [];

            if (!empty($busqueda)) {
                $sql .= " AND (p.codigo_producto LIKE ? OR p.nombre LIKE ?)";
                $params[] = "%$busqueda%";
                $params[] = "%$busqueda%";
            }
            if (!empty($categoriaId)) {
                $sql .= " AND p.categoria_id = ?";
                $params[] = $categoriaId;
            }
            if (!empty($proveedorId)) {
                $sql .= " AND p.proveedor_id = ?";
                $params[] = $proveedorId;
            }

            $sql .= " ORDER BY p.nombre";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll());
            break;

        case 'autocompletar':
            $termino = $_GET['termino'] ?? '';
            if (empty($termino)) {
                echo json_encode([]);
                exit;
            }
            
            $sql = "SELECT id, codigo_producto, nombre, precio_costo 
                    FROM productos 
                    WHERE nombre LIKE ? OR codigo_producto LIKE ?
                    LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["%$termino%", "%$termino%"]);
            echo json_encode($stmt->fetchAll());
            break;

        // ============================================
        // ACCIÓN 'CREAR' REPARADA
        // ============================================
        case 'crear':
            extract($input); 

            // SOPORTE HÍBRIDO: Si el frontend manda 'categoria_id', lo asignamos a $categoriaId de forma segura
            if (!isset($categoriaId) && isset($input['categoria_id'])) {
                $categoriaId = $input['categoria_id'];
            }
            if (!isset($proveedorId) && isset($input['proveedor_id'])) {
                $proveedorId = $input['proveedor_id'];
            }

            // Validar campos obligatorios
            if (empty($nombre) || empty($categoriaId)) {
                http_response_code(400);
                throw new Exception('Nombre y Categoría son obligatorios');
            }
            if (!isset($existencia) || !is_numeric($existencia) || $existencia < 0) {
                http_response_code(400);
                throw new Exception('La existencia debe ser un número mayor o igual a cero');
            }
            if (empty($precioCosto) || !is_numeric($precioCosto) || $precioCosto <= 0) {
                http_response_code(400);
                throw new Exception('El precio de costo debe ser mayor a cero');
            }
            
            // Generar el nuevo código de producto
            $codigo_producto = generarCodigoProducto($pdo, $nombre);
            
            // Insertar registrando explícitamente el ID de la categoría
            $sql = "INSERT INTO productos (codigo_producto, nombre, descripcion, categoria_id, proveedor_id, existencia, precio_costo)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $codigo_producto,
                $nombre,
                $descripcion ?? '',
                intval($categoriaId),
                !empty($proveedorId) ? intval($proveedorId) : null,
                $existencia,
                $precioCosto
            ]);
            
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'codigo_producto' => $codigo_producto]);
            break;

        // ============================================
        // ACCIÓN 'ACTUALIZAR' REPARADA
        // ============================================
        case 'actualizar':
            extract($input); 

            // SOPORTE HÍBRIDO: Si el frontend manda 'categoria_id' en la actualización
            if (!isset($categoriaId) && isset($input['categoria_id'])) {
                $categoriaId = $input['categoria_id'];
            }
            if (!isset($proveedorId) && isset($input['proveedor_id'])) {
                $proveedorId = $input['proveedor_id'];
            }

            if (empty($id) || empty($nombre) || empty($categoriaId)) {
                http_response_code(400);
                throw new Exception('ID, Nombre y Categoría son obligatorios');
            }

            // Actualizar registrando explícitamente la categoría
            $sql = "UPDATE productos SET 
                        nombre = ?, 
                        descripcion = ?, 
                        categoria_id = ?, 
                        proveedor_id = ?, 
                        existencia = ?,
                        precio_costo = ?
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $descripcion ?? '',
                intval($categoriaId),
                !empty($proveedorId) ? intval($proveedorId) : null,
                $existencia,
                $precioCosto,
                $id
            ]);
            
            echo json_encode(['success' => true]);
            break;

        case 'actualizarExistencia':
            extract($input); 

            if (!isset($id) || !isset($existencia) || !is_numeric($existencia) || $existencia < 0) {
                http_response_code(400);
                throw new Exception('ID y una existencia válida son obligatorios');
            }

            $stmt = $pdo->prepare("UPDATE productos SET existencia = ? WHERE id = ?");
            $stmt->execute([$existencia, $id]);
            
            echo json_encode(['success' => true]);
            break;

        case 'eliminar':
            $id = $input['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                throw new Exception('El ID es obligatorio para eliminar');
            }

            $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>