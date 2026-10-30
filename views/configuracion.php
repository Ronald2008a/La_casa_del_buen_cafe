<?php
/**
 * Vista: Conexión con XAMPP MySQL (PDO / phpMyAdmin), Supabase PostgreSQL y Auth
 * La Casa del Buen Café
 */
$mysqlSchemaContent = file_exists(__DIR__ . '/../database/mysql_schema.sql')
    ? file_get_contents(__DIR__ . '/../database/mysql_schema.sql')
    : '-- Archivo database/mysql_schema.sql no encontrado';

$supabaseSqlContent = file_exists(__DIR__ . '/../supabase/schema.sql')
    ? file_get_contents(__DIR__ . '/../supabase/schema.sql')
    : '-- Archivo supabase/schema.sql no encontrado';

$mysqlCfg = $db->getMySQLConfig();
?>
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Columna Izquierda (6 cols): XAMPP MySQL + Supabase + Auth -->
    <div class="lg:col-span-6 space-y-6">

        <!-- 1. TARJETA CONEXIÓN XAMPP MYSQL -->
        <div class="bg-white rounded-2xl border-2 <?= $db->isMySQLActive() ? 'border-emerald-500/60' : 'border-amber-400/70' ?> shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h2 class="text-lg font-extrabold text-stone-800 flex items-center gap-2">
                        <i class="fa-solid fa-server text-amber-600"></i> Conexión Local XAMPP MySQL
                    </h2>
                    <p class="text-xs text-stone-500">Conecta directamente la página web con el motor MySQL/MariaDB de XAMPP</p>
                </div>
                <?php if ($db->isMySQLActive()): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> MYSQL CONECTADO
                    </span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-red-100 text-red-700">
                        MYSQL APAGADO
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$db->isMySQLActive() && $db->getMySQLError()): ?>
                <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-800">
                    <strong><i class="fa-solid fa-triangle-exclamation mr-1"></i> Aviso:</strong>
                    Abre el <strong>XAMPP Control Panel</strong> y presiona <strong>Start</strong> en el módulo <strong>MySQL</strong>.<br>
                    <span class="font-mono text-[11px] text-red-600">Detalle: <?= htmlspecialchars($db->getMySQLError()) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-3 text-xs">
                <input type="hidden" name="action" value="save_mysql_config">

                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block font-bold text-stone-700 mb-1">Servidor Host MySQL</label>
                        <input type="text" name="mysql_host" value="<?= htmlspecialchars($mysqlCfg['host']) ?>" required
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Puerto</label>
                        <input type="text" name="mysql_port" value="<?= htmlspecialchars($mysqlCfg['port']) ?>" required
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Base de Datos</label>
                        <input type="text" name="mysql_database" value="<?= htmlspecialchars($mysqlCfg['database']) ?>" required
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Usuario</label>
                        <input type="text" name="mysql_user" value="<?= htmlspecialchars($mysqlCfg['user']) ?>" required
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-stone-700 mb-1">Contraseña</label>
                        <input type="password" name="mysql_password" value="<?= htmlspecialchars($mysqlCfg['password']) ?>" placeholder="(vacío en XAMPP)"
                               class="w-full px-3 py-2 rounded-xl border border-stone-200 font-mono">
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-[#2C1810] hover:bg-[#4A2C1D] text-white font-bold shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-database"></i> Conectar y Crear Tablas en MySQL
                    </button>

                    <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=<?= urlencode($mysqlCfg['database']) ?>"
                       target="_blank"
                       class="px-4 py-2.5 rounded-xl bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold transition flex items-center gap-1.5">
                        <i class="fa-solid fa-up-right-from-square"></i> Abrir en phpMyAdmin
                    </a>
                </div>
            </form>
        </div>

        <!-- 2. TARJETA SUPABASE CLOUD -->
        <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-extrabold text-stone-800 flex items-center gap-2">
                        <i class="fa-solid fa-bolt text-emerald-600"></i> Sincronización en la Nube con Supabase (Opcional)
                    </h2>
                    <p class="text-xs text-stone-500">Conecta tu proyecto de Supabase para respaldar o trabajar en la nube PostgreSQL</p>
                </div>
                <?php if ($db->isConnected()): ?>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800">CONECTADO</span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-stone-100 text-stone-600">OPCIONAL</span>
                <?php endif; ?>
            </div>

            <form method="POST" action="index.php" class="space-y-3 text-xs">
                <input type="hidden" name="action" value="save_supabase_config">
                <div>
                    <label class="block font-bold text-stone-700 mb-1">Supabase Project URL</label>
                    <input type="url" name="supabase_url" required
                           value="<?= htmlspecialchars($db->getUrl()) ?>"
                           placeholder="https://xxxxxxxxxxxxxxxxxxxx.supabase.co"
                           class="w-full px-3.5 py-2 rounded-xl border border-stone-200 font-mono text-xs">
                </div>
                <div>
                    <label class="block font-bold text-stone-700 mb-1">Supabase API Key (`anon` / `service_role`)</label>
                    <input type="password" name="supabase_key" required
                           placeholder="<?= $db->isConnected() ? htmlspecialchars($db->getMaskedKey()) : 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...' ?>"
                           class="w-full px-3.5 py-2 rounded-xl border border-stone-200 font-mono text-xs">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow transition flex items-center gap-2">
                    <i class="fa-solid fa-plug-circle-check"></i> Guardar Conexión Supabase
                </button>
            </form>
        </div>

        <!-- 3. AUTENTICACIÓN DE USUARIOS -->
        <div class="bg-white rounded-2xl border border-stone-200/80 shadow-sm p-6">
            <h3 class="text-base font-extrabold text-stone-800 flex items-center gap-2 mb-1">
                <i class="fa-solid fa-shield-halved text-[#6F4E37]"></i> Acceso de Usuarios (MySQL & Supabase Auth)
            </h3>
            <p class="text-xs text-stone-500 mb-4">Inicia sesión o registra usuarios en la tabla `usuarios`</p>

            <?php if (!empty($_SESSION['user'])): ?>
                <div class="p-4 rounded-xl bg-stone-50 border border-stone-200 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-stone-400">Sesión activa (<?= htmlspecialchars($_SESSION['user']['modo'] ?? 'MySQL') ?>)</p>
                        <p class="font-bold text-stone-800 text-sm"><?= htmlspecialchars($_SESSION['user']['nombre']) ?></p>
                        <p class="text-xs text-stone-500"><?= htmlspecialchars($_SESSION['user']['email']) ?> · Rol: <strong class="uppercase"><?= htmlspecialchars($_SESSION['user']['rol']) ?></strong></p>
                    </div>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs">
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <form method="POST" action="index.php" class="space-y-3 p-4 rounded-xl bg-stone-50 border border-stone-200/70">
                        <input type="hidden" name="action" value="login">
                        <h4 class="font-bold text-stone-800 text-sm">Iniciar Sesión</h4>
                        <div>
                            <label class="block font-semibold text-stone-600 mb-1">Correo</label>
                            <input type="email" name="email" required value="admin@casadelbuencafe.com" class="w-full px-3 py-2 rounded-lg border border-stone-200 bg-white">
                        </div>
                        <div>
                            <label class="block font-semibold text-stone-600 mb-1">Contraseña</label>
                            <input type="password" name="password" required value="password" class="w-full px-3 py-2 rounded-lg border border-stone-200 bg-white">
                        </div>
                        <button type="submit" class="w-full py-2 rounded-lg bg-[#2C1810] text-white font-bold">Entrar</button>
                    </form>

                    <form method="POST" action="index.php" class="space-y-2.5 p-4 rounded-xl bg-stone-50 border border-stone-200/70">
                        <input type="hidden" name="action" value="register">
                        <h4 class="font-bold text-stone-800 text-sm">Registrar Usuario</h4>
                        <div>
                            <input type="text" name="nombre" required placeholder="Nombre completo" class="w-full px-3 py-1.5 rounded-lg border border-stone-200 bg-white">
                        </div>
                        <div>
                            <input type="email" name="email" required placeholder="correo@ejemplo.com" class="w-full px-3 py-1.5 rounded-lg border border-stone-200 bg-white">
                        </div>
                        <div class="grid grid-cols-2 gap-1.5">
                            <input type="password" name="password" required placeholder="Contraseña" class="w-full px-2.5 py-1.5 rounded-lg border border-stone-200 bg-white">
                            <select name="rol" class="w-full px-2 py-1.5 rounded-lg border border-stone-200 bg-white">
                                <option value="admin">Admin</option>
                                <option value="cajero">Cajero</option>
                                <option value="barista">Barista</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full py-2 rounded-lg bg-[#6F4E37] text-white font-bold">Crear Cuenta</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Columna Derecha (6 cols): Visor SQL (XAMPP MySQL y Supabase) -->
    <div class="lg:col-span-6">
        <div class="bg-stone-900 text-stone-100 rounded-2xl p-6 shadow-lg flex flex-col h-full">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-stone-700 mb-3">
                <div class="flex gap-2">
                    <button type="button" id="btnShowMysql" onclick="switchSqlViewer('mysql')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-stone-950">
                        <i class="fa-solid fa-database mr-1"></i> SQL para XAMPP MySQL
                    </button>
                    <button type="button" id="btnShowSupabase" onclick="switchSqlViewer('supabase')"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-stone-800 text-stone-300 hover:bg-stone-700">
                        <i class="fa-solid fa-bolt mr-1"></i> SQL para Supabase
                    </button>
                </div>
                <button type="button" onclick="copySqlSchema()" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-stone-950 font-bold text-xs flex items-center gap-1.5">
                    <i class="fa-regular fa-copy"></i> <span id="copyBtnText">Copiar SQL</span>
                </button>
            </div>
            <p id="sqlViewerSubtitle" class="text-[11px] text-stone-400 mb-2">
                Archivo: <code>database/mysql_schema.sql</code> — Se ejecuta automáticamente al conectar XAMPP MySQL o puedes importarlo en phpMyAdmin.
            </p>
            <textarea id="sqlSchemaTextarea" readonly class="w-full flex-1 min-h-[480px] bg-stone-950 text-amber-300 font-mono text-[11px] p-4 rounded-xl border border-stone-800 focus:outline-none"><?= htmlspecialchars($mysqlSchemaContent) ?></textarea>
        </div>
    </div>
</div>

<script>
const mysqlSql = <?= json_encode($mysqlSchemaContent) ?>;
const supabaseSql = <?= json_encode($supabaseSqlContent) ?>;

function switchSqlViewer(mode) {
    const ta = document.getElementById('sqlSchemaTextarea');
    const sub = document.getElementById('sqlViewerSubtitle');
    const bMysql = document.getElementById('btnShowMysql');
    const bSupa = document.getElementById('btnShowSupabase');

    if (mode === 'mysql') {
        ta.value = mysqlSql;
        sub.innerHTML = 'Archivo: <code>database/mysql_schema.sql</code> — Se ejecuta automáticamente al conectar XAMPP MySQL o puedes importarlo en phpMyAdmin.';
        bMysql.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-stone-950';
        bSupa.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-stone-800 text-stone-300 hover:bg-stone-700';
    } else {
        ta.value = supabaseSql;
        sub.innerHTML = 'Archivo: <code>supabase/schema.sql</code> — Pega este script en el SQL Editor de tu proyecto en Supabase.';
        bSupa.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-500 text-stone-950';
        bMysql.className = 'px-3 py-1.5 rounded-xl text-xs font-bold bg-stone-800 text-stone-300 hover:bg-stone-700';
    }
}

function copySqlSchema() {
    const el = document.getElementById('sqlSchemaTextarea');
    el.select();
    navigator.clipboard.writeText(el.value);
    const btn = document.getElementById('copyBtnText');
    btn.textContent = '¡Copiado!';
    setTimeout(() => { btn.textContent = 'Copiar SQL'; }, 2000);
}
</script>
