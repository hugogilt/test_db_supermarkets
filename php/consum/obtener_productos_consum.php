<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../../config/conexion.php';

header('Content-Type: application/json');

try {
    $stmt = $conexion->query("SELECT * FROM productos_consum");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($productos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
