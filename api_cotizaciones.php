<?php
// api_cotizaciones.php

require 'db.php';

// Función auxiliar para enviar respuestas estructuradas en formato JSON
function enviarRespuesta($status, $data) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    if (!isset($pdo)) {
        enviarRespuesta(500, ['error' => 'Error de conexión con la base de datos.']);
    }

    $accion = $_REQUEST['accion'] ?? null;

    switch ($accion) {

        // ============================================
        // CREAR COTIZACIÓN
        // ============================================
        case 'crear':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                enviarRespuesta(405, ['error' => 'Método no permitido.']);
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input) || empty($input['cliente']) || empty($input['productos'])) {
                enviarRespuesta(400, ['error' => 'Datos incompletos. Faltan cliente o productos.']);
            }
            if (empty($input['cliente']['nombre'])) {
                enviarRespuesta(400, ['error' => 'El nombre del cliente es obligatorio.']);
            }

            $pdo->beginTransaction();

            try {
                $cliente_data     = $input['cliente'];
                $condiciones_data = $input['condiciones'];
                $productos_data   = $input['productos'];
                $totales_data     = $input['totales'];

                // 1. Buscar o crear cliente (Versión con actualización automática)
                $stmt_cliente = $pdo->prepare("SELECT id FROM clientes WHERE nombre_razon_social = ?");
                $stmt_cliente->execute([$cliente_data['nombre']]);
                $cliente_id = $stmt_cliente->fetchColumn();

                if (!$cliente_id) {
                    // Si el cliente no existe, lo creamos desde cero
                    $stmt_insert_cliente = $pdo->prepare(
                        "INSERT INTO clientes (nombre_razon_social, rut, telefono, correo)
                         VALUES (?, ?, ?, ?)"
                    );
                    $stmt_insert_cliente->execute([
                        $cliente_data['nombre'],
                        $cliente_data['rut']      ?? null,
                        $cliente_data['telefono'] ?? null,
                        $cliente_data['correo']   ?? null,
                    ]);
                    $cliente_id = $pdo->lastInsertId();
                } else {
                    // SECCIÓN CORREGIDA: Si el cliente ya existe, actualizamos sus datos opcionales 
                    // con la última información escrita en el formulario
                    $stmt_update_cliente = $pdo->prepare(
                        "UPDATE clientes 
                         SET rut = IFNULL(?, rut), 
                             telefono = IFNULL(?, telefono), 
                             correo = IFNULL(?, correo)
                         WHERE id = ?"
                    );
                    $stmt_update_cliente->execute([
                        !empty($cliente_data['rut']) ? $cliente_data['rut'] : null,
                        !empty($cliente_data['telefono']) ? $cliente_data['telefono'] : null,
                        !empty($cliente_data['correo']) ? $cliente_data['correo'] : null,
                        $cliente_id
                    ]);
                }

                // 2. Calcular días de validez para fecha_vencimiento
                $validez_texto = $condiciones_data['validez'] ?? '';
                $dias_validez  = 30; // Default de 30 días
                if (preg_match('/(\d+)/', $validez_texto, $match)) {
                    $dias_validez = intval($match[1]);
                }

                // 3. Insertar cotización base con estado 'Pendiente'
                $stmt_cot = $pdo->prepare(
                    "INSERT INTO cotizaciones
                        (cliente_id, subtotal, iva, total,
                         forma_de_pago, validez_oferta, fecha_entrega, garantia,
                         fecha_emision, fecha_vencimiento,
                         estado, descuento_global_porcentaje)
                     VALUES
                        (?, ?, ?, ?,
                         ?, ?, ?, ?,
                         CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY),
                         'Pendiente', 0)"
                );
                $stmt_cot->execute([
                    $cliente_id,
                    $totales_data['subtotal'],
                    $totales_data['iva'],
                    $totales_data['total'],
                    $condiciones_data['forma_pago'] ?? null,
                    $condiciones_data['validez']    ?? null,
                    $condiciones_data['entrega']    ?? null,
                    $condiciones_data['garantia']   ?? null,
                    $dias_validez,
                ]);
                $cotizacion_id = $pdo->lastInsertId();

                // 4. Insertar desglose de los ítems asociados
                $stmt_det = $pdo->prepare(
                    "INSERT INTO cotizacion_detalles
                        (cotizacion_id, producto_id, cantidad, precio_unitario_congelado, subtotal_linea)
                     VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($productos_data as $producto) {
                    $stmt_det->execute([
                        $cotizacion_id,
                        $producto['id'],
                        $producto['cantidad'],
                        $producto['precio_unitario'],
                        $producto['subtotal'],
                    ]);
                }

                $pdo->commit();

                enviarRespuesta(201, [
                    'success'       => true,
                    'id_cotizacion' => $cotizacion_id,
                    'mensaje'       => '¡Cotización guardada exitosamente!',
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                enviarRespuesta(500, ['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ============================================
        // LISTAR (Historial General)
        // ============================================
        case 'listar':
            $sql = "SELECT
                        c.id,
                        c.id                   AS folio,
                        cl.nombre_razon_social AS cliente_nombre,
                        c.fecha_emision,
                        c.total                AS monto_total,
                        c.estado
                    FROM cotizaciones c
                    JOIN clientes cl ON c.cliente_id = cl.id";

            $params = [];
            $where  = [];

            if (!empty($_GET['cliente'])) {
                $where[]  = "(cl.nombre_razon_social LIKE ? OR cl.rut LIKE ?)";
                $params[] = "%" . $_GET['cliente'] . "%";
                $params[] = "%" . $_GET['cliente'] . "%";
            }

            if (!empty($_GET['fecha'])) {
                $where[]  = "c.fecha_emision = ?";
                $params[] = $_GET['fecha'];
            }

            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            $sql .= " ORDER BY c.fecha_emision DESC, c.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            enviarRespuesta(200, $cotizaciones);
            break;

        // ============================================
        // OBTENER DETALLE COMPLETO (Estructura JSON)
        // ============================================
        case 'obtener':
            if (empty($_GET['id'])) {
                enviarRespuesta(400, ['error' => 'ID no proporcionado.']);
            }

            $id = intval($_GET['id']);

            $stmt_cab = $pdo->prepare(
                "SELECT
                    c.id,
                    c.fecha_emision,
                    c.fecha_vencimiento,
                    c.estado,
                    c.subtotal,
                    c.iva,
                    c.total,
                    c.descuento_global_porcentaje,
                    c.forma_de_pago,
                    c.validez_oferta,
                    c.fecha_entrega,
                    c.garantia,
                    cl.nombre_razon_social AS cliente_nombre,
                    cl.rut                 AS cliente_rut,
                    cl.telefono            AS cliente_telefono,
                    cl.correo              AS cliente_correo,
                    cl.direccion           AS cliente_direccion
                 FROM cotizaciones c
                 JOIN clientes cl ON c.cliente_id = cl.id
                 WHERE c.id = ?"
            );
            $stmt_cab->execute([$id]);
            $cabecera = $stmt_cab->fetch(PDO::FETCH_ASSOC);

            if (!$cabecera) {
                enviarRespuesta(404, ['error' => 'Cotización no encontrada.']);
            }

            $stmt_det = $pdo->prepare(
                "SELECT
                    cd.cantidad,
                    cd.precio_unitario_congelado AS precio_unitario,
                    cd.subtotal_linea            AS subtotal,
                    p.codigo_producto            AS codigo,
                    p.nombre                     AS producto_nombre
                 FROM cotizacion_detalles cd
                 JOIN productos p ON cd.producto_id = p.id
                 WHERE cd.cotizacion_id = ?"
            );
            $stmt_det->execute([$id]);
            $detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

            enviarRespuesta(200, [
                'cabecera' => $cabecera,
                'detalles' => $detalles,
            ]);
            break;

        // ============================================
        // ANULAR COTIZACIÓN
        // ============================================
        case 'anular':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                enviarRespuesta(405, ['error' => 'Método no permitido.']);
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                enviarRespuesta(400, ['error' => 'ID no proporcionado.']);
            }

            $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id = ?");
            if ($stmt->execute([$input['id']])) {
                enviarRespuesta(200, ['success' => true, 'mensaje' => 'Cotización anulada correctamente.']);
            } else {
                enviarRespuesta(500, ['error' => 'No se pudo anular.']);
            }
            break;

        // ============================================
        // GENERAR Y EXPORTAR PDF NATIVO (DOMPDF)
        // ============================================
        case 'generar_pdf':
            if (empty($_GET['id'])) {
                enviarRespuesta(400, ['error' => 'ID de cotización requerido.']);
            }

            $id = intval($_GET['id']);

            // 1. Consultar cabecera y datos del cliente asociados
            $stmt_cab = $pdo->prepare("
                SELECT c.*, cl.nombre_razon_social AS cliente_nombre, cl.rut AS cliente_rut,
                       cl.telefono AS cliente_telefono, cl.correo AS cliente_correo, cl.direccion AS cliente_direccion
                FROM cotizaciones c
                JOIN clientes cl ON c.cliente_id = cl.id
                WHERE c.id = ?
            ");
            $stmt_cab->execute([$id]);
            $cabecera = $stmt_cab->fetch(PDO::FETCH_ASSOC);

            if (!$cabecera) {
                enviarRespuesta(404, ['error' => 'Cotización no encontrada.']);
            }

            // 2. Consultar el desglose de productos asociados a la cotización
            $stmt_det = $pdo->prepare("
                SELECT cd.*, p.codigo_producto AS codigo, p.nombre AS producto_nombre
                FROM cotizacion_detalles cd
                JOIN productos p ON cd.producto_id = p.id
                WHERE cd.cotizacion_id = ?
            ");
            $stmt_det->execute([$id]);
            $detalles = $stmt_det->fetchAll(PDO::FETCH_ASSOC);

            // 3. Lógica para cargar e incrustar la foto del logo en Base64
            $rutaLogo = 'logo.png'; 
            $logoBase64 = '';

            if (file_exists($rutaLogo)) {
                $dataLogo = file_get_contents($rutaLogo);
                $tipoLogo = pathinfo($rutaLogo, PATHINFO_EXTENSION);
                $logoBase64 = 'data:image/' . $tipoLogo . ';base64,' . base64_encode($dataLogo);
            }
            
            // Si la foto no existe por algún motivo, usaremos un logo de respaldo SVG integrado para que no falle
            if (empty($logoBase64)) {
                $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode("<svg viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'><rect width='100' height='100' rx='15' fill='#1a365d'/><circle cx='50' cy='50' r='20' fill='#fff'/></svg>");
            }

            // 4. Inicializar e instanciar Dompdf a través del Autoloader de Composer
            require_once 'vendor/autoload.php'; 
            
            unset($dompdf); 
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); 

            $dompdf = new \Dompdf\Dompdf($options);

            // 5. Construcción dinámica de filas HTML con formato de moneda chilena
            $tabla_items_html = "";
            $contador = 1;
            foreach ($detalles as $item) {
                $precio_formateado = number_format($item['precio_unitario_congelado'], 0, ',', '.');
                $subtotal_formateado = number_format($item['subtotal_linea'], 0, ',', '.');
                
                $tabla_items_html .= "
                <tr>
                    <td style='text-align: center;'>" . $contador . "</td>
                    <td><strong>" . $item['producto_nombre'] . "</strong><br><small style='color:#718096;'>Código: " . $item['codigo'] . "</small></td>
                    <td style='text-align: center;'>" . $item['cantidad'] . "</td>
                    <td style='text-align: right;'>\$" . $precio_formateado . "</td>
                    <td style='text-align: right;'>\$" . $subtotal_formateado . "</td>
                </tr>";
                $contador++;
            }

            // Formatear montos financieros globales
            $subtotal_final = number_format($cabecera['subtotal'], 0, ',', '.');
            $iva_final = number_format($cabecera['iva'], 0, ',', '.');
            $total_final = number_format($cabecera['total'], 0, ',', '.');
            
            // Tratamiento de fechas legibles locales
            $fecha_emision_legible = date("d-m-Y", strtotime($cabecera['fecha_emision']));
            $fecha_venc_legible = date("d-m-Y", strtotime($cabecera['fecha_vencimiento']));
            $folio_formateado = "COT-" . str_pad($cabecera['id'], 4, '0', STR_PAD_LEFT);

            $cliente_rut = $cabecera['cliente_rut'] ? $cabecera['cliente_rut'] : '—';
            $cliente_telefono = $cabecera['cliente_telefono'] ? $cabecera['cliente_telefono'] : '—';
            $cliente_correo = $cabecera['cliente_correo'] ? $cabecera['cliente_correo'] : '—';
            $forma_pago = $cabecera['forma_de_pago'] ? $cabecera['forma_de_pago'] : '—';
            $validez_oferta = $cabecera['validez_oferta'] ? $cabecera['validez_oferta'] : '—';
            $fecha_entrega = $cabecera['fecha_entrega'] ? $cabecera['fecha_entrega'] : '—';
            $garantia = $cabecera['garantia'] ? $cabecera['garantia'] : '—';

            // 6. Definición de la maqueta HTML/CSS del comprobante PDF con la información real de ELECTRIFER
            $html_content = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    @page { size: A4; margin: 15mm; }
                    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; font-size: 10pt; line-height: 1.4; }
                    .header-container { position: relative; border-bottom: 2px solid #edf2f7; padding-bottom: 15px; margin-bottom: 20px; }
                    .company-info { width: 60%; float: left; }
                    .company-name { font-size: 18pt; font-weight: bold; color: #1a365d; margin: 0; letter-spacing: 0.5px; }
                    .company-subtitle { font-size: 10pt; font-style: italic; color: #4a5568; margin: 2px 0 6px 0; font-weight: 500; }
                    .company-details { font-size: 8.5pt; color: #4a5568; line-height: 1.5; }
                    .logo-container { width: 180px; height: 110px; float: right; text-align: right; }
                    .clear { clear: both; }
                    .document-title { font-size: 13pt; font-weight: bold; color: #2b6cb0; text-transform: uppercase; margin-bottom: 15px; }
                    .meta-table { width: 100%; margin-bottom: 25px; border-spacing: 0; }
                    .meta-cell { width: 50%; vertical-align: top; font-size: 9.5pt; }
                    .client-box { background-color: #f7fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; }
                    table.items-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
                    table.items-table th { background-color: #1a365d; color: white; padding: 8px; font-size: 9pt; text-transform: uppercase; text-align: left; }
                    table.items-table td { padding: 9px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9pt; }
                    table.items-table tr:nth-child(even) td { background-color: #fcfdfd; }
                    .totals-wrapper { width: 100%; margin-top: 10px; }
                    .totals-box { width: 35%; float: right; font-size: 10pt; }
                    .grand-total { font-weight: bold; color: #1a365d; background-color: #ebf8ff; padding: 6px; }
                    .footer-tables { width: 100%; margin-top: 35px; border-spacing: 0; page-break-inside: avoid; }
                    .footer-cell { width: 50%; vertical-align: top; }
                    .terms-box { font-size: 8.5pt; color: #4a5568; padding-right: 15px; }
                    .bank-box { background-color: #f7fafc; padding: 12px; border-radius: 6px; border: 1px solid #cbd5e0; font-size: 8.5pt; color: #2d3748; }
                    .section-title { font-weight: bold; color: #2c5282; border-bottom: 1px solid #cbd5e0; padding-bottom: 3px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
                </style>
            </head>
            <body>

                <div class='header-container'>
                    <div class='company-info'>
                        <h1 class='company-name'>ELECTRIFER</h1>
                        <div class='company-subtitle'>Comercializadora de Productos Eléctricos</div>
                        <p class='company-details'>
                            <strong>RUT:</strong> 8.910.136-0<br>
                            <strong>Dirección:</strong> Diagonal 8 Oriente 2886, Talca<br>
                            <strong>Celular:</strong> +56 9 9231 8335<br>
                            <strong>Contacto:</strong> contacto@electrifer.cl | electrifer@gmail.com
                        </p>
                    </div>
                    <div class='logo-container'>
                        <img src='" . $logoBase64 . "' style='max-height: 110px; width: auto;' alt='Logo ELECTRIFER'>
                    </div>
                    <div class='clear'></div>
                </div>

                <div class='document-title'>Cotización N° " . $folio_formateado . "</div>

                <table class='meta-table'>
                    <tr>
                        <td class='meta-cell' style='padding-right: 10px;'>
                            <div class='client-box'>
                                <strong style='color: #2c5282;'>DATOS DEL CLIENTE</strong><br style='margin-bottom:4px;'>
                                <strong>Nombre:</strong> " . $cabecera['cliente_nombre'] . "<br>
                                <strong>RUT:</strong> " . $cliente_rut . "<br>
                                <strong>Teléfono:</strong> " . $cliente_telefono . "<br>
                                <strong>Correo:</strong> " . $cliente_correo . "
                            </div>
                        </td>
                        <td class='meta-cell' style='padding-left: 10px; vertical-align: middle;'>
                            <strong>Fecha Emisión:</strong> " . $fecha_emision_legible . "<br>
                            <strong>Válido Hasta:</strong> " . $fecha_venc_legible . "<br>
                            <strong>Forma de Pago:</strong> " . $forma_pago . "<br>
                            <strong>Autorizado por:</strong> Patricio Zuñiga
                        </td>
                    </tr>
                </table>

                <table class='items-table'>
                    <thead>
                        <tr>
                            <th style='width: 8%; text-align: center;'>Item</th>
                            <th style='width: 52%;'>Descripción del Producto</th>
                            <th style='width: 10%; text-align: center;'>Cant.</th>
                            <th style='width: 15%; text-align: right;'>Precio Unit.</th>
                            <th style='width: 15%; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $tabla_items_html . "
                    </tbody>
                </table>

                <div class='totals-wrapper'>
                    <div class='totals-box'>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 5px 0;'>Subtotal Neto:</td>
                                <td style='text-align: right;'>\$" . $subtotal_final . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 5px 0; border-bottom: 1px solid #edf2f7;'>IVA (19%):</td>
                                <td style='text-align: right; border-bottom: 1px solid #edf2f7;'>\$" . $iva_final . "</td>
                            </tr>
                            <tr class='grand-total'>
                                <td style='padding: 6px;'>TOTAL:</td>
                                <td style='text-align: right; padding: 6px;'>\$" . $total_final . "</td>
                            </tr>
                        </table>
                    </div>
                    <div class='clear'></div>
                </div>

                <table class='footer-tables'>
                    <tr>
                        <td class='footer-cell'>
                            <div class='terms-box'>
                                <div class='section-title'>Condiciones Comerciales</div>
                                <strong>Validez de la oferta:</strong> " . $validez_oferta . "<br>
                                <strong>Plazo de Entrega:</strong> " . $fecha_entrega . "<br>
                                <strong>Garantía:</strong> " . $garantia . "<br>
                                <p style='font-size: 7.5pt; margin-top: 10px; color:#718096;'>* Este documento es una cotización formal y no representa una transferencia de inventario definitiva ni una obligación tributaria.</p>
                            </div>
                        </td>
                        <td class='footer-cell'>
                            <div class='bank-box'>
                                <div class='section-title'>Datos de Transferencia</div>
                                <strong>Banco:</strong> Banco Estado<br>
                                <strong>Tipo de Cuenta:</strong> Cuenta Corriente<br>
                                <strong>N° de Cuenta:</strong> 38300018243<br>
                                <strong>Nombre:</strong> Patricio Zuñiga<br>
                                <strong>RUT:</strong> 8.910.136-0<br>
                                <strong>Correo:</strong> contacto@electrifer.cl
                            </div>
                        </td>
                    </tr>
                </table>

            </body>
            </html>
            ";

            // 7. Inyección de las cabeceras HTTP específicas para el flujo binario PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="Cotizacion_' . $id . '.pdf"');

            $dompdf->loadHtml($html_content);
            $dompdf->render();
            
            echo $dompdf->output();
            exit;
            break;

        default:
            enviarRespuesta(400, ['error' => 'Acción no válida.']);
            break;
    }

} catch (Exception $e) {
    enviarRespuesta(500, ['error' => 'Error de servidor: ' . $e->getMessage()]);
}
?>