# ☕ La Casa del Buen Café — App Integral PHP + XAMPP MySQL & Supabase

Sistema web completo desarrollado en **PHP** conectado directamente con **XAMPP MySQL (`la_casa_del_buen_cafe` vía PDO)** y compatible con **Supabase (PostgreSQL + REST API + Auth)**.

---

## 🗄️ Conexión Automática con XAMPP MySQL

1. Abre el **XAMPP Control Panel** e inicia los módulos:
   - **Apache**
   - **MySQL**
2. Abre en tu navegador:
   👉 **`http://localhost/La_casa_del_buen_cafe/`**
3. **¡Creación automática!** Al cargar la página por primera vez con MySQL encendido, el sistema ejecuta automáticamente [`database/mysql_schema.sql`](database/mysql_schema.sql) a través de [`config/conexion_mysql.php`](config/conexion_mysql.php), creando la base de datos **`la_casa_del_buen_cafe`** y sus 7 tablas con todos los productos iniciales.
4. Puedes visualizar tus tablas y registros en vivo desde **phpMyAdmin**:
   👉 **`http://localhost/phpmyadmin/index.php?route=/database/structure&db=la_casa_del_buen_cafe`**

---

## ☁️ Cómo conectar también con Supabase (Opcional)

1. En la pestaña **MySQL & Supabase** dentro de la aplicación web, puedes cambiar al visor **SQL para Supabase** ([`supabase/schema.sql`](supabase/schema.sql)) o configurar tu **Project URL** y **API Key**.
2. Cuando ambos motores están activos, cada venta, producto o cliente registrado en **XAMPP MySQL** se replica también hacia **Supabase**.

---

## 📦 Módulos Incluidos

- **📊 Dashboard Administrativo**: KPIs de ingresos, pedidos activos, alertas de reabastecimiento y acceso en 1 clic a **phpMyAdmin**.
- **🛒 Tienda en Línea / Catálogo de Café**: Filtro por categorías, buscador en vivo, origen y nivel de tueste, carrito de compras y checkout web.
- **📠 Punto de Venta (POS)**: Terminal rápida para cajeros/baristas, búsqueda por código SKU, calculadora de cambio en efectivo y ticket imprimible.
- **🔔 Centro de Pedidos**: Tablero en tiempo real para gestionar órdenes de tienda online y barra POS (`pendiente`, `preparando`, `completado`, `cancelado`).
- **📦 Gestión de Inventario CRUD & Kardex**: Creación, edición y eliminación de productos y categorías en MySQL, alertas de stock mínimo y registro de entradas/salidas en `movimientos_inventario`.
- **👥 Clientes & Fidelización**: Directorio CRUD de clientes con acumulación automática de puntos.
