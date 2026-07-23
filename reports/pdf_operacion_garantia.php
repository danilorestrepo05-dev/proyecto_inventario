<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
include(__DIR__ . '/../config/csrf.php');
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../menu.php?error=Token CSRF inválido");
    exit();
}
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require(__DIR__ . '/../fpdf/fpdf.php');

$id_servicio = intval($_POST['id_servicio'] ?? 0);
$id_trabajo  = intval($_POST['id_trabajo'] ?? 0);
$incluir_garantia = intval($_POST['incluir_garantia'] ?? 0);

if ($id_servicio <= 0 && $id_trabajo <= 0) {
    header("Location: ../views/reparaciones.php?error=ID de servicio o trabajo inválido");
    exit();
}

$modo_servicio = ($id_servicio > 0);

if ($modo_servicio) {
    $sql = "SELECT s.*, c.nombre, c.apellido, c.identificacion, c.tipo_identificacion, c.telefono, c.correo
            FROM servicio s LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente WHERE s.ID_servicio = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_servicio);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows == 0) {
        mysqli_close($conn);
        header("Location: ../views/reparaciones.php?error=Servicio no encontrado");
        exit();
    }
    $servicio = $resultado->fetch_assoc();

    $sql_disp = "SELECT * FROM dispositivo_servicio WHERE ID_servicio = ?";
    $stmt_disp = $conn->prepare($sql_disp);
    $stmt_disp->bind_param("i", $id_servicio);
    $stmt_disp->execute();
    $result_disp = $stmt_disp->get_result();
    $stmt_disp->close();

    $dispositivos = [];
    while ($d = $result_disp->fetch_assoc()) {
        $dispositivos[] = $d;
    }

    $trabajos_por_dispositivo = [];
    $garantias_por_dispositivo = [];
    $sql_trab = "SELECT t.*, g.ID_garantia, g.dias AS gar_dias, g.fecha_inicio AS gar_fecha_inicio, g.fecha_fin AS gar_fecha_fin
                 FROM trabajo t
                 LEFT JOIN garantia g ON g.ID_trabajo = t.ID_trabajo
                 WHERE t.ID_dispositivo = ?";
    $stmt_trab = $conn->prepare($sql_trab);
    foreach ($dispositivos as $d) {
        $stmt_trab->bind_param("i", $d['ID_dispositivo']);
        $stmt_trab->execute();
        $r_trab = $stmt_trab->get_result();
        $trabajos = [];
        $garantias_disp = [];
        while ($t = $r_trab->fetch_assoc()) {
            $trabajos[] = $t;
            if (!empty($t['gar_dias'])) {
                $garantias_disp[] = $t;
            }
        }
        $trabajos_por_dispositivo[$d['ID_dispositivo']] = $trabajos;
        $garantias_por_dispositivo[$d['ID_dispositivo']] = $garantias_disp;
    }
    $stmt_trab->close();

    $sql_rep = "SELECT rr.*, p.nombre AS producto_nombre, g.fecha_fin AS gar_trabajo_fin
                FROM reparacion_repuesto rr
                INNER JOIN trabajo t ON rr.ID_trabajo = t.ID_trabajo
                INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                LEFT JOIN producto p ON rr.ID_producto = p.ID_producto
                LEFT JOIN garantia g ON g.ID_trabajo = t.ID_trabajo
                WHERE ds.ID_servicio = ?";
    $stmt_rep = $conn->prepare($sql_rep);
    $stmt_rep->bind_param("i", $id_servicio);
    $stmt_rep->execute();
    $result_rep = $stmt_rep->get_result();
    $stmt_rep->close();

    $sql_prog = "SELECT pi.*, g.fecha_fin AS gar_trabajo_fin
                 FROM programa_instalado pi
                 INNER JOIN trabajo t ON pi.ID_trabajo = t.ID_trabajo
                 INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                 LEFT JOIN garantia g ON g.ID_trabajo = t.ID_trabajo
                 WHERE ds.ID_servicio = ?";
    $stmt_prog = $conn->prepare($sql_prog);
    $stmt_prog->bind_param("i", $id_servicio);
    $stmt_prog->execute();
    $result_prog = $stmt_prog->get_result();
    $stmt_prog->close();

} else {

    $sql = "SELECT t.*, ds.dispositivo, ds.marca, ds.modelo, ds.numero_serie,
                   s.ID_servicio, s.nombre AS servicio_nombre,
                   c.nombre, c.apellido, c.identificacion, c.tipo_identificacion, c.telefono, c.correo,
                   g.ID_garantia, g.dias AS gar_dias, g.fecha_inicio AS gar_fecha_inicio, g.fecha_fin AS gar_fecha_fin
            FROM trabajo t
            INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
            INNER JOIN servicio s ON ds.ID_servicio = s.ID_servicio
            LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
            LEFT JOIN garantia g ON g.ID_trabajo = t.ID_trabajo
            WHERE t.ID_trabajo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_trabajo);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();

    if ($resultado->num_rows == 0) {
        mysqli_close($conn);
        header("Location: ../views/reparaciones.php?error=Trabajo no encontrado");
        exit();
    }
    $trabajo = $resultado->fetch_assoc();

    $sql_rep = "SELECT rr.*, p.nombre AS producto_nombre, g.fecha_fin AS gar_trabajo_fin
                FROM reparacion_repuesto rr
                LEFT JOIN producto p ON rr.ID_producto = p.ID_producto
                LEFT JOIN garantia g ON g.ID_trabajo = rr.ID_trabajo
                WHERE rr.ID_trabajo = ?";
    $stmt_rep = $conn->prepare($sql_rep);
    $stmt_rep->bind_param("i", $id_trabajo);
    $stmt_rep->execute();
    $result_rep = $stmt_rep->get_result();
    $stmt_rep->close();

    $sql_prog = "SELECT pi.*, g.fecha_fin AS gar_trabajo_fin
                 FROM programa_instalado pi
                 LEFT JOIN garantia g ON g.ID_trabajo = pi.ID_trabajo
                 WHERE pi.ID_trabajo = ?";
    $stmt_prog = $conn->prepare($sql_prog);
    $stmt_prog->bind_param("i", $id_trabajo);
    $stmt_prog->execute();
    $result_prog = $stmt_prog->get_result();
    $stmt_prog->close();
}

mysqli_close($conn);

class PDF extends FPDF {
    function Header() {
    }
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, utf8_decode('Cel: 319 748 99 30 | 301 506 04 35'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('Fredonia - Antioquia'), 0, 1, 'C');
        $this->Cell(0, 4, utf8_decode('compumasterld@gmail.com'), 0, 1, 'C');
        $this->SetY(-6);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 4, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
}

function draw_header_bar($pdf, $title) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(26, 32, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, utf8_decode('  ' . $title), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
}

function draw_client_info($pdf, $row) {
    draw_header_bar($pdf, 'INFORMACIÓN DEL CLIENTE');
    $pdf->SetFont('Arial', '', 9);
    $cliente_nombre = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''));
    $pdf->Cell(40, 6, utf8_decode('Cliente:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($cliente_nombre), 0, 1);

    $ident_texto = '';
    if (!empty($row['identificacion'])) {
        $tipo = $row['tipo_identificacion'] ?? '';
        if ($tipo == 'nit') {
            $ident_texto = 'NIT ' . $row['identificacion'];
        } elseif ($tipo == 'cc') {
            $ident_texto = 'CC ' . $row['identificacion'];
        } elseif ($tipo == 'otro') {
            $ident_texto = 'ID: ' . $row['identificacion'];
        }
    }
    if ($ident_texto) {
        $pdf->Cell(40, 6, utf8_decode('Identificación:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($ident_texto), 0, 1);
    }
    if (!empty($row['telefono'])) {
        $pdf->Cell(40, 6, utf8_decode('Teléfono:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($row['telefono']), 0, 1);
    }
    if (!empty($row['correo'])) {
        $pdf->Cell(40, 6, utf8_decode('Correo:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($row['correo']), 0, 1);
    }
    $pdf->Ln(3);
}

function draw_device_section($pdf, $disp, $garantias_trabajo = [], $incluir_garantia = false) {
    draw_header_bar($pdf, 'INFORMACIÓN DEL DISPOSITIVO');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(40, 6, utf8_decode('Dispositivo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($disp['dispositivo'] ?? ''), 0, 1);
    $pdf->Cell(40, 6, utf8_decode('Marca / Modelo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode(trim(($disp['marca'] ?? '') . ' ' . ($disp['modelo'] ?? ''))), 0, 1);
    $pdf->Cell(40, 6, utf8_decode('Número de Serie:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($disp['numero_serie'] ?? '-'), 0, 1);

    if ($incluir_garantia && !empty($garantias_trabajo)) {
        $pdf->Ln(1);
        foreach ($garantias_trabajo as $g) {
            $dias = $g['gar_dias'];
            $fecha_inicio = date('d/m/Y', strtotime($g['gar_fecha_inicio']));
            $fecha_fin = date('d/m/Y', strtotime($g['gar_fecha_fin']));
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(0, 100, 0);
            $pdf->Cell(40, 6, utf8_decode('Garantía mano de obra:'), 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(0, 6, utf8_decode("{$dias} días ({$fecha_inicio} - {$fecha_fin})"), 0, 1);
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    $pdf->Ln(3);
}

function draw_diagnostico($pdf, $trabajo_row) {
    if (!empty($trabajo_row['diagnostico'])) {
        draw_header_bar($pdf, 'DIAGNÓSTICO FINAL');
        $pdf->SetFont('Arial', '', 9);
        $tipo_trabajo = $trabajo_row['tipo_trabajo'] ?? 'General';
        $pdf->Cell(40, 6, utf8_decode('Tipo:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($tipo_trabajo), 0, 1);
        $pdf->Ln(1);
        $pdf->MultiCell(0, 5, utf8_decode($trabajo_row['diagnostico']));
        $pdf->Ln(3);
    }
}

function draw_repuestos_table($pdf, $result_rep, $incluir_garantia) {
    if ($result_rep->num_rows == 0) return;

    draw_header_bar($pdf, 'REPUESTOS UTILIZADOS');

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(220, 220, 220);

    $col_ancho_prod = 55;
    $col_ancho_cant = 15;
    $col_ancho_pu = 30;
    $col_ancho_sub = 30;

    if ($incluir_garantia) {
        $col_ancho_gar = 30;
    } else {
        $col_ancho_gar = 0;
    }

    $pdf->Cell($col_ancho_prod, 6, utf8_decode('Producto'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_cant, 6, utf8_decode('Cant.'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_pu, 6, utf8_decode('P. Unitario'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_sub, 6, utf8_decode('Subtotal'), 1, 0, 'C', true);
    if ($incluir_garantia) {
        $pdf->Cell($col_ancho_gar, 6, utf8_decode('Garantía hasta'), 1, 1, 'C', true);
    } else {
        $pdf->Ln();
    }

    $pdf->SetFont('Arial', '', 8);
    while ($r = $result_rep->fetch_assoc()) {
        $subtotal = $r['cantidad'] * $r['precio_unitario'];
        $pdf->Cell($col_ancho_prod, 5, utf8_decode(substr($r['producto_nombre'], 0, 28)), 1, 0, 'L');
        $pdf->Cell($col_ancho_cant, 5, $r['cantidad'], 1, 0, 'C');
        $pdf->Cell($col_ancho_pu, 5, '$' . number_format($r['precio_unitario'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell($col_ancho_sub, 5, '$' . number_format($subtotal, 0, ',', '.'), 1, 0, 'R');
        if ($incluir_garantia) {
            $gar_texto = 'Sin garantía';
            if (!empty($r['garantia_proveedor_dias']) && $r['garantia_proveedor_dias'] > 0 && !empty($r['gar_trabajo_fin'])) {
                $gar_texto = date('d/m/Y', strtotime($r['gar_trabajo_fin']));
            }
            $pdf->Cell($col_ancho_gar, 5, $gar_texto, 1, 1, 'C');
        } else {
            $pdf->Ln();
        }
    }
    $pdf->Ln(3);
}

function draw_programas_table($pdf, $result_prog, $incluir_garantia) {
    if ($result_prog->num_rows == 0) return;

    draw_header_bar($pdf, 'PROGRAMAS INSTALADOS');

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(220, 220, 220);

    $col_ancho_nom = 40;
    $col_ancho_ver = 20;
    $col_ancho_cant = 15;
    $col_ancho_pu = 25;
    $col_ancho_sub = 25;

    if ($incluir_garantia) {
        $col_ancho_gar = 30;
    } else {
        $col_ancho_gar = 0;
    }

    $pdf->Cell($col_ancho_nom, 6, utf8_decode('Programa'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_ver, 6, utf8_decode('Versión'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_cant, 6, utf8_decode('Cant.'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_pu, 6, utf8_decode('P. Unitario'), 1, 0, 'C', true);
    $pdf->Cell($col_ancho_sub, 6, utf8_decode('Subtotal'), 1, 0, 'C', true);
    if ($incluir_garantia) {
        $pdf->Cell($col_ancho_gar, 6, utf8_decode('Garantía hasta'), 1, 1, 'C', true);
    } else {
        $pdf->Ln();
    }

    $pdf->SetFont('Arial', '', 8);
    while ($p = $result_prog->fetch_assoc()) {
        $prog_cant = intval($p['cantidad'] ?? 1);
        $prog_sub = $prog_cant * floatval($p['costo'] ?? 0);
        $pdf->Cell($col_ancho_nom, 5, utf8_decode(substr($p['nombre'], 0, 22)), 1, 0, 'L');
        $pdf->Cell($col_ancho_ver, 5, utf8_decode($p['version'] ?? '-'), 1, 0, 'C');
        $pdf->Cell($col_ancho_cant, 5, $prog_cant, 1, 0, 'C');
        $pdf->Cell($col_ancho_pu, 5, '$' . number_format(floatval($p['costo'] ?? 0), 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell($col_ancho_sub, 5, '$' . number_format($prog_sub, 0, ',', '.'), 1, 0, 'R');
        if ($incluir_garantia) {
            $gar_texto = 'Sin garantía';
            if (!empty($p['gar_dias']) && !empty($p['gar_fecha_fin'])) {
                $gar_texto = date('d/m/Y', strtotime($p['gar_fecha_fin']));
            }
            $pdf->Cell($col_ancho_gar, 5, $gar_texto, 1, 1, 'C');
        } else {
            $pdf->Ln();
        }
    }
    $pdf->Ln(3);
}

function draw_terminos_condiciones($pdf, $datos, $incluir_garantia) {
    draw_header_bar($pdf, 'TÉRMINOS Y CONDICIONES');
    $pdf->SetFont('Arial', '', 8);

    $clausulas = [];
    $clausulas[] = "1. La garantía cubre únicamente el trabajo técnico realizado por nuestro personal autorizado.";
    $clausulas[] = "2. No aplica por mal uso, daños físicos por golpes, líquidos derramados, sobretensión eléctrica o manipulación por terceros ajenos a CompuMasterLD.";
    $clausulas[] = "3. Para hacer válida cualquier reclamación, el cliente debe presentar este certificado de servicio.";
    $clausulas[] = "4. El cliente podrá presentar reclamaciones dentro del plazo de garantía vigente. Una vez vencido este plazo, dispondrá de 30 días calendario adicionales para reportar fallas que no hubiese podido identificar en el uso normal del equipo.";
    $clausulas[] = "5. CompuMasterLD no se responsabiliza por datos perdidos durante el proceso de reparación.";

    if ($incluir_garantia) {
        $num = 6;
        if ($datos['tiene_gar_mano_obra']) {
            $clausulas[] = "{$num}. La mano de obra tiene una garantía individual por trabajo según se indica en cada sección de dispositivo. Los plazos cuentan a partir de la fecha de entrega.";
            $num++;
        }
        if ($datos['tiene_gar_repuestos']) {
            $clausulas[] = "{$num}. Los repuestos instalados tienen garantía del proveedor por el plazo indicado en cada ítem de la tabla de repuestos.";
            $num++;
        }
        if ($datos['sin_gar_repuestos']) {
            $clausulas[] = "{$num}. Algunos repuestos fueron instalados sin garantía por parte del proveedor, el cliente asume la responsabilidad de los mismos.";
            $num++;
        }
        if ($datos['tiene_gar_programas']) {
            $clausulas[] = "{$num}. Los programas instalados tienen garantía por el plazo indicado en cada ítem de la tabla de programas.";
            $num++;
        }
        if ($datos['sin_gar_programas']) {
            $clausulas[] = "{$num}. Algunos programas fueron instalados sin garantía, su uso queda bajo responsabilidad del cliente.";
            $num++;
        }
    }

    $texto = implode(' ', $clausulas);
    $pdf->MultiCell(0, 4, utf8_decode($texto));
    $pdf->Ln(3);
}

function calcular_datos_garantia($result_rep, $result_prog, $garantias_por_dispositivo = [], $trabajos_por_dispositivo = []) {
    $datos = [
        'tiene_gar_mano_obra' => false,
        'tiene_gar_repuestos' => false,
        'sin_gar_repuestos' => false,
        'tiene_gar_programas' => false,
        'sin_gar_programas' => false,
    ];

    if (!empty($garantias_por_dispositivo)) {
        foreach ($garantias_por_dispositivo as $gar_list) {
            if (!empty($gar_list)) {
                $datos['tiene_gar_mano_obra'] = true;
                break;
            }
        }
    }

    $result_rep->data_seek(0);
    while ($r = $result_rep->fetch_assoc()) {
        if (!empty($r['garantia_proveedor_dias']) && $r['garantia_proveedor_dias'] > 0 && !empty($r['gar_trabajo_fin'])) {
            $datos['tiene_gar_repuestos'] = true;
        } else {
            $datos['sin_gar_repuestos'] = true;
        }
    }

    $result_prog->data_seek(0);
    while ($p = $result_prog->fetch_assoc()) {
        if (!empty($p['gar_dias']) && !empty($p['gar_fecha_fin'])) {
            $datos['tiene_gar_programas'] = true;
        } else {
            $datos['sin_gar_programas'] = true;
        }
    }

    return $datos;
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$logo = __DIR__ . '/../assets/img/logo_pdf.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 60, 10, 0, 30);
    $pdf->Ln(30);
}

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('CERTIFICADO DE TRABAJO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode('Fecha: ' . date('d/m/Y H:i')), 0, 1, 'C');
$pdf->Ln(4);

if ($modo_servicio) {

    draw_client_info($pdf, $servicio);

    foreach ($dispositivos as $disp) {
        $gar_disp = $garantias_por_dispositivo[$disp['ID_dispositivo']] ?? [];
        draw_device_section($pdf, $disp, $gar_disp, $incluir_garantia);

        $trabajos = $trabajos_por_dispositivo[$disp['ID_dispositivo']] ?? [];
        foreach ($trabajos as $trab) {
            draw_diagnostico($pdf, $trab);
        }
    }

} else {

    draw_client_info($pdf, $trabajo);

    $gar_trabajo = [];
    if (!empty($trabajo['gar_dias'])) {
        $gar_trabajo[] = $trabajo;
    }
    draw_device_section($pdf, $trabajo, $gar_trabajo, $incluir_garantia);

    draw_diagnostico($pdf, $trabajo);
}

draw_repuestos_table($pdf, $result_rep, $incluir_garantia);
draw_programas_table($pdf, $result_prog, $incluir_garantia);

$datos_gar = calcular_datos_garantia($result_rep, $result_prog, $garantias_por_dispositivo ?? [], $trabajos_por_dispositivo ?? []);
draw_terminos_condiciones($pdf, $datos_gar, $incluir_garantia);

$pdf->Output('I', 'certificado_trabajo_' . date('Y-m-d') . '.pdf');
