<?php
$url = "https://tienda.consum.es/api/rest/V1.0/catalog/product?page=1&limit=100&offset=2800";
$response = file_get_contents($url);

if ($response === false) {
    die("❌ No se pudo obtener la respuesta de la API.\n");
}

$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT); // Imprime toda la respuesta con formato bonito
?>
