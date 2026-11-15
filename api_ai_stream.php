<?php
/**
 * API Streaming Token a Token (Server-Sent Events) - Barista IA en Vivo
 * La Casa del Buen Café
 */

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
for ($i = 0; $i < ob_get_level(); $i++) {
    @ob_end_flush();
}
ob_implicit_flush(1);

$query = trim($_GET['pregunta'] ?? '¿Qué me recomiendas hoy?');
$qLower = mb_strtolower($query);

// Respuestas del Sommelier / Barista de La Casa del Buen Café
$respuesta = "¡Hola! Bienvenido a La Casa del Buen Café. ";

if (str_contains($qLower, 'prensa') || str_contains($qLower, 'filtrado') || str_contains($qLower, 'metodo')) {
    $respuesta .= "Para métodos de filtrado y prensa francesa, te recomiendo nuestro grano Reserva Especial Huila de 500g con molienda media-gruesa. Resalta notas exquisitas a panela, frutos rojos y chocolate oscuro. Acompáñalo con nuestra Torta Casera de Zanahoria recién horneada.";
} elseif (str_contains($qLower, 'frio') || str_contains($qLower, 'calor') || str_contains($qLower, 'refrescante') || str_contains($qLower, 'tarde')) {
    $respuesta .= "Para un momento refrescante en Neiva, te recomiendo nuestro Cold Brew Naranja & Café, infusionado en frío durante 18 horas para extraer la dulzura natural del café sin acidez amarga. Hace un maridaje perfecto con los Platanitos Maduros Dulces.";
} elseif (str_contains($qLower, 'torta') || str_contains($qLower, 'dulce') || str_contains($qLower, 'postre')) {
    $respuesta .= "Si te apetece algo dulce, nuestra Torta Húmeda de Café y Nueces bañada en reducción de espresso es la joya de la casa. Si prefieres algo más suave, la nueva Torta Red Velvet acompañada de un Cappuccino Artesanal de 8oz es insuperable.";
} elseif (str_contains($qLower, 'salado') || str_contains($qLower, 'empanada') || str_contains($qLower, 'snack')) {
    $respuesta .= "De nuestra selección horneada y crujiente, no te pierdas la Empanada Mixta Especial con carne desmechada y queso fundido, o nuestro nuevo paquete de Platanitos Mix de Chicharrón artesanal.";
} else {
    $respuesta .= "Te recomiendo probar nuestro Cappuccino Artesanal 8oz preparado con doble shot de espresso del Huila y leche texturizada sedosa. Si deseas comprar para preparar en casa, el café en grano Reserva Especial Huila 500g tiene un puntaje SCA 86 de exportación.";
}

// Transmitir token a token
$tokens = preg_split('/(\s+)/u', $respuesta, -1, PREG_SPLIT_DELIM_CAPTURE);
foreach ($tokens as $token) {
    if ($token === '') continue;
    echo "data: " . json_encode(['token' => $token], JSON_UNESCAPED_UNICODE) . "\n\n";
    @flush();
    usleep(25000); // 25ms por token para una animación de tipeo fluida
}

echo "data: [DONE]\n\n";
@flush();

