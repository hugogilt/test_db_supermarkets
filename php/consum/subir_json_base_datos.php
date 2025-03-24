<?php
require '../../config/conexion.php'; // Conexión a la base de datos

try {

    // Leer el JSON generado
    $jsonFile = "productos_filtrados_bucle_page_packs.json";
    if (!file_exists($jsonFile)) {
        throw new Exception("El archivo JSON no existe.");
    }

    $json = file_get_contents($jsonFile);
    $productos = json_decode($json, true);

    if (!$productos || !is_array($productos)) {
        throw new Exception("Error al decodificar el JSON.");
    }

    // Preparar la consulta SQL
    $sql = "INSERT INTO productos_consum (nombre, precio, precio_kilo, slug, descripcion, imagen, oferta_pack, unidades_pack, precio_unidad_pack) 
                VALUES (:nombre, :precio, :precio_kilo, :slug, :descripcion, :imagen, :oferta_pack, :unidades_pack, :precio_unidad_pack)";
    
    $stmt = $conexion->prepare($sql);
    
    // Insertar productos
    $conexion->beginTransaction();
    $productosProcesados = 0;

    foreach ($productos as $producto) {
        try {
            $stmt->execute([
                ':nombre' => $producto['name'] ?? "Sin nombre",
                ':precio' => isset($producto['price']) ? $producto['price'] : null, 
                ':precio_kilo' => isset($producto['price_kilo']) ? $producto['price_kilo'] : null, 
                ':slug' => $producto['slug'] ?? null,
                ':descripcion' => $producto['description'] ?? null,
                ':imagen' => $producto['image'] ?? null,
                ':oferta_pack' => isset($producto['ofertaPack']) ? (int)$producto['ofertaPack'] : 0,
                ':unidades_pack' => $producto['unidadesPack'] ?? null,
                ':precio_unidad_pack' => isset($producto['precioUnidadPack']) ? $producto['precioUnidadPack'] : null
            ]);
            $productosProcesados++;
        } catch (Exception $e) {
            echo "Error en producto ID {$producto['id']}: " . $e->getMessage() . "\n";
        }
    }
    
    $conexion->commit();
    echo "Productos insertados correctamente: $productosProcesados de " . count($productos) . "\n";

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage();
}
?>
