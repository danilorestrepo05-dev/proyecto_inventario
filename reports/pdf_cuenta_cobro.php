<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
include(__DIR__ . '/../config/csrf.php');
if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../menu.php?error=Token CSRF invalido");
    exit();
}
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

require(__DIR__ . '/../fpdf/fpdf.php');

$empresa = [
    'nombre' => 'CompuMasterLD',
    'nit' => '1041149861-6',
    'banco' => 'Cuenta de ahorros Bancolombia Nro. 376-617464-84',
    'tel1' => '319 748 99 30',
    'tel2' => '301 506 04 35',
    'dir' => 'Fredonia - Antioquia',
    'ciudad' => 'Fredonia',
];

function u($char) {
    return chr($char);
}

function numero_a_letras($numero) {
    if ($numero == 0) return 'CERO';
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $especiales = ['VEINTIUNO', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO',
                   'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $numero = intval(round($numero));
    if ($numero < 0 || $numero > 999999999) return 'FUERA DE RANGO';
    $resultado = '';
    $millones = intval($numero / 1000000);
    $resto_millones = $numero % 1000000;
    $miles = intval($resto_millones / 1000);
    $unidades_final = $resto_millones % 1000;
    if ($millones > 0) {
        if ($millones == 1) { $resultado .= 'UN MILLON'; } else { $resultado .= numero_a_bajo_millon($millones) . ' MILLONES'; }
        if ($resto_millones > 0) $resultado .= ' ';
    }
    if ($miles > 0) {
        if ($miles == 1) { $resultado .= 'MIL'; } else { $resultado .= numero_a_bajo_millon($miles) . ' MIL'; }
        if ($unidades_final > 0) $resultado .= ' ';
    }
    if ($unidades_final > 0) { $resultado .= numero_a_bajo_millon($unidades_final); }
    return $resultado;
}

function numero_a_bajo_millon($n) {
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
                 'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $especiales = ['VEINTIUN', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO',
                   'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    $resultado = '';
    $c = intval($n / 100);
    $resto = $n % 100;
    if ($n == 100) return 'CIEN';
    if ($c > 0) $resultado .= $centenas[$c];
    if ($resto > 0) {
        if ($resultado) $resultado .= ' ';
        $d = intval($resto / 10);
        $u = $resto % 10;
        if ($resto >= 21 && $resto <= 29) {
            $resultado .= $especiales[$resto - 21];
        } elseif ($d > 0) {
            $resultado .= $decenas[$d];
            if ($u > 0) $resultado .= ' Y ' . $unidades[$u];
        } else {
            $resultado .= $unidades[$u];
        }
    }
    return $resultado;
}

function t($str) {
    $search = ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'];
    $replace = [chr(225),chr(233),chr(237),chr(243),chr(250),chr(241),chr(193),chr(201),chr(205),chr(211),chr(218),chr(209)];
    return str_replace($search, $replace, $str);
}

// Mode detection
$id_servicio = intval($_POST['id_servicio'] ?? 0);
$id_trabajo = intval($_POST['id_trabajo'] ?? 0);
$mostrar_precios = intval($_POST['mostrar_precios'] ?? 0);
$descuento_valor = floatval($_POST['descuento_valor'] ?? 0);
$descuento_tipo = $_POST['descuento_tipo'] ?? 'fijo';

if ($id_servicio <= 0 && $id_trabajo <= 0) {
    header("Location: ../views/reparaciones.php?error=ID invalido");
    exit();
}

$is_service_mode = ($id_servicio > 0);

// =================== DATA EXTRACTION ===================

if ($is_service_mode) {

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
    $servicio_data = $resultado->fetch_assoc();

    $sql_disp = "SELECT * FROM dispositivo_servicio WHERE ID_servicio = ?";
    $stmt_disp = $conn->prepare($sql_disp);
    $stmt_disp->bind_param("i", $id_servicio);
    $stmt_disp->execute();
    $result_disp = $stmt_disp->get_result();
    $stmt_disp->close();

    $arr_dispositivos = [];
    while ($d = $result_disp->fetch_assoc()) {
        $arr_dispositivos[] = $d;
    }

    $sql_trab_all = "SELECT t.*
                     FROM trabajo t
                     INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                     WHERE ds.ID_servicio = ?";
    $stmt_trab = $conn->prepare($sql_trab_all);
    $stmt_trab->bind_param("i", $id_servicio);
    $stmt_trab->execute();
    $result_trab = $stmt_trab->get_result();
    $stmt_trab->close();

    $all_trabajos = [];
    while ($tr = $result_trab->fetch_assoc()) {
        $all_trabajos[$tr['ID_dispositivo']][] = $tr;
    }

    $sql_rep_all = "SELECT rr.*, p.nombre AS producto_nombre
                    FROM reparacion_repuesto rr
                    INNER JOIN trabajo t ON rr.ID_trabajo = t.ID_trabajo
                    INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                    LEFT JOIN producto p ON rr.ID_producto = p.ID_producto
                    WHERE ds.ID_servicio = ?";
    $stmt_rep = $conn->prepare($sql_rep_all);
    $stmt_rep->bind_param("i", $id_servicio);
    $stmt_rep->execute();
    $result_rep = $stmt_rep->get_result();
    $stmt_rep->close();

    $all_repuestos = [];
    $total_repuestos = 0;
    while ($r = $result_rep->fetch_assoc()) {
        $sub = $r['cantidad'] * $r['precio_unitario'];
        $total_repuestos += $sub;
        $all_repuestos[$r['ID_trabajo']][] = $r;
    }

    $sql_prog_all = "SELECT pi.*
                     FROM programa_instalado pi
                     INNER JOIN trabajo t ON pi.ID_trabajo = t.ID_trabajo
                     INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
                     WHERE ds.ID_servicio = ?";
    $stmt_prog = $conn->prepare($sql_prog_all);
    $stmt_prog->bind_param("i", $id_servicio);
    $stmt_prog->execute();
    $result_prog = $stmt_prog->get_result();
    $stmt_prog->close();

    $all_programas = [];
    $total_programas = 0;
    while ($p = $result_prog->fetch_assoc()) {
        $all_programas[$p['ID_trabajo']][] = $p;
        $prog_cant = intval($p['cantidad'] ?? 1);
        $total_programas += $prog_cant * floatval($p['costo'] ?? 0);
    }

    mysqli_close($conn);

    $mano_obra = floatval($servicio_data['mano_obra_costo'] ?? 0);

    $cliente_nombre = trim(($servicio_data['nombre'] ?? '') . ' ' . ($servicio_data['apellido'] ?? ''));
    $ident_texto = '';
    if (!empty($servicio_data['identificacion'])) {
        $tipo = $servicio_data['tipo_identificacion'] ?? '';
        if ($tipo == 'nit') { $ident_texto = 'NIT ' . $servicio_data['identificacion'];
        } elseif ($tipo == 'cc') { $ident_texto = 'CC ' . $servicio_data['identificacion'];
        } elseif ($tipo == 'otro') { $ident_texto = 'ID: ' . $servicio_data['identificacion']; }
    }

    $numero_id = $id_servicio;

} else {

    $sql = "SELECT t.*, ds.dispositivo, ds.marca, ds.modelo,
                   c.nombre, c.apellido, c.identificacion, c.tipo_identificacion, c.telefono, c.correo
            FROM trabajo t
            INNER JOIN dispositivo_servicio ds ON t.ID_dispositivo = ds.ID_dispositivo
            INNER JOIN servicio s ON ds.ID_servicio = s.ID_servicio
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
    $trabajo = $resultado->fetch_assoc();

    $sql_rep = "SELECT rr.*, p.nombre AS producto_nombre
                FROM reparacion_repuesto rr
                LEFT JOIN producto p ON rr.ID_producto = p.ID_producto
                WHERE rr.ID_trabajo = ?";
    $stmt_rep = $conn->prepare($sql_rep);
    $stmt_rep->bind_param("i", $id_trabajo);
    $stmt_rep->execute();
    $result_rep = $stmt_rep->get_result();
    $stmt_rep->close();

    $sql_prog = "SELECT * FROM programa_instalado WHERE ID_trabajo = ?";
    $stmt_prog = $conn->prepare($sql_prog);
    $stmt_prog->bind_param("i", $id_trabajo);
    $stmt_prog->execute();
    $result_prog = $stmt_prog->get_result();
    $stmt_prog->close();

    mysqli_close($conn);

    $arr_repuestos = [];
    $total_repuestos = 0;
    while ($r = $result_rep->fetch_assoc()) {
        $sub = $r['cantidad'] * $r['precio_unitario'];
        $total_repuestos += $sub;
        $arr_repuestos[] = $r;
    }

    $arr_programas = [];
    $total_programas = 0;
    while ($p = $result_prog->fetch_assoc()) {
        $arr_programas[] = $p;
        $prog_cant = intval($p['cantidad'] ?? 1);
        $total_programas += $prog_cant * floatval($p['costo'] ?? 0);
    }

    $mano_obra = floatval($trabajo['mano_obra_costo'] ?? 0);

    $cliente_nombre = trim(($trabajo['nombre'] ?? '') . ' ' . ($trabajo['apellido'] ?? ''));
    $ident_texto = '';
    if (!empty($trabajo['identificacion'])) {
        $tipo = $trabajo['tipo_identificacion'] ?? '';
        if ($tipo == 'nit') { $ident_texto = 'NIT ' . $trabajo['identificacion'];
        } elseif ($tipo == 'cc') { $ident_texto = 'CC ' . $trabajo['identificacion'];
        } elseif ($tipo == 'otro') { $ident_texto = 'ID: ' . $trabajo['identificacion']; }
    }

    $numero_id = $id_trabajo;
}

// =================== TOTALS ===================

$total_bruto = $mano_obra + $total_repuestos + $total_programas;
$total_descuento = 0;
if ($descuento_valor > 0) {
    if ($descuento_tipo == 'porcentaje') {
        $total_descuento = $total_bruto * ($descuento_valor / 100);
    } else {
        $total_descuento = min($descuento_valor, $total_bruto);
    }
}
$total_neto = $total_bruto - $total_descuento;

// =================== PDF CLASS ===================

class PDF extends FPDF {
    function Header() {}
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, 'Cel: 319 748 99 30 | 301 506 04 35', 0, 1, 'C');
        $this->Cell(0, 4, t('Fredonia - Antioquia'), 0, 1, 'C');
        $this->Cell(0, 4, 'compumasterld' . chr(64) . 'gmail.com', 0, 1, 'C');
        $this->SetY(-6);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 4, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
    }
}

function verificar_cierre($pdf, $espacio) {
    $restante = $pdf->GetPageHeight() - $pdf->GetY() - 30;
    if ($restante < $espacio) {
        $pdf->AddPage();
    }
}

// =================== PDF GENERATION ===================

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$logo = __DIR__ . '/../assets/img/logo_pdf.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 60, 10, 0, 30);
    $pdf->Ln(30);
}

$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 10, t('CUENTA DE COBRO'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

$no_cuenta = 'No. CxC-' . date('Y') . '-' . str_pad($numero_id, 4, '0', STR_PAD_LEFT);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, $no_cuenta, 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, t('Fredonia, ' . date('d/m/Y')), 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetDrawColor(26, 32, 53);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, t($cliente_nombre), 0, 1, 'C');
if ($ident_texto) {
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $ident_texto, 0, 1, 'C');
}
$pdf->Ln(8);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t('DEBE A'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, t($empresa['nombre']), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, t('NIT ' . $empresa['nit']), 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetDrawColor(180, 180, 180);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

$suma_texto = numero_a_letras($total_neto) . ' PESOS';
$suma_numero = '$' . number_format($total_neto, 0, ',', '.') . ' COP';
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t('LA SUMA DE'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 6, t($suma_texto) . ' (' . $suma_numero . ')', 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetDrawColor(26, 32, 53);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 8, t('POR CONCEPTO DE'), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(3);

// =================== CONCEPT SECTION ===================

$numero_item = 1;

if ($is_service_mode) {

    foreach ($arr_dispositivos as $disp) {
        $disp_id = $disp['ID_dispositivo'];
        $marca_modelo = trim(($disp['marca'] ?? '') . ' ' . ($disp['modelo'] ?? ''));
        $dispositivo_nombre = $disp['dispositivo'] ?? '';

        verificar_cierre($pdf, 25);

        $concepto = $numero_item . '. ' . t($dispositivo_nombre);
        if ($marca_modelo) $concepto .= ' ' . t($marca_modelo) . ':';
        else $concepto .= ':';
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 6, $concepto, 0, 'L');
        $numero_item++;

        $tasks = $all_trabajos[$disp_id] ?? [];
        foreach ($tasks as $trab) {
            $diagnostico = trim($trab['diagnostico'] ?? '');
            $problema = trim($trab['problema_reportado'] ?? '');
            $notas = trim($trab['notas_internas'] ?? '');
            $trab_id = $trab['ID_trabajo'];

            if (strlen($problema) > 0 || strlen($diagnostico) > 0 || strlen($notas) > 0) {
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->SetTextColor(60, 60, 60);
                if (strlen($problema) > 0) {
                    $pdf->MultiCell(0, 5, '   Reportado: ' . t($problema), 0, 'L');
                }
                if (strlen($diagnostico) > 0) {
                    $pdf->MultiCell(0, 5, '   Diagn' . u(243) . 'stico: ' . t($diagnostico), 0, 'L');
                }
                if (strlen($notas) > 0) {
                    $pdf->MultiCell(0, 5, '   Notas: ' . t($notas), 0, 'L');
                }
                $pdf->SetTextColor(0, 0, 0);
            }

            $task_repuestos = $all_repuestos[$trab_id] ?? [];
            foreach ($task_repuestos as $r) {
                $sub = $r['cantidad'] * $r['precio_unitario'];
                $rep_nombre = t($r['producto_nombre'] ?? '');
                if ($rep_nombre) {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->MultiCell(0, 5, '   ' . $rep_nombre . ':', 0, 'L');
                }
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->SetTextColor(60, 60, 60);
                $detalle_rep = '   Cantidad: ' . $r['cantidad'];
                if ($mostrar_precios == 1) $detalle_rep .= ' - Valor: $' . number_format($sub, 0, ',', '.');
                $pdf->MultiCell(0, 5, $detalle_rep, 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            }

            $task_programas = $all_programas[$trab_id] ?? [];
            foreach ($task_programas as $p) {
                $prog_cant = intval($p['cantidad'] ?? 1);
                $prog_sub = $prog_cant * floatval($p['costo'] ?? 0);
                $prog_nombre = t($p['nombre'] ?? '');
                if (!empty($p['version'])) $prog_nombre .= ' v' . $p['version'];
                if ($prog_nombre) {
                    $pdf->SetFont('Arial', 'B', 9);
                    $pdf->MultiCell(0, 5, '   ' . $prog_nombre . ':', 0, 'L');
                }
                $pdf->SetFont('Arial', 'I', 9);
                $pdf->SetTextColor(60, 60, 60);
                $detalle_prog = '';
                if (!empty($p['licencia'])) $detalle_prog .= 'Licencia: ' . t($p['licencia']);
                if ($prog_cant > 0) {
                    if ($detalle_prog) $detalle_prog .= ' - ';
                    $detalle_prog .= 'Cantidad: ' . $prog_cant;
                }
                if ($mostrar_precios == 1 && floatval($p['costo'] ?? 0) > 0) {
                    if ($detalle_prog) $detalle_prog .= ' - ';
                    $detalle_prog .= 'Valor: $' . number_format($prog_sub, 0, ',', '.');
                }
                if ($detalle_prog) $pdf->MultiCell(0, 5, '   ' . $detalle_prog, 0, 'L');
                $pdf->SetTextColor(0, 0, 0);
            }

            $pdf->Ln(2);
        }
    }

    if ($mano_obra > 0 && $mostrar_precios == 1) {
        $numero_item++;
        $linea_mo = $numero_item . '. Mano de obra:';
        verificar_cierre($pdf, 15);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 6, $linea_mo, 0, 'L');
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell(0, 5, '   Valor: $' . number_format($mano_obra, 0, ',', '.'), 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }

} else {

    verificar_cierre($pdf, 25);

    $marca_modelo = trim(($trabajo['marca'] ?? '') . ' ' . ($trabajo['modelo'] ?? ''));
    $dispositivo_nombre = $trabajo['dispositivo'] ?? '';

    $concepto = $numero_item . '. ' . t($dispositivo_nombre);
    if ($marca_modelo) $concepto .= ' ' . t($marca_modelo) . ':';
    else $concepto .= ':';
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell(0, 6, $concepto, 0, 'L');
    $numero_item++;

    $diagnostico = trim($trabajo['diagnostico'] ?? '');
    $problema = trim($trabajo['problema_reportado'] ?? '');
    $notas = trim($trabajo['notas_internas'] ?? '');

    if (strlen($problema) > 0 || strlen($diagnostico) > 0 || strlen($notas) > 0) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(60, 60, 60);
        if (strlen($problema) > 0) {
            $pdf->MultiCell(0, 5, '   Reportado: ' . t($problema), 0, 'L');
        }
        if (strlen($diagnostico) > 0) {
            $pdf->MultiCell(0, 5, '   Diagn' . u(243) . 'stico: ' . t($diagnostico), 0, 'L');
        }
        if (strlen($notas) > 0) {
            $pdf->MultiCell(0, 5, '   Notas: ' . t($notas), 0, 'L');
        }
        $pdf->SetTextColor(0, 0, 0);
    }
    $pdf->Ln(1);

    if ($mano_obra > 0 && $mostrar_precios == 1) {
        $linea_mo = $numero_item . '. Mano de obra:';
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->MultiCell(0, 6, $linea_mo, 0, 'L');

        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->MultiCell(0, 5, '   Valor: $' . number_format($mano_obra, 0, ',', '.'), 0, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $numero_item++;
        $pdf->Ln(1);
    }

    if (count($arr_repuestos) > 0) {
        foreach ($arr_repuestos as $r) {
            $sub = $r['cantidad'] * $r['precio_unitario'];
            $linea = $numero_item . '. ' . t($r['producto_nombre']) . ':';
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->MultiCell(0, 6, $linea, 0, 'L');

            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(60, 60, 60);
            $detalle_rep = '   Cantidad: ' . $r['cantidad'];
            if ($mostrar_precios == 1) $detalle_rep .= ' - Valor: $' . number_format($sub, 0, ',', '.');
            $pdf->MultiCell(0, 5, $detalle_rep, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $numero_item++;
        }
        $pdf->Ln(1);
    }

    if (count($arr_programas) > 0) {
        foreach ($arr_programas as $p) {
            $prog_cant = intval($p['cantidad'] ?? 1);
            $prog_sub = $prog_cant * floatval($p['costo'] ?? 0);
            $linea = $numero_item . '. ' . t($p['nombre']);
            if (!empty($p['version'])) $linea .= ' v' . $p['version'];
            $linea .= ':';
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->MultiCell(0, 6, $linea, 0, 'L');

            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetTextColor(60, 60, 60);
            $detalle_prog = '';
            if (!empty($p['licencia'])) $detalle_prog .= 'Licencia: ' . t($p['licencia']);
            if ($prog_cant > 0) {
                if ($detalle_prog) $detalle_prog .= ' - ';
                $detalle_prog .= 'Cantidad: ' . $prog_cant;
            }
            if ($mostrar_precios == 1 && floatval($p['costo'] ?? 0) > 0) {
                if ($detalle_prog) $detalle_prog .= ' - ';
                $detalle_prog .= 'Valor: $' . number_format($prog_sub, 0, ',', '.');
            }
            if ($detalle_prog) $pdf->MultiCell(0, 5, '   ' . $detalle_prog, 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $numero_item++;
        }
        $pdf->Ln(1);
    }
}

// =================== SUMMARY TABLE ===================

$pdf->SetAutoPageBreak(false);

if ($mostrar_precios == 1) {
    $pdf->Ln(2);
    $pdf->SetDrawColor(26, 32, 53);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 10);
    if ($mano_obra > 0) {
        $pdf->Cell(0, 6, 'Mano de obra: $' . number_format($mano_obra, 0, ',', '.'), 0, 1, 'R');
    }
    $pdf->Cell(0, 6, 'Repuestos: $' . number_format($total_repuestos, 0, ',', '.'), 0, 1, 'R');
    if ($total_programas > 0) {
        $pdf->Cell(0, 6, 'Programas: $' . number_format($total_programas, 0, ',', '.'), 0, 1, 'R');
    }
    $pdf->Cell(0, 6, 'Subtotal: $' . number_format($total_bruto, 0, ',', '.'), 0, 1, 'R');

    if ($total_descuento > 0) {
        $desc_label = 'Descuento';
        if ($descuento_tipo == 'porcentaje') { $desc_label .= ' (' . $descuento_valor . '%)';
        } else { $desc_label .= ' (fijo)'; }
        $pdf->Cell(0, 6, $desc_label . ': -$' . number_format($total_descuento, 0, ',', '.'), 0, 1, 'R');
    }

    $pdf->SetDrawColor(26, 32, 53);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(26, 32, 53);
    $pdf->Cell(0, 8, 'TOTAL COP: $' . number_format($total_neto, 0, ',', '.'), 0, 1, 'R');
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetDrawColor(26, 32, 53);
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
}

// =================== CLOSING ===================

$altura_cierre = 42;
$y_actual = $pdf->GetY();
$espacio_disponible = $pdf->GetPageHeight() - $y_actual - 20;
if ($espacio_disponible < $altura_cierre) {
    $pdf->AddPage();
}
$pdf->SetAutoPageBreak(false);

$pdf->Ln(6);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'Cordialmente,', 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t($empresa['nombre']), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, 'NIT: ' . $empresa['nit'], 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t($empresa['banco']), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

$pdf->Output('I', 'cuenta_cobro_' . date('Y-m-d') . '.pdf');
