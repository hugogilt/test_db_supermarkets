<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

$productos = [];

foreach ($categoriesData['results'] as $category) {
    foreach ($category['categories'] as $subcategory) {
        $subUrl = "https://tienda.mercadona.es/api/categories/" . $subcategory['id'] . "/";
        $subJson = file_get_contents($subUrl);
        $subcategoryData = json_decode($subJson, true);

        if (isset($subcategoryData['categories'])) {
            foreach ($subcategoryData['categories'] as $subsubcategory) {
                if (isset($subsubcategory['products'])) {
                    foreach ($subsubcategory['products'] as $product) {
                        // Obtener peso: unit_size o, si no existe, drained_weight
                        $peso = null;
                        if (isset($product['price_instructions']['unit_size']) && $product['price_instructions']['unit_size'] !== null) {
                            $peso = $product['price_instructions']['unit_size'];
                        } elseif (isset($product['price_instructions']['drained_weight']) && $product['price_instructions']['drained_weight'] !== null) {
                            $peso = $product['price_instructions']['drained_weight'];
                        }

                        $productos[] = [
                            'nombre' => $product['display_name'],
                            'precio' => $product['price_instructions']['unit_price'],
                            'slug' => $product['slug'],
                            'peso' => $peso,
                            'imagen' => $product['thumbnail']
                        ];
                    }
                }
            }
        }
    }
}

// Guardar los datos en un archivo JSON
$filePath = 'productos_mercadona-7-5.json';
file_put_contents($filePath, json_encode($productos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(['success' => true, 'message' => 'Productos guardados en productos_mercadona.json']);
?>
