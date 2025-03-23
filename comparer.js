const fs = require("fs");

// Función para leer JSON
function readJSON(filename) {
    return JSON.parse(fs.readFileSync(filename, "utf8"));
}

// Función para comparar los productos
function compareProducts(products1, products2) {
    const ids1 = new Set(products1.map(p => p.id));
    const ids2 = new Set(products2.map(p => p.id));

    const onlyInFirst = products1.filter(p => !ids2.has(p.id));
    const onlyInSecond = products2.filter(p => !ids1.has(p.id));

    console.log(`📌 Productos solo en productos_general.json (${onlyInFirst.length}):`);
    console.log(onlyInFirst);

    console.log(`📌 Productos solo en productos_categorias.json (${onlyInSecond.length}):`);
    console.log(onlyInSecond);
}

// Leer los archivos JSON
const productsGeneral = readJSON("productos_general.json");
const productsCategorias = readJSON("productos_categorias.json");

// Comparar los productos
compareProducts(productsGeneral, productsCategorias);
