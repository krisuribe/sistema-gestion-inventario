<?php
// api_categorias.php

// 1. Incluir el archivo de conexión
require 'db.php';

// 2. Obtener la acción solicitada (listar, crear, actualizar, eliminar)
// Usamos 'GET' para 'accion' por simplicidad, aunque POST/PUT/DELETE serían semánticamente correctos.
$accion = $_GET['accion'] ?? 'listar'; // 'listar' por defecto

// 3. Manejar los datos de entrada
// json_decode(file_get_contents('php://input')) lee los datos JSON enviados por fetch
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($accion) {
        // RF-02: Listado de Categorías
        case 'listar':
            $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
            echo json_encode($stmt->fetchAll());
            break;

        // RF-01: Registro de Categorías
        case 'crear':
            $nombre = $input['nombre'] ?? '';
            if (empty($nombre)) {
                throw new Exception('El nombre de la categoría es obligatorio');
            }
            
            // Validar nombre único
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt->execute([$nombre]);
            if ($stmt->fetch()) {
                http_response_code(409); // Conflict
                throw new Exception('El nombre de la categoría ya existe');
            }

            // Insertar
            $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            $id_insertado = $pdo->lastInsertId();
            
            // Devolver el nuevo objeto creado
            echo json_encode(['id' => $id_insertado, 'nombre' => $nombre]);
            break;

        // RF-03: Actualización de Categorías
        case 'actualizar':
            $id = $input['id'] ?? null;
            $nombre = $input['nombre'] ?? '';
            
            if (!$id || empty($nombre)) {
                throw new Exception('ID y nombre son obligatorios para actualizar');
            }

            // Validar nombre único (excluyendo el propio ID)
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = ? AND id != ?");
            $stmt->execute([$nombre, $id]);
            if ($stmt->fetch()) {
                http_response_code(409); // Conflict
                throw new Exception('El nombre de la categoría ya existe en otro registro');
            }

            // Actualizar
            $stmt = $pdo->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
            $stmt->execute([$nombre, $id]);
            
            echo json_encode(['id' => $id, 'nombre' => $nombre]);
            break;

        // RF-04: Eliminación de Categorías
        case 'eliminar':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('El ID es obligatorio para eliminar');
            }

            // Validar que no tenga productos asociados (usamos la llave foránea)
            // La base de datos (CONSTRAINT) ya previene esto, pero es bueno verificar
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(400); // Bad Request
                throw new Exception('No se puede eliminar la categoría porque tiene productos asociados');
            }

            // Eliminar
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }

} catch (PDOException $e) {
    // Capturar errores de la base de datos
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Exception $e) {
    // Capturar otros errores (como validaciones)
    // El código HTTP ya puede haber sido seteado (409, 400)
    if (http_response_code() === 200) { // Si no se seteó un código específico
        http_response_code(400);
    }
    echo json_encode(['error' => $e->getMessage()]);
}

?>