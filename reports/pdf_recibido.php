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

// IDs enviados desde el formulario (solo uno debe tener valor)
$id_servicio = intval($_POST['id_servicio'] ?? 0);
$id_trabajo = intval($_POST['id_trabajo'] ?? 0);

if ($id_servicio <= 0 && $id_trabajo <= 0) {
    header("Location: ../views/reparaciones.php?error=Parámetro inválido");
    exit();
}

// Modo servicio: obtiene el servicio, sus dispositivos y trabajos asociados
if ($id_servicio > 0) {
    $sql = "SELECT s.*, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                   c.identificacion, c.tipo_identificacion, c.telefono, c.correo
            FROM servicio s
            LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
            WHERE s.ID_servicio = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_servicio);
    $stmt->execute();
    $serv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$serv) {
        mysqli_close($conn);
        header("Location: ../views/reparaciones.php?error=Servicio no encontrado");
        exit();
    }

    // Obtiene todos los dispositivos del servicio
    $sql_disp = "SELECT * FROM dispositivo_servicio WHERE ID_servicio = ? ORDER BY ID_dispositivo";
    $stmt_disp = $conn->prepare($sql_disp);
    $stmt_disp->bind_param("i", $id_servicio);
    $stmt_disp->execute();
    $result_disp = $stmt_disp->get_result();
    $stmt_disp->close();

    // Para cada dispositivo, carga sus trabajos asociados
    $dispositivos = [];
    while ($d = $result_disp->fetch_assoc()) {
        $sql_trab = "SELECT * FROM trabajo WHERE ID_dispositivo = ? ORDER BY ID_trabajo";
        $stmt_trab = $conn->prepare($sql_trab);
        $stmt_trab->bind_param("i", $d['ID_dispositivo']);
        $stmt_trab->execute();
        $result_trab = $stmt_trab->get_result();
        $d['trabajos'] = [];
        while ($t = $result_trab->fetch_assoc()) {
            $d['trabajos'][] = $t;
        }
        $stmt_trab->close();
        $dispositivos[] = $d;
    }
    mysqli_close($conn);

    $modo = 'servicio';
    $cliente_nombre = trim(($serv['cliente_nombre'] ?? '') . ' ' . ($serv['cliente_apellido'] ?? ''));
} else {
    // Modo trabajo individual: obtiene un solo trabajo con sus relaciones
    $sql = "SELECT t.*, ds.dispositivo, ds.marca, ds.modelo, ds.numero_serie,
                   s.ID_servicio,
                   c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                   c.identificacion, c.tipo_identificacion, c.telefono, c.correo
            FROM trabajo t
            LEFT JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
            LEFT JOIN servicio s ON ds.ID_servicio = s.ID_servicio
            LEFT JOIN cliente c ON s.ID_cliente = c.ID_cliente
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
    $trab = $resultado->fetch_assoc();
    mysqli_close($conn);

    $modo = 'trabajo';
    $cliente_nombre = trim(($trab['cliente_nombre'] ?? '') . ' ' . ($trab['cliente_apellido'] ?? ''));
}

// Clase PDF personalizada con acceso al umbral de salto de página
class PDF extends FPDF {
    function getPageBreakTrigger() { return $this->PageBreakTrigger; }
    // Cabecera vacía: se maneja manualmente
    function Header() {
    }
    // Pie de página con datos de contacto de la empresa
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

// Salta de página si no hay suficiente espacio vertical para el contenido
function verificar_cierre($pdf, $espacio) {
    if ($pdf->GetY() + $espacio > $pdf->getPageBreakTrigger()) {
        $pdf->AddPage();
        return true;
    }
    return false;
}

// Dibuja encabezado de sección con fondo oscuro y texto blanco
function render_seccion_header($pdf, $titulo) {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(26, 32, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, utf8_decode('  ' . $titulo), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
}

// Renderiza los datos del cliente: nombre, identificación, teléfono y correo
function render_cliente($pdf, $rep) {
    render_seccion_header($pdf, 'INFORMACIÓN DEL CLIENTE');

    $cliente_nombre = trim(($rep['cliente_nombre'] ?? '') . ' ' . ($rep['cliente_apellido'] ?? ''));

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(40, 6, utf8_decode('Cliente:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($cliente_nombre), 0, 1);

    // Formatea número de identificación con prefijo según tipo
    $ident_texto = '';
    if (!empty($rep['identificacion'])) {
        $tipo = $rep['tipo_identificacion'] ?? '';
        if ($tipo == 'nit') {
            $ident_texto = 'NIT ' . $rep['identificacion'];
        } elseif ($tipo == 'cc') {
            $ident_texto = 'CC ' . $rep['identificacion'];
        } elseif ($tipo == 'otro') {
            $ident_texto = 'ID: ' . $rep['identificacion'];
        }
    }
    if ($ident_texto) {
        $pdf->Cell(40, 6, utf8_decode('Identificación:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($ident_texto), 0, 1);
    }
    if (!empty($rep['telefono'])) {
        $pdf->Cell(40, 6, utf8_decode('Teléfono:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($rep['telefono']), 0, 1);
    }
    if (!empty($rep['correo'])) {
        $pdf->Cell(40, 6, utf8_decode('Correo:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode($rep['correo']), 0, 1);
    }
    $pdf->Ln(3);
}

// Renderiza información del dispositivo: tipo, marca/modelo y número de serie
function render_dispositivo($pdf, $disp) {
    render_seccion_header($pdf, 'INFORMACIÓN DEL DISPOSITIVO');

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(40, 6, utf8_decode('Dispositivo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($disp['dispositivo'] ?? ''), 0, 1);

    $pdf->Cell(40, 6, utf8_decode('Marca / Modelo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode(trim(($disp['marca'] ?? '') . ' ' . ($disp['modelo'] ?? ''))), 0, 1);

    $pdf->Cell(40, 6, utf8_decode('Número de Serie:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($disp['numero_serie'] ?? '-'), 0, 1);

    $pdf->Cell(40, 6, utf8_decode('No. Servicio:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode('#' . $disp['ID_servicio']), 0, 1);
    $pdf->Ln(3);
}

// Renderiza el problema reportado y tipo de trabajo
function render_trabajo($pdf, $trab) {
    render_seccion_header($pdf, 'PROBLEMA REPORTADO');

    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(40, 6, utf8_decode('Tipo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode($trab['tipo_trabajo'] ?? ''), 0, 1);

    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode($trab['problema_reportado'] ?? ''));
    $pdf->Ln(5);
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// Logo de la empresa en el encabezado del PDF
$logo = __DIR__ . '/../assets/img/logo_pdf.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 60, 10, 0, 30);
    $pdf->Ln(30);
}

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('FICHA DE INGRESO'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode('Fecha: ' . date('d/m/Y H:i')), 0, 1, 'C');
$pdf->Ln(4);

// Sección de datos del cliente
render_cliente($pdf, $modo === 'servicio' ? $serv : $trab);

// Modo servicio: itera dispositivos y renderiza cada uno con sus trabajos
if ($modo === 'servicio') {
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(26, 32, 53);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 7, utf8_decode('  Servicio: #' . $id_servicio), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);

    foreach ($dispositivos as $disp) {
        verificar_cierre($pdf, 40);
        render_dispositivo($pdf, $disp);

        if (!empty($disp['trabajos'])) {
            foreach ($disp['trabajos'] as $trab) {
                verificar_cierre($pdf, 30);
                render_trabajo($pdf, $trab);
            }
        } else {
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 6, utf8_decode('  No hay trabajos registrados para este dispositivo'), 0, 1);
            $pdf->Ln(5);
        }
    }
// Modo trabajo: renderiza dispositivo, problema y referencia cruzada
} else {
    verificar_cierre($pdf, 40);
    render_dispositivo($pdf, $trab);

    verificar_cierre($pdf, 30);
    render_trabajo($pdf, $trab);

    render_seccion_header($pdf, 'REFERENCIA');

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(40, 6, utf8_decode('No. Trabajo:'), 0, 0);
    $pdf->Cell(0, 6, utf8_decode('#TRAB-' . $id_trabajo), 0, 1);

    if (!empty($trab['ID_servicio'])) {
        $pdf->Cell(40, 6, utf8_decode('No. Servicio:'), 0, 0);
        $pdf->Cell(0, 6, utf8_decode('#' . $trab['ID_servicio']), 0, 1);
    }
}

$pdf->Output('I', 'ficha_ingreso_' . date('Y-m-d') . '.pdf');
