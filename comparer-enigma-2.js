const fs = require('fs');

// Cargar los archivos JSON
const productos1 = JSON.parse(fs.readFileSync('productos_php-v2.JSON', 'utf8'));
const productos2 = JSON.parse(fs.readFileSync('productos_filtrados_bucle_page.json', 'utf8'));

// Contar productos en cada archivo
console.log(`Total productos en archivo1: ${productos1.length}`);
console.log(`Total productos en archivo2: ${productos2.length}`);

// Obtener los IDs de cada archivo
const ids1 = new Set(productos1.map(p => p.id));
const ids2 = new Set(productos2.map(p => p.id));

console.log(`Total IDs únicos en archivo1: ${ids1.size}`);
console.log(`Total IDs únicos en archivo2: ${ids2.size}`);

// Verificar productos faltantes
const soloEn1 = productos1.filter(p => !ids2.has(p.id));
const soloEn2 = productos2.filter(p => !ids1.has(p.id));

console.log(`Productos en archivo1 pero no en archivo2: ${soloEn1.length}`);
console.log(`Productos en archivo2 pero no en archivo1: ${soloEn2.length}`);

// Mostrar algunos ejemplos para analizar
console.log("Ejemplo de productos en archivo1 pero no en archivo2:", soloEn1);
console.log("Ejemplo de productos en archivo2 pero no en archivo1:", soloEn2);
