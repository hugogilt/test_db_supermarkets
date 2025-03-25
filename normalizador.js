const fs = require('fs');
const natural = require('natural');
const stringSimilarity = require('string-similarity');

// Función para limpiar nombres de productos
function cleanName(name) {
    return name.toLowerCase()
        .replace(/(hacendado|consum|marca blanca|pascual|danone|coca-cola|pepsi|nestlé)/gi, "")
        .replace(/[^a-z0-9\s]/gi, "")
        .replace(/\s+/g, " ")
        .trim();
}

// Función para encontrar el producto más similar en la otra lista
function findBestMatch(product, productsList) {
    const cleanedProduct = cleanName(product.nombre);
    const cleanedList = productsList.map(p => cleanName(p.nombre));
    
    const matches = stringSimilarity.findBestMatch(cleanedProduct, cleanedList);
    
    if (matches.bestMatch.rating >= 0.7) { // Umbral de similitud
        return { original: product.nombre, match: productsList[matches.bestMatchIndex].nombre, similarity: matches.bestMatch.rating };
    }
    return { original: product.nombre, match: null, similarity: 0 };
}

// Cargar archivos JSON
const mercadonaProducts = JSON.parse(fs.readFileSync('productos_mercadona.json', 'utf8'));
const consumProducts = JSON.parse(fs.readFileSync('productos_consum.json', 'utf8'));

// Comparar productos
const matchedProducts = mercadonaProducts.map(product => findBestMatch(product, consumProducts));

// Guardar resultados
fs.writeFileSync('productos_normalizados.json', JSON.stringify(matchedProducts, null, 2));
console.log('Comparación completada. Resultados guardados en productos_normalizados.json');