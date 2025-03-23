<?php

function fetchProductsFromURL($url) {
    $allProducts = [];
    $seenProductIds = [];
    $offset = 0;
    $limit = 100;
    $maxProducts = 2900;
    $hasMore = true;

    while ($hasMore) {
        $apiUrl = "{$url}&limit={$limit}&offset={$offset}";
        $response = file_get_contents($apiUrl);
        $data = json_decode($response, true);

        if (!$data || !isset($data['products']) || !is_array($data['products'])) {
            break;
        }

        foreach ($data['products'] as $product) {
            if (!isset($seenProductIds[$product['id']])) {
                $seenProductIds[$product['id']] = true;

                $id = $product['id'];
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
        }

        $offset += $limit;
        if (count($data['products']) < $limit || count($allProducts) >= $maxProducts) {
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

    $generalProducts = fetchProductsFromURL("https://tienda.consum.es/api/rest/V1.0/catalog/product?page=1");
    foreach ($generalProducts as $product) {
        if (!isset($seenProductIds[$product['id']])) {
            $seenProductIds[$product['id']] = true;
            $allProducts[] = $product;
        }        
    }

    return $allProducts;
}

$products = fetchAllProducts();
file_put_contents("productos_filtrados.json", json_encode($products, JSON_PRETTY_PRINT));

echo "Productos guardados en productos.json\n";
?>
