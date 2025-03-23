<?php

function fetchProductsFromURL($url, &$seenProductIds, &$productCounts) {
    $allProducts = [];
    $offset = 0;
    $limit = 100;
    $hasMore = true;

    while ($hasMore) {
        $apiUrl = "{$url}&limit={$limit}&offset={$offset}";
        $response = file_get_contents($apiUrl);
        $data = json_decode($response, true);

        if (!$data || !isset($data['products']) || !is_array($data['products'])) {
            break;
        }

        foreach ($data['products'] as $product) {
            $id = $product['id'];

            // Contamos cuántas veces aparece el producto
            $productCounts[$id] = ($productCounts[$id] ?? 0) + 1;

            // Si un producto aparece 3 veces, detener la búsqueda
            if ($productCounts[$id] >= 3) {
                return []; // Detener búsqueda
            }

            // Si ya lo hemos registrado antes, continuamos
            if (isset($seenProductIds[$id])) {
                continue;
            }

            // Guardar como visto
            $seenProductIds[$id] = true;

            // Obtener datos del producto
            $name = $product['productData']['name'] . ' ' . $product['productData']['brand']['name'];
            $slug = $product['productData']['seo'];
            $description = $product['productData']['description'];
            $image = $product['productData']['imageURL'];

            $price = 0;
            $price_kilo = 0;
            foreach ($product['priceData']['prices'] as $priceItem) {
                if ($priceItem['id'] === 'OFFER_PRICE') {
                    $price = $priceItem['value']['centAmount'];
                    $price_kilo = $priceItem['value']['centUnitAmount'];
                    break;
                } elseif ($priceItem['id'] === 'PRICE' && $price === 0) {
                    $price = $priceItem['value']['centAmount'];
                    $price_kilo = $priceItem['value']['centUnitAmount'];
                }
            }

            $allProducts[] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'price_kilo' => $price_kilo,
                'slug' => $slug,
                'description' => $description,
                'image' => $image
            ];
        }

        $offset += $limit;
        $hasMore = count($data['products']) === $limit;
    }

    return $allProducts;
}

function fetchAllProducts() {
    $baseUrls = [
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?showRecommendations=false",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?filters=filter.ownBrand%3Atrue&showRecommendations=false",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?filters=filter.novelty%3Atrue&showRecommendations=false",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?filters=filter.discount%3Atrue&showRecommendations=false"
    ];

    $allProducts = [];
    $seenProductIds = [];
    $productCounts = [];
    $emptyCount = 0;

    foreach ($baseUrls as $baseUrl) {
        for ($page = 1; $page <= 50; $page++) {
            if ($emptyCount >= 5) {
                break;
            }

            $url = "{$baseUrl}&page={$page}";
            $products = fetchProductsFromURL($url, $seenProductIds, $productCounts);

            if (empty($products)) {
                $emptyCount++;
            } else {
                $emptyCount = 0;
                $allProducts = array_merge($allProducts, $products);
            }

            // Si algún producto ha aparecido 3 veces, detener todo
            if (max($productCounts) >= 3) {
                echo "Se ha encontrado un producto 3 veces. Deteniendo búsqueda...\n";
                break 2;
            }
        }
    }

    return $allProducts;
}

// Ejecutar función y guardar resultados
$products = fetchAllProducts();
file_put_contents("productos_filtrados_bucle_page_optimizado.json", json_encode($products, JSON_PRETTY_PRINT));

echo "Productos guardados en productos_filtrados_bucle_page_optimizado.json\n";
?>
