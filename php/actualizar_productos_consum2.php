<?php
// Conexión a la base de datos
include('../config/conexion.php');

// Función para obtener los productos de la API con paginación
function fetchProductsFromURL($url) {
    $allProducts = [];
    $seenProductIds = [];
    $offset = 0;
    $limit = 100;
    $hasMore = true;

    while ($hasMore) {
        // Realizamos la solicitud
        $response = file_get_contents("{$url}&limit={$limit}&offset={$offset}");
        $data = json_decode($response, true);

        if (!$data || !isset($data['products']) || !is_array($data['products'])) {
            break; // Salir si no hay productos o la estructura no es la esperada
        }

        // Filtrar productos repetidos
        $newProducts = array_filter($data['products'], function($product) use ($seenProductIds) {
            return !in_array($product['id'], $seenProductIds);
        });

        // Agregar productos únicos a la lista
        foreach ($newProducts as $product) {
            $seenProductIds[] = $product['id'];
            $allProducts[] = $product;
        }

        // Aumentamos el offset para la siguiente página
        $offset += $limit;

        // Si obtenemos menos productos que el límite, no hay más productos
        if (count($data['products']) < $limit) {
            $hasMore = false;
        }
    }

    return $allProducts;
}

// Función para insertar el producto en la base de datos
function insertProduct($conexion, $product) {
    // Nombre del producto (name + brand)
    $name = $product['productData']['name'];
    $brand = $product['productData']['brand']['name'];
    $productName = $name . ' ' . $brand;

    // Precio
    $price = 0;
    $priceKilo = 0;
    if (isset($product['priceData']['prices'])) {
        foreach ($product['priceData']['prices'] as $priceData) {
            if ($priceData['id'] === 'OFFER_PRICE') {
                $price = $priceData['value']['centAmount'] / 100;
                $priceKilo = $priceData['value']['centUnitAmount'] / 100;
                break;
            }
        }
        // Si no hay OFFER_PRICE, usamos el precio bajo el ID PRICE
        if ($price === 0) {
            $price = $product['priceData']['prices'][0]['value']['centAmount'] / 100;
            $priceKilo = $product['priceData']['prices'][0]['value']['centUnitAmount'] / 100;
        }
    }

    // Slug
    $slug = $product['productData']['seo'];

    // Imagen
    $imageUrl = $product['productData']['imageURL'];

    // Consulta SQL preparada para evitar inyecciones SQL
    $query = "INSERT INTO productos_consum (nombre, precio, precio_kilo, slug, imagen) 
              VALUES (:productName, :price, :priceKilo, :slug, :imageUrl)";

    // Preparar y ejecutar la consulta
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':productName', $productName);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':priceKilo', $priceKilo);
    $stmt->bindParam(':slug', $slug);
    $stmt->bindParam(':imageUrl', $imageUrl);

    try {
        // Ejecutar la consulta
        $stmt->execute();
        echo "Producto '$productName' insertado correctamente.<br>";
    } catch (PDOException $e) {
        echo "Error al insertar el producto '$productName': " . $e->getMessage() . "<br>";
    }
}


// Función principal para obtener productos de varias URL y subirlos a la base de datos
function main() {
    global $conexion;

    // Vaciar la tabla antes de insertar nuevos productos
    $truncateQuery = "TRUNCATE TABLE productos_consum";
    try {
        $conexion->exec($truncateQuery);
        echo "Tabla 'productos_consum' vaciada correctamente.<br>";
    } catch (PDOException $e) {
        echo "Error al vaciar la tabla: " . $e->getMessage() . "<br>";
    }

    $categoryUrls = [
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=1&showRecommendations=false&categories=99999",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.ownBrand%3Atrue&exclusionFilters=true",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=5&showRecommendations=false&filters=&groups=FOLLETOMARZO25&exclusionFilters=true",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.novelty%3Atrue&exclusionFilters=true"
    ];

    $allProducts = [];
    $seenProductIds = [];

    // Obtener productos de las URL de categorías
    foreach ($categoryUrls as $url) {
        $products = fetchProductsFromURL($url);
        foreach ($products as $product) {
            if (!in_array($product['id'], $seenProductIds)) {
                $seenProductIds[] = $product['id'];
                $allProducts[] = $product;
            }
        }
    }

    // Inserción de los productos en la base de datos
    foreach ($allProducts as $product) {
        insertProduct($conexion, $product);
    }

    echo "Total de productos insertados: " . count($allProducts) . "<br>";
}


// Ejecutar el script
main();
?>
