<?php
try {
    // Conexión a la base de datos
    include('47829374983274.php');
    // Conexión a la base de datos usando los valores del archivo config.php
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Configurar el manejo de errores
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Recibir datos del formulario y asignar valores por defecto si están vacíos
    $nombre = !empty($_POST['nombrecliente']) ? $_POST['nombrecliente'] : 'Anónimo';
    $celular = !empty($_POST['celularcliente']) ? $_POST['celularcliente'] : '0000';
    $codigo = $_POST['colocarcodigo'] ?? ''; // El código no cambia
    $atencionCliente = $_POST['atencionCliente'] ?? 0;
    $interaccionesRapidas = $_POST['interaccionesRapidas'] ?? 0;
    $buenTrato = $_POST['buenTrato'] ??0;
    $superacionExpectativas = $_POST['superacionExpectativas'] ??0;
    $comentariosSugerencias = $_POST['comentariosSugerencias'] ??0;

    if (!empty($codigo)) {
        // Insertar en la base de datos
        $sql = "INSERT INTO EncuestaSatisfaccion (id_vendedor, pregunta_1, pregunta_2, pregunta_3, pregunta_4, comentario, nombre_cliente, celular_cliente) 
        VALUES (:codigo, :atencionCliente, :interaccionesRapidas, :buenTrato, :superacionExpectativas, :comentariosSugerencias, :nombre, :celular)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            'nombre' => $nombre,
            'celular' => $celular, 
            'codigo' => $codigo,
            'atencionCliente' => $atencionCliente,
            'interaccionesRapidas' => $interaccionesRapidas,
            'buenTrato' => $buenTrato,
            'superacionExpectativas' => $superacionExpectativas,
            'comentariosSugerencias' => $comentariosSugerencias

        ]);

        // Si la inserción fue exitosa, redirigir a Supergiros
        if ($success) {
            header("Location: https://supergirosnarino.com.co/");
            exit();
        }
    }

    // Si no se ingresó un código de vendedor, mostrar error
    echo "Error: Debes ingresar un código de vendedor.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
