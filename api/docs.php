<?php
/**
 * Portal Interactivo de Documentación y Pruebas en Vivo de la API REST
 * La Casa del Buen Café
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth_middleware.php';

$db = getApiDb();
$engineName = $db->getActiveEngineName();
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#120A06] text-[#F8F5F0]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API REST Docs & Sandbox — La Casa del Buen Café</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .rule-title { font-size: 20px !important; line-height: 1.35 !important; font-weight: 700 !important; }
        .rule-text { font-size: 14px !important; line-height: 1.6 !important; }
        .rule-btn { font-size: 14px !important; font-weight: 700 !important; }
        pre code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1a0f09; }
        ::-webkit-scrollbar-thumb { background: #3d2316; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #e2aa63; }
    </style>
</head>
<body class="min-h-full flex flex-col bg-[#120A06] text-[#F8F5F0]">

    <!-- Encabezado Corporativo -->
    <header class="border-b border-[#3D2316] bg-[#1a0f09]/90 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#E2AA63] to-[#B37B30] flex items-center justify-center text-[#120A06] shadow-lg shadow-[#E2AA63]/20">
                    <i class="fa-solid fa-code text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="rule-title tracking-tight text-white font-serif">La Casa del Buen Café</h1>
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold tracking-wide uppercase bg-[#E2AA63]/20 text-[#E2AA63] border border-[#E2AA63]/30">API REST v2.5</span>
                    </div>
                    <p class="text-xs text-[#A89F91]">Documentación Oficial & Consola Interactiva de Pruebas</p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-[#26150E] border border-[#3D2316] text-[#E2AA63]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <?= htmlspecialchars($engineName) ?>
                </span>
                <a href="../index.php" class="rule-btn px-3.5 py-1.5 rounded-lg border border-[#3D2316] text-[#F8F5F0] hover:bg-[#26150E] transition flex items-center gap-2">
                    <i class="fa-solid fa-store text-xs text-[#E2AA63]"></i> Tienda Web
                </a>
                <a href="../portal_privado.php" class="rule-btn px-3.5 py-1.5 rounded-lg border border-[#3D2316] text-[#F8F5F0] hover:bg-[#26150E] transition flex items-center gap-2">
                    <i class="fa-solid fa-lock text-xs text-[#E2AA63]"></i> Portal Privado
                </a>
            </div>
        </div>
    </header>

    <!-- Barra de Autenticación Rápida (1-Clic Token Generator) -->
    <section class="bg-[#1f120b] border-b border-[#3D2316] px-4 py-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex-1 w-full">
                <div class="flex items-center gap-2 mb-1.5">
                    <i class="fa-solid fa-key text-xs text-[#E2AA63]"></i>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#A89F91]">Token Bearer de Autorización Activo:</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" id="globalAuthToken" placeholder="Ingresa token Bearer o genera uno abajo con 1 clic..." 
                           class="w-full bg-[#120A06] border border-[#3D2316] rounded-lg px-3 py-2 text-xs text-[#F8F5F0] font-mono focus:border-[#E2AA63] focus:outline-none transition">
                    <button onclick="clearToken()" class="rule-btn px-3 py-2 rounded-lg bg-[#26150E] hover:bg-[#3D2316] text-xs text-[#A89F91] transition" title="Limpiar token">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-1 w-full md:w-auto">
                <span class="text-xs text-[#A89F91] font-semibold">Generar Token de Prueba (1 Clic):</span>
                <div class="flex items-center gap-2 flex-wrap">
                    <button onclick="quickAuth('admin')" class="rule-btn px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-rose-300 transition flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-crown text-[11px]"></i> Admin
                    </button>
                    <button onclick="quickAuth('cajero')" class="rule-btn px-3 py-1.5 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 transition flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-cash-register text-[11px]"></i> Cajero
                    </button>
                    <button onclick="quickAuth('barista')" class="rule-btn px-3 py-1.5 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 transition flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-mug-saucer text-[11px]"></i> Barista
                    </button>
                    <button onclick="quickAuth('cliente')" class="rule-btn px-3 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 transition flex items-center gap-1.5 text-xs">
                        <i class="fa-solid fa-user text-[11px]"></i> Cliente
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido Principal: Explorador de Endpoints -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Menú Lateral de Módulos -->
        <aside class="lg:col-span-3 space-y-2">
            <div class="p-3 bg-[#1a0f09] rounded-xl border border-[#3D2316] mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-[#A89F91] block mb-2">Servidor Base URL</span>
                <code class="text-xs bg-[#120A06] p-2 rounded block text-[#E2AA63] font-mono break-all" id="baseUrlDisplay">http://localhost/La_casa_del_buen_cafe/api</code>
            </div>

            <nav class="space-y-1">
                <a href="#sec-auth" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-shield-halved text-[#E2AA63] w-4 text-center"></i> 1. Autenticación & Tokens
                </a>
                <a href="#sec-productos" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-box text-[#E2AA63] w-4 text-center"></i> 2. Catálogo & Productos CRUD
                </a>
                <a href="#sec-pedidos" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-receipt text-[#E2AA63] w-4 text-center"></i> 3. Pedidos & Comandas KDS
                </a>
                <a href="#sec-clientes" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-users text-[#E2AA63] w-4 text-center"></i> 4. Clientes & Fidelización
                </a>
                <a href="#sec-categorias" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-tags text-[#E2AA63] w-4 text-center"></i> 5. Categorías de Menú
                </a>
                <a href="#sec-inventario" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-warehouse text-[#E2AA63] w-4 text-center"></i> 6. Inventario & Kardex
                </a>
                <a href="#sec-stats" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-[#F8F5F0] hover:bg-[#26150E] transition">
                    <i class="fa-solid fa-chart-line text-[#E2AA63] w-4 text-center"></i> 7. Métricas & Analítica
                </a>
            </nav>
        </aside>

        <!-- Lista de Endpoints y Consola Interactiva -->
        <div class="lg:col-span-9 space-y-10">

            <!-- 1. AUTENTICACIÓN -->
            <section id="sec-auth" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-[#E2AA63]"></i> 1. Autenticación y Autorización
                    </h2>
                    <span class="text-xs text-[#A89F91]">Bearer JWT HS256</span>
                </div>

                <!-- Endpoint: POST /auth/login -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="POST" data-url="auth/login">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-400 font-bold text-xs uppercase">POST</span>
                            <code class="text-sm font-mono text-white">/api/auth/login</code>
                            <span class="text-xs text-[#A89F91]">Iniciar sesión y generar Bearer Token</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-500/20 text-gray-300">Público</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <p class="rule-text text-sm text-[#C4B9A8]">Permite a cualquier usuario (Admin, Cajero, Barista o Cliente) iniciar sesión. Devuelve un Bearer Token firmado para incluir en cabeceras <code>Authorization: Bearer &lt;token&gt;</code>.</p>
                        <div>
                            <label class="text-xs font-semibold text-[#A89F91] block mb-1">Cuerpo JSON de la Solicitud:</label>
                            <textarea class="w-full bg-[#120A06] border border-[#3D2316] rounded p-2 text-xs font-mono text-[#F8F5F0] h-24" data-payload>
{
  "email": "admin@casadelbuencafe.com",
  "password": "",
  "rol": "admin"
}
                            </textarea>
                        </div>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: GET /auth/me -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="auth/me">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/auth/me</code>
                            <span class="text-xs text-[#A89F91]">Consultar perfil y permisos del usuario actual</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-300">Requiere Auth</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <p class="rule-text text-sm text-[#C4B9A8]">Verifica el Token Bearer activo y retorna la identidad del usuario y sus permisos de acceso.</p>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 2. PRODUCTOS CRUD -->
            <section id="sec-productos" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-box text-[#E2AA63]"></i> 2. Catálogo & Productos CRUD
                    </h2>
                    <span class="text-xs text-[#A89F91]">Catálogo, Precios & Existencias</span>
                </div>

                <!-- Endpoint: GET /productos -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="productos">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/productos</code>
                            <span class="text-xs text-[#A89F91]">Listar catálogo con filtros y fotos reales</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-500/20 text-gray-300">Público</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <p class="rule-text text-sm text-[#C4B9A8]">Obtiene los productos con paginación, alertas de stock mínimo y filtros opcionales (<code>?categoria_id=1&q=cafe&destacado=1</code>).</p>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: POST /productos -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="POST" data-url="productos">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-400 font-bold text-xs uppercase">POST</span>
                            <code class="text-sm font-mono text-white">/api/productos</code>
                            <span class="text-xs text-[#A89F91]">Crear nuevo producto y asentar stock inicial</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Admin</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <p class="rule-text text-sm text-[#C4B9A8]">Crea un producto en catálogo. Si se especifica stock inicial &gt; 0, genera automáticamente la entrada en el Kardex.</p>
                        <div>
                            <label class="text-xs font-semibold text-[#A89F91] block mb-1">Cuerpo JSON:</label>
                            <textarea class="w-full bg-[#120A06] border border-[#3D2316] rounded p-2 text-xs font-mono text-[#F8F5F0] h-32" data-payload>
{
  "categoria_id": 9,
  "codigo": "MOC-001",
  "nombre": "Mocaccino Especial Huila 8oz",
  "descripcion": "Espresso especial, chocolate semiamargo y leche texturizada.",
  "precio": 9500,
  "costo": 3500,
  "stock": 30,
  "stock_minimo": 8,
  "destacado": 1
}
                            </textarea>
                        </div>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: PATCH /productos/1 -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="PATCH" data-url="productos/1">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-purple-500/20 text-purple-400 font-bold text-xs uppercase">PATCH</span>
                            <code class="text-sm font-mono text-white">/api/productos/{id}</code>
                            <span class="text-xs text-[#A89F91]">Actualización parcial / ajuste de stock o precio</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Admin</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <div>
                            <label class="text-xs font-semibold text-[#A89F91] block mb-1">Cuerpo JSON:</label>
                            <textarea class="w-full bg-[#120A06] border border-[#3D2316] rounded p-2 text-xs font-mono text-[#F8F5F0] h-20" data-payload>
{
  "precio": 12000,
  "stock": 25,
  "motivo_ajuste": "Actualización de tarifa y recuento físico"
}
                            </textarea>
                        </div>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. PEDIDOS & KDS -->
            <section id="sec-pedidos" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-[#E2AA63]"></i> 3. Pedidos & Comandas KDS
                    </h2>
                    <span class="text-xs text-[#A89F91]">POS, Checkout & Cocina</span>
                </div>

                <!-- Endpoint: GET /pedidos -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="pedidos">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/pedidos</code>
                            <span class="text-xs text-[#A89F91]">Listar pedidos con desglose de ítems</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-300">Staff / Cliente</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <p class="rule-text text-sm text-[#C4B9A8]">Carga las órdenes con sus productos sin problema N+1. Si el usuario autenticado es un cliente, solo visualiza sus propias comandas.</p>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: POST /pedidos -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="POST" data-url="pedidos">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-400 font-bold text-xs uppercase">POST</span>
                            <code class="text-sm font-mono text-white">/api/pedidos</code>
                            <span class="text-xs text-[#A89F91]">Crear comanda / venta (descuenta stock, Kardex, puntos)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-500/20 text-gray-300">Omnicanal</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <div>
                            <label class="text-xs font-semibold text-[#A89F91] block mb-1">Cuerpo JSON:</label>
                            <textarea class="w-full bg-[#120A06] border border-[#3D2316] rounded p-2 text-xs font-mono text-[#F8F5F0] h-36" data-payload>
{
  "tipo": "tienda_online",
  "metodo_pago": "efectivo",
  "cliente_nombre": "Estefanía Olaya",
  "cliente_email": "estefania@ejemplo.com",
  "cliente_telefono": "3105551234",
  "direccion_entrega": "San Juan Plaza Local 151",
  "notas": "Por favor con leche deslactosada",
  "items": [
    { "producto_id": 10, "cantidad": 1 },
    { "producto_id": 1, "cantidad": 1 }
  ]
}
                            </textarea>
                        </div>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: PATCH /pedidos/1/estado -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="PATCH" data-url="pedidos/1/estado">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-purple-500/20 text-purple-400 font-bold text-xs uppercase">PATCH</span>
                            <code class="text-sm font-mono text-white">/api/pedidos/{id}/estado</code>
                            <span class="text-xs text-[#A89F91]">Actualizar estado en KDS (preparando, completado)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-bold">Barista / Staff</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <div>
                            <label class="text-xs font-semibold text-[#A89F91] block mb-1">Cuerpo JSON:</label>
                            <textarea class="w-full bg-[#120A06] border border-[#3D2316] rounded p-2 text-xs font-mono text-[#F8F5F0] h-16" data-payload>
{
  "estado": "preparando"
}
                            </textarea>
                        </div>
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 4. CLIENTES & FIDELIZACIÓN -->
            <section id="sec-clientes" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-users text-[#E2AA63]"></i> 4. Clientes & Fidelización Club del Café
                    </h2>
                    <span class="text-xs text-[#A89F91]">Tarjeta VIP & Puntos</span>
                </div>

                <!-- Endpoint: GET /clientes/2/puntos -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="clientes/2/puntos">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/clientes/{id}/puntos</code>
                            <span class="text-xs text-[#A89F91]">Consultar saldo de puntos, nivel y catálogo de recompensas</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300">Club Socios</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. INVENTARIO & KARDEX -->
            <section id="sec-inventario" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-warehouse text-[#E2AA63]"></i> 5. Inventario & Kardex
                    </h2>
                    <span class="text-xs text-[#A89F91]">Auditoría & Reabastecimiento</span>
                </div>

                <!-- Endpoint: GET /inventario -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="inventario">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/inventario</code>
                            <span class="text-xs text-[#A89F91]">Resumen de inventario, valuación COP y alertas críticas</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Admin</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: GET /inventario/kardex -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="inventario/kardex">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/inventario/kardex</code>
                            <span class="text-xs text-[#A89F91]">Historial inmutable de movimientos de stock</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Admin</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 6. ESTADÍSTICAS & DASHBOARD -->
            <section id="sec-stats" class="space-y-4">
                <div class="border-b border-[#3D2316] pb-2 flex items-center justify-between">
                    <h2 class="rule-title text-white font-serif flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-[#E2AA63]"></i> 6. Estadísticas & Métricas
                    </h2>
                    <span class="text-xs text-[#A89F91]">KPIs Financieros & Cola KDS</span>
                </div>

                <!-- Endpoint: GET /stats/dashboard -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="stats/dashboard">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/stats/dashboard</code>
                            <span class="text-xs text-[#A89F91]">KPIs de ventas, ticket promedio y Top 5 productos</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold">Admin</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>

                <!-- Endpoint: GET /stats/kds -->
                <div class="endpoint-card bg-[#1a0f09] border border-[#3D2316] rounded-xl overflow-hidden" data-method="GET" data-url="stats/kds">
                    <div class="p-4 flex flex-wrap items-center justify-between gap-3 bg-[#22130b] cursor-pointer" onclick="toggleDetails(this)">
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded bg-blue-500/20 text-blue-400 font-bold text-xs uppercase">GET</span>
                            <code class="text-sm font-mono text-white">/api/stats/kds</code>
                            <span class="text-xs text-[#A89F91]">Estado en tiempo real de colas en cocina y barra</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-300 font-bold">Barista / Caja</span>
                            <i class="fa-solid fa-chevron-down text-xs text-[#A89F91] transition-transform"></i>
                        </div>
                    </div>
                    <div class="p-4 border-t border-[#3D2316] space-y-3 hidden">
                        <button onclick="executeApi(this)" class="rule-btn px-4 py-2 rounded-lg bg-[#E2AA63] hover:bg-[#d4994f] text-[#120A06] text-xs transition flex items-center gap-2">
                            <i class="fa-solid fa-play"></i> Probar Endpoint
                        </button>
                        <div class="response-box hidden space-y-1">
                            <span class="text-xs font-semibold text-[#A89F91] block">Respuesta HTTP:</span>
                            <pre class="bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-emerald-400 font-mono"></pre>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-[#3D2316] py-6 px-4 bg-[#140b07] text-center text-xs text-[#A89F91]">
        <p>La Casa del Buen Café — Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila</p>
        <p class="mt-1 text-[#6e6355]">API RESTful Compatible con Estándar OpenAPI 3.0 &bull; Arquitectura Desacoplada PHP 8.2 &bull; 2026</p>
    </footer>

    <!-- Lógica JavaScript del Cliente de Pruebas -->
    <script>
        const API_BASE = window.location.origin + window.location.pathname.replace(/\/api\/docs\.php.*/, '/api');
        document.getElementById('baseUrlDisplay').textContent = API_BASE;

        function toggleDetails(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('.fa-chevron-down');
            body.classList.toggle('hidden');
            if (icon) {
                icon.style.transform = body.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        }

        function clearToken() {
            document.getElementById('globalAuthToken').value = '';
            localStorage.removeItem('cbc_test_token');
        }

        // Cargar token persistido en localStorage
        const savedToken = localStorage.getItem('cbc_test_token');
        if (savedToken) {
            document.getElementById('globalAuthToken').value = savedToken;
        }

        // Generar token con 1 clic para cualquier rol de prueba
        async function quickAuth(rol) {
            const emails = {
                admin: 'admin@casadelbuencafe.com',
                cajero: 'cajero@casadelbuencafe.com',
                barista: 'barista@casadelbuencafe.com',
                cliente: 'estefania@ejemplo.com'
            };
            const email = emails[rol] || 'admin@casadelbuencafe.com';

            try {
                const res = await fetch(API_BASE + '/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email, rol: rol })
                });
                const data = await res.json();
                if (data.ok && data.data && data.data.token) {
                    const token = data.data.token;
                    document.getElementById('globalAuthToken').value = token;
                    localStorage.setItem('cbc_test_token', token);
                    alert(`¡Token generado exitosamente como [${rol.toUpperCase()}]!\nUsuario: ${data.data.user.nombre}`);
                } else {
                    alert('Error al generar token: ' + (data.message || 'Error desconocido'));
                }
            } catch (err) {
                alert('No se pudo conectar con el servidor: ' + err.message);
            }
        }

        // Ejecutar petición HTTP hacia el endpoint seleccionado
        async function executeApi(btn) {
            const card = btn.closest('.endpoint-card');
            const method = card.getAttribute('data-method');
            const endpointPath = card.getAttribute('data-url');
            const responseBox = card.querySelector('.response-box');
            const preOutput = responseBox.querySelector('pre');
            const payloadArea = card.querySelector('[data-payload]');

            const token = document.getElementById('globalAuthToken').value.trim();
            const headers = {
                'Accept': 'application/json'
            };
            if (token) {
                headers['Authorization'] = 'Bearer ' + token;
            }

            let body = null;
            if (['POST', 'PUT', 'PATCH'].includes(method) && payloadArea) {
                headers['Content-Type'] = 'application/json';
                body = payloadArea.value.trim();
            }

            responseBox.classList.remove('hidden');
            preOutput.textContent = 'Enviando petición a ' + API_BASE + '/' + endpointPath + '...';
            preOutput.className = 'bg-[#120A06] p-3 rounded text-xs border border-[#3D2316] overflow-x-auto text-amber-300 font-mono';

            const startTime = performance.now();
            try {
                const url = API_BASE + '/' + endpointPath;
                const resp = await fetch(url, {
                    method: method,
                    headers: headers,
                    body: body
                });

                const dur = Math.round(performance.now() - startTime);
                const json = await resp.json();
                const formatted = JSON.stringify(json, null, 2);

                preOutput.textContent = `// HTTP Status: ${resp.status} ${resp.statusText} (${dur} ms)\n\n` + formatted;
                if (resp.ok) {
                    preOutput.className = 'bg-[#120A06] p-3 rounded text-xs border border-emerald-500/30 overflow-x-auto text-emerald-400 font-mono';
                } else {
                    preOutput.className = 'bg-[#120A06] p-3 rounded text-xs border border-rose-500/30 overflow-x-auto text-rose-400 font-mono';
                }
            } catch (e) {
                preOutput.textContent = 'Error en la petición de red: ' + e.message;
                preOutput.className = 'bg-[#120A06] p-3 rounded text-xs border border-rose-500/30 overflow-x-auto text-rose-400 font-mono';
            }
        }
    </script>
</body>
</html>
