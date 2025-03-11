<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/conexion.php'; // Incluir el archivo de conexión

// URL de la API de Mercadona
$url = "https://tienda.mercadona.es/api/categories/";

// Obtener los datos de la API
$json = file_get_contents($url);

if ($json === FALSE) {
    die(json_encode(['error' => 'Error al obtener los datos de Mercadona.']));
}

$categoriesData = json_decode($json, true);

// Verificar si se obtuvieron resultados
if (!isset($categoriesData['results'])) {
    die(json_encode(['error' => 'No se encontraron categorías.']));
}

try {
    // Eliminar los productos antiguos antes de insertar los nuevos
    $conexion->exec("DELETE FROM productos_mercadona");

    // Preparar la consulta para insertar productos
    $sql = "INSERT INTO productos_mercadona (nombre, precio, slug, peso, imagen) VALUES (:nombre, :precio, :slug, :peso, :imagen)";
    $stmt = $conexion->prepare($sql);

    foreach ($categoriesData['results'] as $category) {
        foreach ($category['categories'] as $subcategory) {
            $subUrl = "https://tienda.mercadona.es/api/categories/" . $subcategory['id'] . "/";
            $subJson = file_get_contents($subUrl);
            $subcategoryData = json_decode($subJson, true);

            if (isset($subcategoryData['categories'])) {
                foreach ($subcategoryData['categories'] as $subsubcategory) {
                    if (isset($subsubcategory['products'])) {
                        foreach ($subsubcategory['products'] as $product) {
                            $nombre = $product['display_name'];
                            $precio = $product['price_instructions']['unit_price'];
                            $slug = $product['slug'];
                            $peso = $product['price_instructions']['unit_size'];
                            $imagen = $product['thumbnail'];

                            $stmt->bindParam(':nombre', $nombre);
                            $stmt->bindParam(':precio', $precio);
                            $stmt->bindParam(':slug', $slug);
                            $stmt->bindParam(':peso', $peso);
                            $stmt->bindParam(':imagen', $imagen);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Productos actualizados correctamente.']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
