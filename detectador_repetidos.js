const fs = require('fs');

// Cargar el archivo JSON con los productos
const productos = JSON.parse(fs.readFileSync('productos_filtrados.json', 'utf8'));

// Objeto para rastrear productos por nombre
const productosPorNombre = {};
const productosDuplicados = [];

// Analizar los productos y detectar nombres repetidos con diferente ID
productos.forEach(producto => {
    const nombre = producto.name;
    const id = producto.id;

    if (!productosPorNombre[nombre]) {
        productosPorNombre[nombre] = new Set();
    }

    productosPorNombre[nombre].add(id);
});

// Filtrar los nombres que tienen más de un ID
Object.entries(productosPorNombre).forEach(([nombre, ids]) => {
    if (ids.size > 1) {
        productosDuplicados.push({
            name: nombre,
            ids: Array.from(ids)
        });
    }
});

// Guardar los productos repetidos en un nuevo archivo JSON
fs.writeFileSync('productos_mismo_nombre2.json', JSON.stringify(productosDuplicados, null, 4));

console.log(`Se encontraron ${productosDuplicados.length} nombres de productos con diferentes IDs.`);
console.log('El resultado se ha guardado en productos_mismo_nombre.json');
