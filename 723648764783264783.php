<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

try {
    include('47829374983274.php');
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, id_vendedor, pregunta_1, pregunta_2, pregunta_3, pregunta_4, nombre_cliente, celular_cliente,
                comentario, fecha_votacion,
                ROUND((pregunta_1 + pregunta_2 + pregunta_3 + pregunta_4) / 4.0, 2) AS promedio
            FROM EncuestaSatisfaccion
            ORDER BY promedio ASC";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Encuesta Satisfacción");

    // Insertar el logo en la esquina superior izquierda
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setPath('img/logo1.jpg'); // Ruta del logo
    $drawing->setHeight(60); // Puedes ajustar el tamaño
    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);

    // Saltar espacio para el logo (A1:B3)
    $startRow = 4;

    // Estilo para encabezados
    $headerStyle = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FF007ACC']
        ],
        'font' => [
            'bold' => true,
            'color' => ['argb' => 'FFFFFFFF']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ];

    // Encabezados
    $encabezados = [
        'Código del Vendedor', 'Atención al Cliente', 'Interacciones Rápidas',
        'Buen Trato', 'Superación de Expectativas', 'Comentarios y Sugerencias',
        'Promedio', 'Nombre del Cliente', 'Celular', 'Fecha y Hora'
    ];
    $col = 'A';
    foreach ($encabezados as $titulo) {
        $sheet->setCellValue($col . $startRow, $titulo);
        $sheet->getStyle($col . $startRow)->applyFromArray($headerStyle);
        $col++;
    }

// Insertar datos
$fila = $startRow + 1;
foreach ($clientes as $cliente) {
    $sheet->fromArray([
        $cliente['id_vendedor'],
        $cliente['pregunta_1'],
        $cliente['pregunta_2'],
        $cliente['pregunta_3'],
        $cliente['pregunta_4'],
        $cliente['comentario'],
        number_format($cliente['promedio'], 2, '.', ''),
        $cliente['nombre_cliente'],
        $cliente['celular_cliente'],
        $cliente['fecha_votacion']
    ], null, 'A' . $fila);
    $fila++;
}
// Aplicar estilo al encabezado
$sheet->getStyle("A{$startRow}:K{$startRow}")->applyFromArray($headerStyle);

// Ajustar altura del encabezado
$sheet->getRowDimension($startRow)->setRowHeight(25);

// Autoajustar columnas
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Estilo general de filas de datos
$dataStyle = [
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];
$sheet->getStyle("A" . ($startRow + 1) . ":K" . ($fila - 1))->applyFromArray($dataStyle);


// === AQUI AÑADES LOS BORDES ===
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];

$lastRow = $fila - 1;
$range = "A{$startRow}:K{$lastRow}";

// Asegurar que las celdas estén activas (necesario para bordes)
foreach (range($startRow, $lastRow) as $row) {
    foreach (range('A', 'K') as $col) {
        $cell = $col . $row;
        if ($sheet->getCell($cell)->getValue() === null) {
            $sheet->setCellValue($cell, '');
        }
    }
}

// Aplicar bordes
$sheet->getStyle($range)->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000']
        ]
    ]
]);

// Alinear el contenido a la izquierda
$sheet->getStyle($range)->applyFromArray([
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
]);

// Descargar
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Votacion_clientes.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
