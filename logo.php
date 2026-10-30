<?php
/**
 * Servidor del Logo Oficial — La Casa del Buen Café
 * Copia automáticamente el archivo a assets/logo.jpg y lo sirve con caché HTTP
 */

$uploadedSource = 'C:/Users/ESTEFANIA OLAYA/.gemini/antigravity/brain/10302298-2931-4266-97ef-b3490139617c/.user_uploaded/media_1793335969524_831a91b4.jpg';
$localAsset     = __DIR__ . '/assets/logo.jpg';
$htdocsAsset    = 'C:/xampp/htdocs/La_casa_del_buen_cafe/assets/logo.jpg';

// Asegurar copia física en assets/logo.jpg tanto en el workspace como en htdocs
if (file_exists($uploadedSource)) {
    if (!file_exists($localAsset)) {
        if (!is_dir(dirname($localAsset))) {
            @mkdir(dirname($localAsset), 0777, true);
        }
        @copy($uploadedSource, $localAsset);
    }
    if (!file_exists($htdocsAsset)) {
        if (!is_dir(dirname($htdocsAsset))) {
            @mkdir(dirname($htdocsAsset), 0777, true);
        }
        @copy($uploadedSource, $htdocsAsset);
    }
}

$targetFile = file_exists($localAsset) ? $localAsset : $uploadedSource;

if (file_exists($targetFile)) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($targetFile);
    exit;
}

http_response_code(404);

