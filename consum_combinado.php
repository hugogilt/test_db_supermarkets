<?php

function fetchProductsFromURL($url) {
    $allProducts = [];
    $seenProductIds = [];
    $offset = 0;
    $limit = 100;
    $hasMore = true;

    while ($hasMore) {
        $fullUrl = "{$url}&limit={$limit}&offset={$offset}";
        $response = file_get_contents($fullUrl);
        $data = json_decode($response, true);

        if (!$data || !isset($data['products']) || !is_array($data['products'])) {
            break;
        }

        foreach ($data['products'] as $product) {
            if (!isset($seenProductIds[$product['id']])) {
                $seenProductIds[$product['id']] = true;
                $allProducts[] = $product;
            }
        }

        $offset += $limit;
        if (count($data['products']) < $limit) {
            $hasMore = false;
        }
    }

    return $allProducts;
}

function fetchGeneralProducts() {
    $allProducts = [];
    $seenProductIds = [];
    $offset = 0;
    $limit = 100;
    $maxProducts = 2900;
    $hasMore = true;

    while ($hasMore && count($allProducts) < $maxProducts) {
        $url = "https://tienda.consum.es/api/rest/V1.0/catalog/product?page=1&limit={$limit}&offset={$offset}";
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (!$data || !isset($data['products']) || !is_array($data['products'])) {
            break;
        }

        foreach ($data['products'] as $product) {
            if (!isset($seenProductIds[$product['id']])) {
                $seenProductIds[$product['id']] = true;
                $allProducts[] = $product;
            }
        }

        $offset += $limit;
        if (count($allProducts) >= $maxProducts || count($data['products']) < $limit) {
            $hasMore = false;
        }
    }

    return $allProducts;
}

function fetchAllProducts() {
    $categoryUrls = [
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=1&showRecommendations=false&categories=99999",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.ownBrand%3Atrue&exclusionFilters=true",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=5&showRecommendations=false&filters=&groups=FOLLETOMARZO25&exclusionFilters=true",
        "https://tienda.consum.es/api/rest/V1.0/catalog/product?orderById=12&showRecommendations=false&filters=filter.novelty%3Atrue&exclusionFilters=true"
    ];

    $allProducts = [];
    $seenProductIds = [];

    foreach ($categoryUrls as $url) {
        $products = fetchProductsFromURL($url);
        foreach ($products as $product) {
            if (!isset($seenProductIds[$product['id']])) {
                $seenProductIds[$product['id']] = true;
                $allProducts[] = $product;
            }
        }
    }

    $generalProducts = fetchGeneralProducts();
    foreach ($generalProducts as $product) {
        if (!isset($seenProductIds[$product['id']])) {
            $seenProductIds[$product['id']] = true;
            $allProducts[] = $product;
        }
    }

    return $allProducts;
}

// Ejecutar la función y mostrar los productos en JSON
$products = fetchAllProducts();
echo json_encode($products, JSON_PRETTY_PRINT);

?>