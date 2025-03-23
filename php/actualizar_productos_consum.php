<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/conexion.php'; // Incluir el archivo de conexión

// Función para obtener productos desde una URL con paginación
function fetchProductsFromURL($url, $maxProducts = null) {
    $allProducts = [];
    $seenProductIds = [];
    
    $offset = 0;
    $limit = 100;
    $hasMore = true;

    while ($hasMore) {
        $response = file_get_contents($url . "&limit={$limit}&offset={$offset}");
        
        if ($response === FALSE) {
            die(json_encode(['error' => 'Error al obtener los productos.']));
        }

        $data = json_decode($response, true);
        if (!isset($data['products']) || !is_array($data['products'])) {
            break;
        }

        foreach ($data['products'] as $product) {
            if (!in_array($product['id'], $seenProductIds)) {
                $seenProductIds[] = $product['id'];
                $allProducts[] = $product;
            }
        }

        $offset += $limit;

        if ($maxProducts !== null && count($allProducts) >= $maxProducts) {
            break;
        }

        if (count($data['products']) < $limit) {
            $hasMore = false;
        }
    }

    return array_slice($allProducts, 0, $maxProducts);
}

// Función para extraer datos y guardarlos en la base de datos
function processAndSaveProducts($products, $conexion) {
    try {
        // Mostrar cuántos productos hay antes de insertarlos
        echo json_encode(['message' => 'Total de productos a insertar: ' . count($products)]);

        // Vaciar la tabla antes de insertar los nuevos productos
        $conexion->exec("DELETE FROM productos_consum");

        // Preparar la consulta para insertar productos
        $sql = "INSERT INTO productos_consum (nombre) VALUES (:nombre)";
        $stmt = $conexion->prepare($sql);

        foreach ($products as $product) {
            // Extraer los datos necesarios
            $nombre = $product['productData']['name'] . " " . $product['productData']['brand']['name'];
            $slug = $product['productData']['seo'];
            $imagen = $product['productData']['imageURL'];

            // Determinar el precio y el precio por kilo
            $precio = null;
            $precio_kilo = null;
            if (isset($product['priceData']['prices'])) {
                foreach ($product['priceData']['prices'] as $price) {
                    if ($price['id'] === 'OFFER_PRICE') {
                        $precio = $price['value']['centAmount'];
                        $precio_kilo = $price['value']['centUnitAmount'];
                        break;
                    } elseif ($price['id'] === 'PRICE') {
                        $precio = $precio ?? $price['value']['centAmount'];
                        $precio_kilo = $precio_kilo ?? $price['value']['centUnitAmount'];
                    }
                }
            }

            // Insertar en la base de datos si los datos son válidos
            if ($nombre && $precio !== null && $slug && $imagen) {
                $stmt->bindParam(':nombre', $nombre);
                // $stmt->bindParam(':precio', $precio);
                // $stmt->bindParam(':precio_kilo', $precio_kilo);
                // $stmt->bindParam(':slug', $slug);
                // $stmt->bindParam(':imagen', $imagen);
                $stmt->execute();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Productos actualizados correctamente.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

// URLs de categorías
$categoryUrls = [
    "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=1&showRecommendations=false&categories=99999",
    "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.ownBrand%3Atrue&exclusionFilters=true",
    "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=5&showRecommendations=false&filters=&groups=FOLLETOMARZO25&exclusionFilters=true",
    "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.novelty%3Atrue&exclusionFilters=true"
];

// URL general de productos con límite de 2900
$generalUrl = "https://tienda.consum.es/api/rest/V1.0/catalog/product?page=1";
$maxGeneralProducts = 2900;

// Obtener productos de las categorías
$allProducts = [];
foreach ($categoryUrls as $url) {
    $categoryProducts = fetchProductsFromURL($url);
    $allProducts = array_merge($allProducts, $categoryProducts);
}

// Obtener productos de la URL general con el límite de 2900
$generalProducts = fetchProductsFromURL($generalUrl, $maxGeneralProducts);
$allProducts = array_merge($allProducts, $generalProducts);

// Guardar productos en la base de datos
processAndSaveProducts($allProducts, $conexion);
?>
