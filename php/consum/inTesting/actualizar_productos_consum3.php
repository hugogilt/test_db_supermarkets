<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Extiende el tiempo de ejecución si es necesario

require '../../config/conexion.php'; // Conexión a la base de datos


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

            // Contar la cantidad de veces que aparece cada producto
            $productCounts[$id] = ($productCounts[$id] ?? 0) + 1;
            if ($productCounts[$id] >= 3) {
                return [];
            }

            // Evitar productos duplicados
            if (isset($seenProductIds[$id])) {
                continue;
            }
            $seenProductIds[$id] = true;

            // Obtener detalles del producto
            $name = $product['productData']['name'] . ' ' . $product['productData']['brand']['name'];
            $slug = $product['productData']['seo'];
            $description = $product['productData']['description'];
            $image = $product['productData']['imageURL'];

            // Obtener precios
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

            // Verificar si es una oferta Pack
            $ofertaPack = false;
            $unidadesPack = 0;
            $precioUnidadPack = 0;
            if (isset($product['offers'])) {
                foreach ($product['offers'] as $offer) {
                    if (isset($offer['shortDescription']) && $offer['shortDescription'] === "Oferta Pack") {
                        $ofertaPack = true;
                        if (isset($offer['sectionUnits'])) {
                            $unidadesPack = $offer['sectionUnits']['amount'] ?? 0;
                        }
                        if (isset($offer['unitPrice'])) {
                            $precioUnidadPack = $offer['unitPrice'] ?? 0;
                        }
                    }
                }
            }

            $allProducts[] = [
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'price_kilo' => $price_kilo,
                'slug' => $slug,
                'description' => $description,
                'image' => $image,
                'ofertaPack' => $ofertaPack,
                'unidadesPack' => $unidadesPack,
                'precioUnidadPack' => $precioUnidadPack
            ];
        }

        $offset += $limit;
        if (count($data['products']) < $limit) {
            $hasMore = false;
        }
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

            if (max($productCounts) >= 3) {
                echo "Se ha encontrado un producto 3 veces. Deteniendo búsqueda...\n";
                break 2;
            }
        }
    }

    return $allProducts;
}

function insertProductsIntoDatabase($products) {
    global $conexion;
    try {
        // Iniciar transacción
        if (!$conexion->inTransaction()) {
            $conexion->beginTransaction();
        }

        // Limpiar la tabla antes de insertar los nuevos productos
        $conexion->exec("DELETE FROM productos_consum");

        // Preparar la consulta para la inserción
        $sql = "INSERT INTO productos_consum (id, nombre, precio, slug, descripcion, imagen, oferta_pack, unidades_pack, precio_unidad_pack) 
                VALUES (:id, :nombre, :precio, :slug, :descripcion, :imagen, :oferta_pack, :unidades_pack, :precio_unidad_pack)";
        $stmt = $conexion->prepare($sql);

        $batchSize = 1000;
        $batch = [];

        foreach ($products as $product) {
            $batch[] = $product;

            if (count($batch) >= $batchSize) {
                insertBatch($stmt, $batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            insertBatch($stmt, $batch);
        }

        $conexion->commit();
        echo "Productos insertados correctamente.\n";
    } catch (PDOException $e) {
        // Verificar si hay una transacción activa antes de hacer rollback
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        echo "Error al insertar productos: " . $e->getMessage();
    }
}


function insertBatch($stmt, $batch) {
    foreach ($batch as $product) {
        $stmt->execute([
            ':id' => $product['id'],
            ':nombre' => $product['name'],
            ':precio' => $product['price'],
            ':slug' => $product['slug'],
            ':descripcion' => $product['description'],
            ':imagen' => $product['image'],
            ':oferta_pack' => $product['ofertaPack'],
            ':unidades_pack' => $product['unidadesPack'],
            ':precio_unidad_pack' => $product['precioUnidadPack']
        ]);
    }
}

$products = fetchAllProducts();
insertProductsIntoDatabase($products);
