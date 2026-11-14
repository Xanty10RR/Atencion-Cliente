<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

try {
    require_once __DIR__ . '/config.php';
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT id, id_vendedor,
                (SELECT COUNT(*) FROM EncuestaSatisfaccion c2 WHERE c2.id_vendedor = EncuestaSatisfaccion.id_vendedor) AS frecuencia,
                pregunta_1, pregunta_2, pregunta_3, pregunta_4, nombre_cliente, celular_cliente,
                comentario, fecha_votacion,
                ROUND((pregunta_1 + pregunta_2 + pregunta_3 + pregunta_4) / 4.0, 2) AS promedio
            FROM EncuestaSatisfaccion
            ORDER BY promedio ASC";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Encuesta Satisfacción");

    // Asignamos un ancho fijo manual a las columnas A y B para dar espacio al logo
    $sheet->getColumnDimension('A')->setWidth(18);
    $sheet->getColumnDimension('B')->setWidth(18);

    // Insertar el logo en la esquina superior izquierda
    $drawing = new Drawing();
    $drawing->setName('Logo');

    // Ruta absoluta segura para el servidor local
    $rutaLogo = __DIR__ . '/img/logo.jpg';
    if (file_exists($rutaLogo)) {
        $drawing->setPath($rutaLogo);
    } else {
        die("Error: El archivo de logo no existe en la ruta: " . $rutaLogo);
    }

    // Desactivamos la proporción automática para obligar a usar las dos medidas exactas
    $drawing->setResizeProportional(false);

    // Definimos las dimensiones personalizadas exactas que necesitas
    $drawing->setWidth(130);
    $drawing->setHeight(76);

    $drawing->setCoordinates('A1');
    $drawing->setWorksheet($sheet);

    // La tabla empieza
    $startRow = 5;


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
        'Código del Vendedor',
        'Frecuencia',
        'Atención al Cliente',
        'Interacciones Rápidas',
        'Buen Trato',
        'Superación de Expectativas',
        'Comentarios y Sugerencias',
        'Promedio',
        'Nombre del Cliente',
        'Celular',
        'Fecha y Hora'
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
            $cliente['frecuencia'],
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

    // Aplicar estilo al encabezado (Limitado hasta la K)
    $sheet->getStyle("A{$startRow}:K{$startRow}")->applyFromArray($headerStyle);

    // Ajustar altura del encabezado
    $sheet->getRowDimension($startRow)->setRowHeight(25);

    // Autoajustar las columnas de datos RESTANTES (De la C a la K)
    foreach (range('C', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $lastRow = $fila - 1;
    $rangeTotal = "A{$startRow}:K{$lastRow}";
    $rangeDatos = "A" . ($startRow + 1) . ":K" . $lastRow; // Crea un rango exclusivo para los datos (Fila 6 en adelante)

    // Asegurar que las celdas estén activas para los bordes
    foreach (range($startRow, $lastRow) as $row) {
        foreach (range('A', 'K') as $col) {
            $cell = $col . $row;
            if ($sheet->getCell($cell)->getValue() === null) {
                $sheet->setCellValue($cell, '');
            }
        }
    }

    //  Aplicar bordes finos a toda la tabla (Encabezado + Datos)
    $sheet->getStyle($rangeTotal)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ]);

    // Alinear verticalmente toda la tabla
    $sheet->getStyle($rangeTotal)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    // Alinea los datos a la izquierda dejando en encabezado centrado
    $sheet->getStyle($rangeDatos)->applyFromArray([
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);

    // Descarga el archivo Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Votacion_clientes.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
