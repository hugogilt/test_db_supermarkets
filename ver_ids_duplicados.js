const fs = require('fs');

// Cargar archivo1.json
const productos1 = JSON.parse(fs.readFileSync('productos_filtrados_bucle_page.json', 'utf8'));

// Objeto para contar cuántas veces aparece cada ID
const idCount = {};
const duplicados = [];

// Contar ocurrencias de cada ID
productos1.forEach(producto => {
    idCount[producto.id] = (idCount[producto.id] || 0) + 1;
});

// Filtrar los productos que tienen un ID duplicado
productos1.forEach(producto => {
    if (idCount[producto.id] > 1 && !duplicados.some(p => p.id === producto.id)) {
        duplicados.push(producto);
    }
});

// Mostrar resultados
console.log(`Total IDs duplicados: ${duplicados.length}`);
console.log("Ejemplo de productos duplicados:", duplicados.slice(0, 5));
