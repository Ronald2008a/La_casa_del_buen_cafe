# ☕ La Casa del Buen Café — Dos Sitios Web Conectados (XAMPP MySQL & Supabase)

El proyecto cuenta con **dos sitios web independientes** conectados en tiempo real a la misma base de datos **`la_casa_del_buen_cafe`** en **XAMPP MySQL**:

---

## 🛍️ Sitio Web #1: Tienda en Línea Exclusiva para Clientes (Solo Compras)
👉 **URL**: **`http://localhost/La_casa_del_buen_cafe/`** (o `index.php`)

Diseñada exclusivamente para los compradores:
- **Catálogo Boutique de Café de Especialidad**: Filtro por categorías, buscador por notas de cata/origen, medidor visual de tueste y ordenamiento por precio.
- **Bolsa de Compras Deslizante (Drawer) & Checkout**: Permite comprar sin ver ningún panel administrativo, acumulando puntos automáticamente con el correo electrónico.
- **Comprobante Digital e Imprimible**: Genera al instante el folio del pedido (`WEB-XXXX`).
- **Rastreador de Pedidos en Vivo**: El cliente puede ingresar su código `WEB-XXXX` para ver si el barista tiene su pedido `Pendiente`, `Preparando` o `Completado`.

---

## 🔐 Sitio Web #2: Portal Interno para Administrador y Trabajador (Barista / Cajero)
👉 **URL**: **`http://localhost/La_casa_del_buen_cafe/admin.php`**

Incluye pantalla de **Login Corporativo** con botones de acceso rápido según el rol:
1. **Perfil Trabajador / Cajero / Barista (`rol: cajero`)**:
   - **📠 Terminal POS**: Caja registradora táctil con miniaturas, billetes rápidos (`$10.000`, `$20.000`, `$50.000`, `$100.000`, `Exacto`) y ticket térmico con QR.
   - **🔔 Comandas**: Recibe al instante los pedidos que hacen los clientes desde la Tienda Pública y permite cambiar su estado (`Pendiente` → `Preparando` → `Completado`).
   - **👥 Club de Clientes**: Consulta y registro de clientes y sus puntos de fidelización.
2. **Perfil Administrador (`rol: admin`)**:
   - Acceso total a los módulos del trabajador **más**:
   - **📊 Panel General (Dashboard)**: KPIs financieros y gráficos interactivos con Chart.js.
   - **📦 Inventario CRUD & Kardex**: Creación, edición y eliminación de productos/categorías y ajustes de bodega.
   - **🗄️ Base de Datos (XAMPP MySQL & Supabase)**: Configuración de conexión y acceso directo a **phpMyAdmin**.
