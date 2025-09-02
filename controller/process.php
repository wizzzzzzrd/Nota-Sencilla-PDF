<?php
require __DIR__ . '/../vendor/autoload.php';
use setasign\Fpdi\Fpdi;

function safeText($s) {
    if ($s === null) return '';
    return mb_convert_encoding(trim($s), 'ISO-8859-1', 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Método no permitido.";
    exit;
}

$vendedor_nombre = $_POST['vendedor_nombre'] ?? '';
$cliente_nombre  = $_POST['cliente_nombre'] ?? '';
$fecha           = $_POST['fecha'] ?? date('Y-m-d');
$domicilio       = $_POST['domicilio'] ?? '';
$nota_no         = $_POST['nota_no'] ?? '';
$total           = $_POST['total'] ?? '0.00';
$items_json      = $_POST['items_json'] ?? '[]';

$items = json_decode($items_json, true);
if (!is_array($items)) $items = [];

$templatePath = __DIR__ . '/../templates/Nota_para_Kevin.pdf';
if (!file_exists($templatePath)) {
    echo "Plantilla no encontrada.";
    exit;
}

$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);

$pageCount = $pdf->setSourceFile($templatePath);
$tplId = $pdf->importPage(1);

$pdf->AddPage();
$pdf->useTemplate($tplId);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0,0,0);

// ----------------------
// Coordenadas definitivas
// ----------------------
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(40, 87);   // Nombre cliente
$pdf->Cell(0, 5, safeText($cliente_nombre), 0, 1);

$pdf->SetXY(41, 98);  // Domicilio
$pdf->MultiCell(110, 5, safeText($domicilio), 0, 'L');

$pdf->SetXY(160, 60);  // Nota No.
$pdf->Cell(40, 5, safeText($nota_no), 0, 1);

$pdf->SetXY(150, 75);  // Nombre Vendedor

// etiqueta en negrita
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 5, 'Vendedor', 0, 0);

// nombre en fuente normal (ajusta tamaño si hace falta)
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(20, 5, safeText($vendedor_nombre), 0, 1);


// --- Fecha con el doble de espacios que antes (antes: 3 -> ahora: 6 en ambos) ---
list($yy, $mm, $dd) = explode('-', $fecha);

// si antes eran 3 espacios, ahora duplicamos a 6
$esp = str_repeat(' ', 12);
$fecha_formateada = sprintf("%02d%s%02d%s%04d", $dd, $esp, $mm, $esp, $yy);

// Ubicar entre (100,150) y (100,180)
$pdf->SetXY(150, 100);
$pdf->Cell(40, 5, safeText($fecha_formateada), 0, 1);

// --- Items ---
$rowHeight = 8;
$y = 125;

foreach ($items as $it) {
    $qty  = number_format($it['qty'] ?? 0, 0, '.', ',');
    $desc = trim($it['desc'] ?? '');
    $unit = number_format($it['unit'] ?? 0, 2, '.', ',');
    $imp  = number_format($it['imp'] ?? (($it['qty'] ?? 0) * ($it['unit'] ?? 0)), 2, '.', ',');

    // Cantidad
    $pdf->SetXY(25, $y);
    $pdf->Cell(20, $rowHeight, safeText($qty), 0, 0, 'L');

    // Descripción
    $pdf->SetXY(60, $y);
    $pdf->Cell(60, $rowHeight, safeText($desc), 0, 0, 'L');

    // Valor unitario
    $pdf->SetXY(125, $y);
    $pdf->Cell(30, $rowHeight, safeText($unit), 0, 0, 'R');

    // Importe
    $pdf->SetXY(160, $y);
    $pdf->Cell(30, $rowHeight, safeText($imp), 0, 0, 'R');

    $y += $rowHeight;
}

// Total
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY(160, 210);
$pdf->Cell(30, 6, number_format((float)$total, 2, '.', ','), 0, 0, 'R');

$ts = date('Ymd_His');
$savePath = __DIR__ . "/../filled/nota_$ts.pdf";
$pdf->Output('F', $savePath);

$filename = "nota_{$ts}.pdf";
$pdf->Output('I', $filename);
exit;
