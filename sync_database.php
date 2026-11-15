<?php
/**
 * Script de sincronización de fotos reales y catálogo completo (16 productos + 10 categorías)
 * Actualiza tanto local_store.json como MySQL la_casa_del_buen_cafe
 */

$jsonFile = __DIR__ . '/data/local_store.json';
$data = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];

// 1. Asegurar categorías completas
$categorias = [
    ["id" => 1, "nombre" => "Tortas", "descripcion" => "Tortas artesanales recién horneadas para acompañar tu café", "icono" => "fa-cake-candles", "activo" => true],
    ["id" => 2, "nombre" => "Galletas Integral", "descripcion" => "Galletas integrales saludables con avena, miel y semillas", "icono" => "fa-cookie-bite", "activo" => true],
    ["id" => 3, "nombre" => "Empanadas Mixtas", "descripcion" => "Empanadas crujientes rellenas de combinación de carnes y queso", "icono" => "fa-utensils", "activo" => true],
    ["id" => 4, "nombre" => "Empanadas de Carne", "descripcion" => "Empanadas tradicionales con carne sazonada de la casa", "icono" => "fa-fire-burner", "activo" => true],
    ["id" => 5, "nombre" => "Empanadas de Pollo", "descripcion" => "Empanadas doradas rellenas de pollo desmechado criollo", "icono" => "fa-drumstick-bite", "activo" => true],
    ["id" => 6, "nombre" => "Platanitos Mixtos", "descripcion" => "Mezcla crujiente de platanitos verdes salados y maduros dulces", "icono" => "fa-bowl-food", "activo" => true],
    ["id" => 7, "nombre" => "Platanitos Maduros", "descripcion" => "Chips artesanales de plátano maduro naturalmente dulces", "icono" => "fa-sun", "activo" => true],
    ["id" => 8, "nombre" => "Platanitos Verdes", "descripcion" => "Chips crocantes de plátano verde con toque de sal marina", "icono" => "fa-leaf", "activo" => true],
    ["id" => 9, "nombre" => "Bebidas", "descripcion" => "Café de especialidad colombiano, bebidas calientes y frías", "icono" => "fa-mug-hot", "activo" => true],
    ["id" => 10, "nombre" => "Platanitos Mix de Chicharrón", "descripcion" => "Chicharrón artesanal crocante con platanitos", "icono" => "fa-cookie-bite", "activo" => true]
];

// 2. Catálogo Completo con TODAS las fotos reales del negocio
$productos = [
    [
        "id" => 1,
        "categoria_id" => 1,
        "codigo" => "TOR-001",
        "nombre" => "Torta Húmeda de Café y Nueces",
        "descripcion" => "Bizcocho artesanal bañado en reducción de espresso y nuez pecana.",
        "origen" => "Horneado Local",
        "tueste" => "N/A",
        "precio" => 11500,
        "costo" => 4200,
        "stock" => 20,
        "stock_minimo" => 5,
        "imagen_url" => "https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 2,
        "categoria_id" => 1,
        "codigo" => "TOR-002",
        "nombre" => "Torta Casera de Zanahoria y Especias",
        "descripcion" => "Con frosting suave de queso crema y canela recién molida.",
        "origen" => "Horneado Local",
        "tueste" => "N/A",
        "precio" => 10500,
        "costo" => 3900,
        "stock" => 15,
        "stock_minimo" => 5,
        "imagen_url" => "https://images.unsplash.com/photo-1621303837174-89787a7d4729?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 3,
        "categoria_id" => 2,
        "codigo" => "GALIN-99",
        "nombre" => "Galletas Integral de Avena",
        "descripcion" => "Paquete de galletas integrales artesanales endulzadas con miel.",
        "origen" => "Artesanal",
        "tueste" => "N/A",
        "precio" => 3500,
        "costo" => 2000,
        "stock" => 20,
        "stock_minimo" => 5,
        "imagen_url" => "https://images.unsplash.com/photo-1499636136210-6f4ee915583e?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 4,
        "categoria_id" => 3,
        "codigo" => "EMP-001",
        "nombre" => "Empanada Mixta Especial",
        "descripcion" => "Rellena de carne desmechada, pollo y corazón de queso fundido.",
        "origen" => "Cocina Local",
        "tueste" => "N/A",
        "precio" => 6000,
        "costo" => 4000,
        "stock" => 10,
        "stock_minimo" => 3,
        "imagen_url" => "uploads/prod_1793470733_605.png",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 5,
        "categoria_id" => 4,
        "codigo" => "EMP-002",
        "nombre" => "Empanada de Carne",
        "descripcion" => "Masa crocante de maíz con carne desmechada sazonada y ají casero.",
        "origen" => "Cocina Local",
        "tueste" => "N/A",
        "precio" => 6000,
        "costo" => 4000,
        "stock" => 10,
        "stock_minimo" => 5,
        "imagen_url" => "uploads/prod_1793470620_604.png",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 6,
        "categoria_id" => 5,
        "codigo" => "EMP-69",
        "nombre" => "Empanada de Pollo",
        "descripcion" => "Empanada dorada rellena de pollo desmechado criollo.",
        "origen" => "Cocina Local",
        "tueste" => "N/A",
        "precio" => 6000,
        "costo" => 4000,
        "stock" => 10,
        "stock_minimo" => 3,
        "imagen_url" => "uploads/prod_1793470380_426.png",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 7,
        "categoria_id" => 6,
        "codigo" => "PLA-001",
        "nombre" => "Paquete Platanitos Mixtos 150g",
        "descripcion" => "Combinación ideal de tajaditas verdes y maduras recién hechas.",
        "origen" => "Quindío, Colombia",
        "tueste" => "N/A",
        "precio" => 6500,
        "costo" => 2200,
        "stock" => 50,
        "stock_minimo" => 10,
        "imagen_url" => "uploads/prod_1794079260_727.jpg",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 8,
        "categoria_id" => 7,
        "codigo" => "PLA-002",
        "nombre" => "Platanitos Maduros Dulces 150g",
        "descripcion" => "Tajaditas crocantes de plátano maduro con su dulzor natural.",
        "origen" => "Valle del Cauca",
        "tueste" => "N/A",
        "precio" => 6000,
        "costo" => 2000,
        "stock" => 40,
        "stock_minimo" => 10,
        "imagen_url" => "uploads/prod_1794079588_669.jpg",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 9,
        "categoria_id" => 8,
        "codigo" => "PLA-003",
        "nombre" => "Platanitos Verdes Sabor Natural 40g",
        "descripcion" => "Chips de plátano verde extra crocantes con toque de sal marina.",
        "origen" => "Huila, Colombia",
        "tueste" => "N/A",
        "precio" => 3500,
        "costo" => 2000,
        "stock" => 5,
        "stock_minimo" => 2,
        "imagen_url" => "uploads/prod_1794073674_501.jpg",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 10,
        "categoria_id" => 9,
        "codigo" => "BEB-001",
        "nombre" => "Cappuccino Artesanal de Origen 8oz",
        "descripcion" => "Doble shot de espresso colombiano con leche texturizada sedosa.",
        "origen" => "Huila, Colombia",
        "tueste" => "Medio",
        "precio" => 8500,
        "costo" => 2800,
        "stock" => 120,
        "stock_minimo" => 15,
        "imagen_url" => "https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 11,
        "categoria_id" => 9,
        "codigo" => "BEB-002",
        "nombre" => "Cold Brew Naranja & Café",
        "descripcion" => "Infusión de café en frío por 18 horas, cítrico y refrescante.",
        "origen" => "Sierra Nevada",
        "tueste" => "Medio",
        "precio" => 11500,
        "costo" => 3800,
        "stock" => 45,
        "stock_minimo" => 10,
        "imagen_url" => "https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 12,
        "categoria_id" => 9,
        "codigo" => "CAF-001",
        "nombre" => "Café en Grano/Molido Reserva Huila 500g",
        "descripcion" => "Notas a panela, frutos rojos y chocolate oscuro. Puntaje SCA 86.",
        "origen" => "Pitalito, Huila",
        "tueste" => "Medio",
        "precio" => 42000,
        "costo" => 24000,
        "stock" => 35,
        "stock_minimo" => 8,
        "imagen_url" => "https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&w=600&q=80",
        "destacado" => true,
        "activo" => true
    ],
    [
        "id" => 13,
        "categoria_id" => 1,
        "codigo" => "TORED-84",
        "nombre" => "Torta Red Velvet",
        "descripcion" => "Delicioso bizcocho aterciopelado con crema suave de vainilla.",
        "origen" => "Huila, Colombia",
        "tueste" => "N/A",
        "precio" => 6000,
        "costo" => 3500,
        "stock" => 9,
        "stock_minimo" => 3,
        "imagen_url" => "https://tortasytortas.com.co/images/virtuemart/product/resized/red%20velvet%20web_640x200.jpg",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 14,
        "categoria_id" => 9,
        "codigo" => "SPEE-95",
        "nombre" => "Speed Max Energizante",
        "descripcion" => "Bebida energizante refrescante para acompañar tu jornada.",
        "origen" => "Colombia",
        "tueste" => "N/A",
        "precio" => 3500,
        "costo" => 2500,
        "stock" => 25,
        "stock_minimo" => 5,
        "imagen_url" => "uploads/prod_1793473739_358.jpg",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 15,
        "categoria_id" => 9,
        "codigo" => "AGUILI45",
        "nombre" => "Cerveza Águila Light",
        "descripcion" => "Cerveza nacional ligera y bien fría para compartir.",
        "origen" => "Colombia",
        "tueste" => "N/A",
        "precio" => 5000,
        "costo" => 3500,
        "stock" => 22,
        "stock_minimo" => 5,
        "imagen_url" => "uploads/prod_1793473266_255.jpg",
        "destacado" => false,
        "activo" => true
    ],
    [
        "id" => 16,
        "categoria_id" => 10,
        "codigo" => "PLAMC-073",
        "nombre" => "Platanitos Mix de Chicharrón 40g",
        "descripcion" => "Crocante chicharrón con platanitos fritos seleccionados.",
        "origen" => "Huila, Colombia",
        "tueste" => "N/A",
        "precio" => 3500,
        "costo" => 2000,
        "stock" => 8,
        "stock_minimo" => 5,
        "imagen_url" => "uploads/prod_1794080699_803.jpg",
        "destacado" => false,
        "activo" => true
    ]
];

// Actualizar JSON
$data['categorias'] = $categorias;
$data['productos'] = $productos;
file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "JSON store updated successfully with " . count($productos) . " products and " . count($categorias) . " categories.\n";

// Limpiar archivos de cache para que refresquen inmediatamente
$cacheDir = __DIR__ . '/data/cache';
if (is_dir($cacheDir)) {
    foreach (glob("$cacheDir/*") as $cf) {
        @unlink($cf);
    }
    echo "Cache files invalidated.\n";
}

// Intentar actualizar MySQL si la conexión está disponible
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=la_casa_del_buen_cafe;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Upsert categorías
    $stmtCat = $pdo->prepare("INSERT INTO categorias (id, nombre, descripcion, icono, activo) VALUES (:id, :nom, :desc, :ico, :act) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), icono = VALUES(icono)");
    foreach ($categorias as $c) {
        $stmtCat->execute([
            ':id'   => $c['id'],
            ':nom'  => $c['nombre'],
            ':desc' => $c['descripcion'],
            ':ico'  => $c['icono'],
            ':act'  => $c['activo'] ? 1 : 0
        ]);
    }

    // Upsert productos
    $stmtProd = $pdo->prepare("INSERT INTO productos (id, categoria_id, codigo, nombre, descripcion, origen, tueste, precio, costo, stock, stock_minimo, imagen_url, destacado, activo) 
        VALUES (:id, :cat, :cod, :nom, :desc, :orig, :tueste, :precio, :costo, :stock, :stk_min, :img, :dest, :act)
        ON DUPLICATE KEY UPDATE 
        categoria_id = VALUES(categoria_id),
        nombre = VALUES(nombre),
        descripcion = VALUES(descripcion),
        origen = VALUES(origen),
        tueste = VALUES(tueste),
        precio = VALUES(precio),
        costo = VALUES(costo),
        stock = VALUES(stock),
        stock_minimo = VALUES(stock_minimo),
        imagen_url = VALUES(imagen_url),
        destacado = VALUES(destacado),
        activo = VALUES(activo)");

    foreach ($productos as $p) {
        $stmtProd->execute([
            ':id'       => $p['id'],
            ':cat'      => $p['categoria_id'],
            ':cod'      => $p['codigo'],
            ':nom'      => $p['nombre'],
            ':desc'     => $p['descripcion'],
            ':orig'     => $p['origen'],
            ':tueste'   => $p['tueste'],
            ':precio'   => $p['precio'],
            ':costo'    => $p['costo'],
            ':stock'    => $p['stock'],
            ':stk_min'  => $p['stock_minimo'],
            ':img'      => $p['imagen_url'],
            ':dest'     => $p['destacado'] ? 1 : 0,
            ':act'      => $p['activo'] ? 1 : 0
        ]);
    }
    echo "MySQL database updated successfully with all real photos and 16 products!\n";
} catch (Exception $e) {
    echo "MySQL not reached from this CLI session: " . $e->getMessage() . " (JSON fallback is fully updated and active).\n";
}

