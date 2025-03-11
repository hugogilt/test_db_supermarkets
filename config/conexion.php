<?php
$host = "sql306.infinityfree.com";
$dbname = "if0_38491353_mercahorro";
$user = "if0_38491353";
$password = "CFWVSrEf9IdnL5r";

try {
    // Conexión a la base de datos usando PDO
    $conexion = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    // Establecer el modo de error para que PDO lance excepciones si ocurre algún error
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Si hay un error de conexión, lo mostramos
    echo "Error en la conexión: " . $e->getMessage();
}
?>
