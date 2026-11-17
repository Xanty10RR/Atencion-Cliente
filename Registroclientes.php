<?php
require_once 'conexion.php';

try {
    // Recibir datos del formulario y asignar valores por defecto si están vacíos
    $nombre = !empty($_POST['nombrecliente']) ? $_POST['nombrecliente'] : 'Anónimo';
    $celular = !empty($_POST['celularcliente']) ? $_POST['celularcliente'] : '0000';
    $codigo = $_POST['colocarcodigo'] ?? ''; // El código no cambia
    $atencionCliente = $_POST['atencionCliente'] ?? 0;
    $interaccionesRapidas = $_POST['interaccionesRapidas'] ?? 0;
    $buenTrato = $_POST['buenTrato'] ?? 0;
    $superacionExpectativas = $_POST['superacionExpectativas'] ?? 0;
    $comentariosSugerencias = $_POST['comentariosSugerencias'] ?? 0;

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

        // Si la inserción fue exitosa, redirigir a login.php
        if ($success) {
            header("Location: login.php");
            exit();
        }
    }

    // Si no se ingresó un código de vendedor, mostrar error
    echo "Error: Debes ingresar un código de vendedor.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
