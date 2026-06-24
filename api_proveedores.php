<?php
// api_proveedores.php

require 'db.php'; // Incluir la conexión

$accion = $_GET['accion'] ?? 'listar';
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($accion) {
        // RF-06: Listado de Proveedores
        case 'listar':
            $stmt = $pdo->query("SELECT * FROM proveedores ORDER BY nombre");
            echo json_encode($stmt->fetchAll());
            break;

        // RF-05: Registro de Proveedores
        case 'crear':
            $nombre = $input['nombre'] ?? '';
            $telefono = $input['telefono'] ?? '';
            $correo = $input['correo'] ?? '';

            if (empty($nombre) || empty($telefono) || empty($correo)) {
                http_response_code(400);
                throw new Exception('Nombre, teléfono y correo son obligatorios');
            }
            
            // Validar nombre único
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetch()) {
                http_response_code(409); // Conflict
                throw new Exception('El nombre del proveedor ya existe');
            }

            // Insertar
            $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, telefono, correo) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $telefono, $correo]);
            $id_insertado = $pdo->lastInsertId();
            
            echo json_encode(['id' => $id_insertado, 'nombre' => $nombre, 'telefono' => $telefono, 'correo' => $correo]);
            break;

        // RF-07: Actualización de Proveedores
        case 'actualizar':
            $id = $input['id'] ?? null;
            $nombre = $input['nombre'] ?? '';
            $telefono = $input['telefono'] ?? '';
            $correo = $input['correo'] ?? '';
            
            if (!$id || empty($nombre) || empty($telefono) || empty($correo)) {
                http_response_code(400);
                throw new Exception('ID, nombre, teléfono y correo son obligatorios');
            }

            // Validar nombre único (excluyendo el propio ID)
            $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE nombre = ? AND id != ?");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetch()) {
                http_response_code(409); // Conflict
                throw new Exception('El nombre del proveedor ya existe en otro registro');
            }

            // Actualizar
            $stmt = $pdo->prepare("UPDATE proveedores SET nombre = ?, telefono = ?, correo = ? WHERE id = ?");
            $stmt->execute([$nombre, $telefono, $correo, $id]);
            
            echo json_encode(['id' => $id, 'nombre' => $nombre, 'telefono' => $telefono, 'correo' => $correo]);
            break;

        // RF-08: Eliminación de Proveedores
        case 'eliminar':
            $id = $input['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                throw new Exception('El ID es obligatorio para eliminar');
            }

            // Validar que no tenga productos asociados
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE proveedor_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400); // Bad Request
                throw new Exception('No se puede eliminar el proveedor porque tiene productos asociados');
            }

            // Eliminar
            $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = ?");
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
    // El código HTTP ya fue seteado (400, 409, 500)
    echo json_encode(['error' => $e->getMessage()]);
}
?>