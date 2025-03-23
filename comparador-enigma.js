const fs = require('fs');

// Cargar los archivos JSON
const productos1 = JSON.parse(fs.readFileSync('productos_filtrados.json', 'utf8'));
const productos2 = JSON.parse(fs.readFileSync('productos_php.json', 'utf8'));

// Obtener los IDs de cada archivo
const ids1 = new Set(productos1.map(p => p.id));
const ids2 = new Set(productos2.map(p => p.id));

// Productos que están en archivo1 pero no en archivo2
const soloEn1 = productos1.filter(p => !ids2.has(p.id));

// Productos que están en archivo2 pero no en archivo1
const soloEn2 = productos2.filter(p => !ids1.has(p.id));

// Mostrar resultados
console.log("Productos en archivo1 pero no en archivo2:", soloEn1);
console.log("Productos en archivo2 pero no en archivo1:", soloEn2);
