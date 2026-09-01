<?php
include("../config/conexion.php");
session_start();
include("../config/csrf.php");
if (!isset($_SESSION['usuario'])) {
    header("Location: ../views/reparaciones.php");
    exit();
}

if (!csrf_validate($_POST['csrf_token'] ?? '')) {
    header("Location: ../views/reparaciones.php?error=Token CSRF inválido");
    exit();
}

require(__DIR__ . '/../fpdf/fpdf.php');

// Datos fijos de la empresa para el encabezado y pie del PDF (sin cuenta bancaria)
$empresa = [
    'nombre' => 'CompuMasterLD',
    'tel1' => '319 748 99 30',
    'tel2' => '301 506 04 35',
    'dir' => 'Fredonia - Antioquia',
];

// Retorna el carácter ASCII correspondiente al código dado
function u($char) {
    return chr($char);
}

// Convierte un número (hasta 999 millones) a su representación en letras
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
    if ($numero == 100) return 'CIEN';

    $parte = function($n, $mayusculas = false) use ($unidades, $decenas, $especiales, $centenas) {
        if ($n == 0) return '';
        $resultado = '';
        $c = intval($n / 100);
        $resto = $n % 100;
        if ($c > 0) {
            $resultado .= $centenas[$c] . ' ';
        }
        if ($resto > 0) {
            if ($resto < 20) {
                $resultado .= $unidades[$resto];
            } elseif ($resto == 20) {
                $resultado .= 'VEINTE';
            } elseif ($resto < 30) {
                $resultado .= $especiales[$resto - 21];
            } else {
                $d = intval($resto / 10);
                $un = $resto % 10;
                $resultado .= $decenas[$d];
                if ($un > 0) {
                    $resultado .= ' Y ' . $unidades[$un];
                }
            }
        }
        $resultado = trim($resultado);
        if ($mayusculas) $resultado = mb_strtoupper($resultado, 'UTF-8');
        return $resultado;
    };

    $resultado = '';
    if ($numero >= 1000000) {
        $millones = intval($numero / 1000000);
        $resto = $numero % 1000000;
        if ($millones == 1) {
            $resultado .= 'UN MILLON ';
        } else {
            $resultado .= $parte($millones, true) . ' MILLONES ';
        }
        if ($resto > 0) {
            $resultado .= $parte($resto);
        }
    } elseif ($numero >= 1000) {
        $miles = intval($numero / 1000);
        $resto = $numero % 1000;
        if ($miles == 1) {
            $resultado .= 'MIL ';
        } else {
            $resultado .= $parte($miles) . ' MIL ';
        }
        if ($resto > 0) {
            $resultado .= $parte($resto);
        }
    } else {
        $resultado = $parte($numero);
    }
    return trim($resultado);
}

// Codifica caracteres especiales (tildes, ñ) a ISO-8859-1 para FPDF
function t($str) {
    $search = ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'];
    $replace = [chr(225),chr(233),chr(237),chr(243),chr(250),chr(241),chr(193),chr(201),chr(205),chr(211),chr(218),chr(209)];
    return str_replace($search, $replace, $str);
}

// Captura datos del formulario de cotización
$cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
$cliente_identificacion = trim($_POST['cliente_identificacion'] ?? '');
$elaborado_por = trim($_POST['elaborado_por'] ?? '');
$dispositivo = trim($_POST['dispositivo'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$numero_serie = trim($_POST['numero_serie'] ?? '');
$alcance = trim($_POST['alcance'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

$descuento_tipo = $_POST['descuento_tipo'] ?? 'fijo';
$descuento_valor = floatval($_POST['descuento_valor'] ?? 0);
$envio_valor = floatval($_POST['envio_valor'] ?? 0);
$vigencia_dias = max(1, intval($_POST['vigencia_dias'] ?? 15));

// Ítems de la cotización enviados como arrays paralelos
$conceptos = $_POST['item_concepto'] ?? [];
$cantidades = $_POST['item_cantidad'] ?? [];
$valores = $_POST['item_valor'] ?? [];

if (empty($cliente_nombre)) {
    header("Location: ../views/cotizacion.php?error=Debe indicar el nombre del cliente");
    exit();
}

// Construye el arreglo de ítems validando solo filas completas
$items = [];
$total_items = 0;
foreach ($conceptos as $idx => $concepto) {
    $concepto = trim($concepto);
    $cantidad = intval($cantidades[$idx] ?? 0);
    $valor = floatval($valores[$idx] ?? 0);
    if ($concepto === '' || $cantidad <= 0) continue;
    if ($cantidad < 1) $cantidad = 1;
    if ($valor < 0) $valor = 0;
    $linea = $cantidad * $valor;
    $total_items += $linea;
    $items[] = [
        'concepto' => $concepto,
        'cantidad' => $cantidad,
        'valor' => $valor,
        'linea' => $linea,
    ];
}

if (empty($items)) {
    header("Location: ../views/cotizacion.php?error=Debe agregar al menos un concepto");
    exit();
}

// Cálculo del descuento y total con valor de envío incluido
$descuento = 0;
if ($descuento_tipo === 'porcentaje' && $descuento_valor > 0) {
    $descuento = $total_items * ($descuento_valor / 100);
} elseif ($descuento_valor > 0) {
    $descuento = min($descuento_valor, $total_items);
}
$total_final = $total_items - $descuento + $envio_valor;

// Fecha de vigencia calculada a partir de los días indicados
$fecha_limite = date('d/m/Y', strtotime('+' . $vigencia_dias . ' days'));

class PDF extends FPDF {
    // Cabecera vacía: se genera manualmente
    function Header() {}
    // Pie de página con datos de contacto (sin cuenta bancaria)
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

// Salta de página si no hay espacio suficiente antes del pie
function verificar_cierre($pdf, $espacio) {
    $restante = $pdf->GetPageHeight() - $pdf->GetY() - 30;
    if ($restante < $espacio) {
        $pdf->AddPage();
    }
}

// =================== GENERACIÓN DEL PDF ===================

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

$logo = __DIR__ . '/../assets/img/logo_pdf.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 60, 10, 0, 30);
    $pdf->Ln(30);
}

// Título e identificación de la cotización
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 10, t('COTIZACI' . u(211) . 'N'), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// Número de cotización con sufijo de tiempo para evitar colisiones (sin correlativo en BD)
$no_cotizacion = 'No. COTIZACI' . u(211) . 'N-' . date('Y') . '-' . date('His') . '-' . rand(100, 999);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, t($no_cotizacion), 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, t('Fredonia, ' . date('d/m/Y')), 0, 1, 'C');
$pdf->Cell(0, 6, t('V' . u(225) . 'lida por ' . $vigencia_dias . ' d' . u(237) . 'as (hasta ' . $fecha_limite . ')'), 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetDrawColor(26, 32, 53);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(6);

// Datos del cliente destinatario y quien elabora
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t('PARA: '), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, t($cliente_nombre), 0, 1, 'L');
if (!empty($cliente_identificacion)) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, t('NIT/CC: ' . $cliente_identificacion), 0, 1, 'L');
}
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t('ELABORADO POR: '), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, t($elaborado_por), 0, 1, 'L');
$pdf->Ln(5);

// Datos del equipo o servicio cotizado
if (!empty($dispositivo) || !empty($marca) || !empty($modelo) || !empty($numero_serie)) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(26, 32, 53);
    $pdf->Cell(0, 7, t('EQUIPO / SERVICIO: '), 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    if (!empty($dispositivo)) $pdf->Cell(0, 6, t($dispositivo), 0, 1, 'L');
    $marca_modelo = trim($marca . ' ' . $modelo);
    if (!empty($marca_modelo)) $pdf->Cell(0, 6, t($marca_modelo), 0, 1, 'L');
    if (!empty($numero_serie)) $pdf->Cell(0, 6, t('Serie: ' . $numero_serie), 0, 1, 'L');
    $pdf->Ln(3);
}

// Motivo / alcance del trabajo a cotizar
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(0, 7, t('MOTIVO / ALCANCE: '), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', 'I', 10);
$pdf->MultiCell(0, 6, t($alcance), 0, 'L');
$pdf->Ln(5);

// =================== TABLA DE CONCEPTOS ===================
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(12, 8, '#', 1, 0, 'C', true);
$pdf->Cell(83, 8, t('Concepto / Descripci' . u(243) . 'n'), 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Cant.', 1, 0, 'C', true);
$pdf->Cell(35, 8, t('Valor Unitario'), 1, 0, 'R', true);
$pdf->Cell(35, 8, 'Valor Total', 1, 1, 'R', true);

$pdf->SetFont('Arial', '', 10);
$n = 1;
foreach ($items as $item) {
    verificar_cierre($pdf, 25);
    $pdf->Cell(12, 7, $n, 1, 0, 'C');
    $pdf->Cell(83, 7, t($item['concepto']), 1, 0, 'L');
    $pdf->Cell(25, 7, $item['cantidad'], 1, 0, 'C');
    $pdf->Cell(35, 7, '$' . number_format($item['valor'], 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(35, 7, '$' . number_format($item['linea'], 0, ',', '.'), 1, 1, 'R');
    $n++;
}

$pdf->Ln(4);

// Totales: subtotal, descuento, envío y total final
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(115, 6, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(75, 6, '$' . number_format($total_items, 0, ',', '.'), 0, 1, 'R');

if ($descuento > 0) {
    $desc_label = 'Descuento';
    if ($descuento_tipo == 'porcentaje') { $desc_label .= ' (' . $descuento_valor . '%)';
    } else { $desc_label .= ' (fijo)'; }
    $pdf->Cell(115, 6, $desc_label . ':', 0, 0, 'R');
    $pdf->Cell(75, 6, '-$' . number_format($descuento, 0, ',', '.'), 0, 1, 'R');
}

if ($envio_valor > 0) {
    $pdf->Cell(115, 6, t('Valor del env' . u(237) . 'o:'), 0, 0, 'R');
    $pdf->Cell(75, 6, '$' . number_format($envio_valor, 0, ',', '.'), 0, 1, 'R');
}

$pdf->SetDrawColor(26, 32, 53);
$pdf->SetLineWidth(0.8);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(115, 8, 'TOTAL: $' . number_format($total_final, 0, ',', '.'), 0, 1, 'R');
$pdf->SetTextColor(0, 0, 0);

// Total en letras
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(195, 6, t(numero_a_letras($total_final) . ' PESOS M/CTE'), 0, 1, 'R');
$pdf->Ln(5);

// Observaciones
if (!empty($observaciones)) {
    verificar_cierre($pdf, 30);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(26, 32, 53);
    $pdf->Cell(0, 7, t('OBSERVACIONES: '), 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->MultiCell(0, 6, t($observaciones), 0, 'L');
    $pdf->Ln(4);
}

// =================== FIRMA (quien elabora) ===================
$y_actual = $pdf->GetY();
$espacio_disponible = $pdf->GetPageHeight() - $y_actual - 20;
if ($espacio_disponible < 60) {
    $pdf->AddPage();
}
$pdf->SetAutoPageBreak(false);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(26, 32, 53);
$pdf->Cell(80, 7, t('ELABOR' . u(211) . ' POR'), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(18);
$pdf->SetDrawColor(80, 80, 80);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, $pdf->GetY(), 95, $pdf->GetY());
$pdf->Ln(1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 6, t($empresa['nombre']), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(80, 5, t('T' . u(233) . 'cnico / Empresa'), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);

$pdf->SetAutoPageBreak(true, 20);

mysqli_close($conn);
$pdf->Output('I', 'cotizacion_' . date('Y-m-d') . '.pdf');