# DOCUMENTACIÓN OFICIAL DE LA API RESTful
## Ecosistema Integral de Servicios Web, POS, KDS & ERP
### **La Casa del Buen Café — Neiva, Huila**
**Versión de la API: 2.5.0-dev | Noviembre 2026**

---

| Metadato de la API | Especificación Oficial |
| :--- | :--- |
| **Nombre del Servicio** | API RESTful Omnicanal "La Casa del Buen Café" |
| **Base URL (Local)** | `http://localhost/La_casa_del_buen_cafe/api` |
| **Portal Interactivo Docs**| `http://localhost/La_casa_del_buen_cafe/api/docs.php` |
| **Formato de Carga Útil** | `application/json; charset=UTF-8` |
| **Esquema de Autenticación**| `Bearer Token (JWT firmado criptográficamente con HMAC SHA-256)` |
| **Control de Acceso (RBAC)**| Roles: `admin`, `cajero`, `barista`, `cliente`, `público` |
| **Sede Física** | Calle 26 # 16-24 Centro Comercial San Juan Plaza Local 151 Primer Piso, Neiva-Huila |
| **Motor de Persistencia** | XAMPP MariaDB/MySQL 10.4+ con fallback transparente a Almacén Local JSON |

---

## 1. Arquitectura y Estándar de Comunicación

La API opera bajo el estándar arquitectónico **RESTful**, exponiendo interfaces limpias, autocontenidas y sin estado (*stateless*). Soporta métodos HTTP estándar (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`) y devuelve códigos de estado HTTP semánticos (200, 201, 204, 400, 401, 403, 404, 409, 422, 500).

### 1.1. Estructura Estándar de Respuesta JSON

Todas las respuestas emitidas por la API siguen una estructura homogénea y predecible:

#### Respuesta Exitosa (200 OK / 201 Created):
```json
{
  "ok": true,
  "status": 200,
  "message": "Operación ejecutada con éxito.",
  "timestamp": "2026-11-21 14:00:00",
  "data": { ... },
  "meta": {
    "total": 16,
    "page": 1,
    "limit": 50
  }
}
```

#### Respuesta de Error (4xx / 5xx):
```json
{
  "ok": false,
  "status": 401,
  "message": "Acceso no autorizado. Se requiere Token Bearer válido.",
  "timestamp": "2026-11-21 14:00:00",
  "data": {
    "code": "UNAUTHORIZED",
    "hint": "Inicia sesión enviando POST a /api/auth/login"
  }
}
```

---

## 2. Autenticación y Autorización por Roles (RBAC)

### 2.1. Formato del Token Bearer
Al autenticarse en `/api/auth/login`, el sistema emite un token firmado:
```http
Authorization: Bearer CBC_TOKEN.<base64UrlHeader>.<base64UrlPayload>.<base64UrlSignature>
```
El token tiene una vigencia predeterminada de **7 días**, incluye la firma criptográfica del servidor y contiene la identidad del usuario, correo y rol asignado.

### 2.2. Matriz de Autorización por Roles

| Módulo / Recurso | Público / Invitado | Cliente (`cliente`) | Cajero (`cajero`) | Barista (`barista`) | Administrador (`admin`) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Explorar Catálogo de Productos** |  Lectura |  Lectura |  Lectura |  Lectura |  Lectura y Gestión |
| **Crear / Editar Productos (CRUD)**| ❌ Denegado | ❌ Denegado | ❌ Denegado | ❌ Denegado |  Total (CRUD) |
| **Crear Pedido / Venta** |  Web Checkout |  Web Checkout |  POS Mostrador | ❌ Denegado |  POS Mostrador |
| **Consultar Comandas KDS** | ❌ Por Folio |  Propias |  Todas |  Todas (Barra) |  Todas |
| **Actualizar Estado KDS** | ❌ Denegado | ❌ Denegado |  Permitido |  Permitido |  Permitido |
| **Cancelar Pedidos / Restock** | ❌ Denegado | ❌ Denegado |  Permitido | ❌ Denegado |  Permitido |
| **Consultar Puntos de Fidelización**| ❌ Denegado |  Propios |  Cualquier Socio| ❌ Denegado |  Todos |
| **Ajustar Inventario & Kardex** | ❌ Denegado | ❌ Denegado | ❌ Denegado | ❌ Denegado |  Total (Kardex) |
| **Dashboard & Analítica Financiera**| ❌ Denegado | ❌ Denegado | ❌ Denegado | ❌ Denegado |  Total (KPIs) |

---

## 3. Catálogo Completo de Endpoints

### 3.1. Módulo: Autenticación & Perfil (`/api/auth`)

#### `POST /api/auth/login`
- **Descripción**: Autentica credenciales y genera un Bearer Token.
- **Acceso**: Público.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "email": "admin@casadelbuencafe.com",
    "password": "",
    "rol": "admin"
  }
  ```
- **Respuesta (200 OK)**:
  ```json
  {
    "ok": true,
    "status": 200,
    "message": "Autenticación exitosa. Token generado correctamente.",
    "data": {
      "token": "CBC_TOKEN.eyJ0eXAi...",
      "token_type": "Bearer",
      "expires_in": 604800,
      "user": {
        "id": 1,
        "email": "admin@casadelbuencafe.com",
        "nombre": "Administrador Principal",
        "rol": "admin"
      }
    }
  }
  ```

#### `POST /api/auth/register`
- **Descripción**: Registra nuevos clientes en el Club de Fidelización con 50 puntos de bienvenida o nuevo personal (requiere token de Admin).
- **Acceso**: Público (clientes) / Admin (personal).
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "nombre": "Estefanía Olaya",
    "email": "estefania@ejemplo.com",
    "telefono": "3105551234",
    "direccion": "Centro Comercial San Juan Plaza Local 151, Neiva",
    "rol": "cliente"
  }
  ```
- **Respuesta (201 Created)**: Retorna el cliente registrado con su token Bearer y saldo inicial de 50 puntos.

#### `GET /api/auth/me`
- **Descripción**: Obtiene la identidad, rol y permisos del usuario autenticado.
- **Cabecera requerida**: `Authorization: Bearer <token>`.
- **Respuesta (200 OK)**:
  ```json
  {
    "ok": true,
    "data": {
      "user": {
        "id": 1,
        "nombre": "Administrador Principal",
        "email": "admin@casadelbuencafe.com",
        "rol": "admin"
      },
      "permisos": {
        "pos": true,
        "kds": true,
        "inventario": true,
        "dashboard": true
      }
    }
  }
  ```

---

### 3.2. Módulo: Catálogo y Productos CRUD (`/api/productos`)

#### `GET /api/productos`
- **Descripción**: Lista de productos con filtros, paginación e información de stock crítico.
- **Acceso**: Público.
- **Parámetros de Consulta**:
  - `categoria_id` *(int, opcional)*: Filtra por categoría.
  - `q` o `search` *(string, opcional)*: Búsqueda textual por nombre, código o descripción.
  - `destacado` *(0 o 1, opcional)*: Filtra por productos destacados.
  - `activo` *(0 o 1, default: 1)*: Filtra por estado activo.
  - `limit` *(int, default: 50)*: Cantidad de ítems por página.
  - `page` *(int, default: 1)*: Número de página.
- **Respuesta (200 OK)**:
  ```json
  {
    "ok": true,
    "data": [
      {
        "id": 10,
        "categoria_id": 9,
        "categoria_nombre": "Bebidas",
        "codigo": "BEB-001",
        "nombre": "Cappuccino Artesanal de Origen 8oz",
        "descripcion": "Doble shot de espresso con leche texturizada.",
        "origen": "Huila, Colombia",
        "tueste": "Medio",
        "precio": 8500,
        "costo": 2800,
        "stock": 120,
        "stock_minimo": 15,
        "alerta_stock": false,
        "imagen_url": "https://images.unsplash.com/photo-1572442388796-11668a67e53d...",
        "destacado": 1,
        "activo": 1
      }
    ],
    "meta": {
      "total": 16,
      "page": 1,
      "limit": 50,
      "total_pages": 1
    }
  }
  ```

#### `GET /api/productos/{id}`
- **Descripción**: Detalle de un producto por su ID numérico o código SKU (`TOR-001`, `BEB-001`).
- **Acceso**: Público.

#### `POST /api/productos`
- **Descripción**: Crear un nuevo producto en el catálogo. Registra inventario inicial en Kardex si stock > 0.
- **Acceso**: Requiere rol `admin`.
- **Cuerpo de Solicitud (JSON)**:
  ```json
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
  ```
- **Respuesta (201 Created)**: Producto creado con ID asignado.

#### `PATCH /api/productos/{id}` o `PUT /api/productos/{id}`
- **Descripción**: Actualización total o parcial de producto. Si se altera el stock manualmente, genera un asiento de ajuste en el Kardex.
- **Acceso**: Requiere rol `admin`.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "precio": 10000,
    "stock": 45,
    "motivo_ajuste": "Ingreso por nueva compra a proveedor"
  }
  ```

#### `DELETE /api/productos/{id}`
- **Descripción**: Elimina un producto del catálogo.
- **Acceso**: Requiere rol `admin`.
- **Respuesta (200 OK)**: Confirmación de eliminación.

---

### 3.3. Módulo: Pedidos, Comandas & POS (`/api/pedidos`)

#### `GET /api/pedidos`
- **Descripción**: Consulta órdenes con carga combinada de ítems (Eager Loading para prevenir consultas N+1).
- **Acceso**: Staff (`admin`, `cajero`, `barista`) o Cliente (filtrado a pedidos propios).
- **Parámetros de Consulta**:
  - `estado`: `pendiente`, `preparando`, `completado`, `cancelado`.
  - `tipo`: `pos` o `tienda_online`.
  - `folio`: búsqueda por folio alfanumérico.

#### `GET /api/pedidos/{id}`
- **Descripción**: Detalle completo de una orden por ID numérico o Folio (`ORD-XXXX`, `POS-XXXX`, `WEB-XXXX`). Permite rastreo público a clientes si conocen su folio.

#### `POST /api/pedidos`
- **Descripción**: Creación atómica de pedido:
  1. Valida existencias en stock.
  2. Descuenta automáticamente las unidades de `productos`.
  3. Registra el movimiento de salida en `movimientos_inventario` (Kardex).
  4. Asigna puntos de fidelización al socio (1 punto por cada \$1.000 COP).
  5. Genera comanda en cola KDS.
- **Acceso**: Público / Cliente / Cajero / Admin.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "tipo": "tienda_online",
    "metodo_pago": "efectivo",
    "cliente_nombre": "Estefanía Olaya",
    "cliente_email": "estefania@ejemplo.com",
    "cliente_telefono": "3105551234",
    "direccion_entrega": "San Juan Plaza Local 151",
    "notas": "Por favor con leche deslactosada",
    "items": [
      { "producto_id": 10, "cantidad": 2 },
      { "producto_id": 1, "cantidad": 1 }
    ]
  }
  ```
- **Respuesta (201 Created)**:
  ```json
  {
    "ok": true,
    "status": 201,
    "message": "¡Pedido procesado con éxito!",
    "data": {
      "id": 15,
      "folio": "WEB-8A4B2C",
      "total": 28500,
      "estado": "pendiente",
      "puntos_ganados": 28,
      "items": [ ... ]
    }
  }
  ```

#### `PATCH /api/pedidos/{id}/estado`
- **Descripción**: Actualiza el estado de la comanda en cocina y barra.
- **Acceso**: Requiere rol `barista`, `cajero` o `admin`.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "estado": "preparando"
  }
  ```
  *(Valores válidos: `pendiente`, `preparando`, `completado`, `cancelado`)*.

#### `DELETE /api/pedidos/{id}`
- **Descripción**: Cancela el pedido y restituye automáticamente las unidades al stock en inventario y Kardex.
- **Acceso**: Requiere rol `cajero` o `admin`.

---

### 3.4. Módulo: Clientes & Club de Fidelización (`/api/clientes`)

#### `GET /api/clientes`
- **Descripción**: Lista de socios registrados y saldo de puntos acumulados.
- **Acceso**: Requiere rol `cajero` o `admin`.

#### `GET /api/clientes/{id}/puntos`
- **Descripción**: Consulta el balance de fidelización, categoría de socio (`Bronce`, `Plata`, `Oro Diamante`) y recompensas redimibles.
- **Acceso**: Cliente propio o Staff.
- **Respuesta (200 OK)**:
  ```json
  {
    "ok": true,
    "data": {
      "cliente_id": 2,
      "nombre": "Estefanía Olaya",
      "puntos": 150,
      "nivel": {
        "nombre": "Plata Especial",
        "beneficio": "10% de descuento adicional"
      },
      "recompensas": [
        {
          "id": "rec-cafe",
          "nombre": "Café de Origen 8oz Gratis",
          "puntos_requeridos": 100,
          "disponible": true
        },
        {
          "id": "rec-torta",
          "nombre": "Porción de Torta Artesanal o Empanada Gratis",
          "puntos_requeridos": 150,
          "disponible": true
        }
      ]
    }
  }
  ```

#### `POST /api/clientes/{id}/canjear`
- **Descripción**: Redime puntos por un beneficio de cortesía.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "puntos": 100,
    "recompensa": "Café de Origen 8oz Gratis"
  }
  ```

---

### 3.5. Módulo: Inventario & Kardex (`/api/inventario`)

#### `GET /api/inventario`
- **Descripción**: Auditoría de existencias físicas, valuación monetaria del inventario a costo y venta en COP, y lista de productos en stock crítico.
- **Acceso**: Requiere rol `admin`.
- **Respuesta (200 OK)**:
  ```json
  {
    "ok": true,
    "data": {
      "resumen": {
        "total_productos": 16,
        "total_unidades": 458,
        "productos_criticos": 2,
        "valuacion_costo_cop": 3280000,
        "valuacion_venta_cop": 8150000,
        "margen_potencial": 59.8
      },
      "alertas_stock_critico": [
        {
          "id": 9,
          "codigo": "PLA-003",
          "nombre": "Platanitos Verdes Sabor Natural 40g",
          "stock": 5,
          "stock_minimo": 2
        }
      ]
    }
  }
  ```

#### `GET /api/inventario/kardex`
- **Descripción**: Historial completo de movimientos de inventario (`entrada`, `salida`, `ajuste`, `venta`) con trazabilidad cronológica.
- **Acceso**: Requiere rol `admin`.

#### `POST /api/inventario/movimientos`
- **Descripción**: Asienta un movimiento manual en bodega (compra a proveedor, merma o ajuste).
- **Acceso**: Requiere rol `admin`.
- **Cuerpo de Solicitud (JSON)**:
  ```json
  {
    "producto_id": 7,
    "tipo": "entrada",
    "cantidad": 50,
    "motivo": "Compra a Distribuidora del Huila - Factura 8841"
  }
  ```

---

### 3.6. Módulo: Métricas & KPIs en Tiempo Real (`/api/stats`)

#### `GET /api/stats/dashboard`
- **Descripción**: Resumen financiero consolidado con ventas históricas, ventas del día, ventas del mes, ticket promedio, ventas por canal y Top 5 productos.
- **Acceso**: Requiere rol `admin`.

#### `GET /api/stats/kds`
- **Descripción**: Estado en vivo de la cola de preparación en barra y cocina para pantallas táctiles de baristas.
- **Acceso**: Requiere rol `barista`, `cajero` o `admin`.

---

## 4. Códigos de Estado HTTP Utilizados

| Código HTTP | Significado Semántico | Caso de Aplicación |
| :---: | :--- | :--- |
| **`200 OK`** | Operación Exitosa | Consultas `GET`, actualizaciones `PUT`/`PATCH` y borrados `DELETE`. |
| **`201 Created`** | Recurso Creado | Creación de productos, pedidos, movimientos de inventario y clientes. |
| **`204 No Content`** | Sin Contenido | Respuesta inmediata a solicitudes preflight de navegador `OPTIONS`. |
| **`400 Bad Request`** | Petición Malformada | Parámetros obligatorios ausentes en la URL o formato inválido. |
| **`401 Unauthorized`** | No Autenticado | Ausencia de token Bearer o firma criptográfica inválida. |
| **`403 Forbidden`** | Prohibido / Denegado | Token válido pero rol con privilegios insuficientes para la acción. |
| **`404 Not Found`** | No Encontrado | Recurso, producto o pedido inexistente en la base de datos. |
| **`409 Conflict`** | Conflicto de Negocio | Código SKU duplicado, correo ya registrado o stock insuficiente. |
| **`422 Unprocessable`** | Validación de Negocio | Precios negativos, cantidades en cero o email con sintaxis inválida. |
| **`500 Internal Error`** | Error de Servidor | Incidencia no recuperable en base de datos. |

---

## 5. Portal Interactivo de Pruebas (Swagger / Postman Web)

El sistema incluye una interfaz visual interactiva para desarrolladores y operadores ubicada en:
**`http://localhost/La_casa_del_buen_cafe/api/docs.php`**

### Características del Portal:
- **1-Clic Token Generator**: Genera al instante tokens válidos con rol de **Admin**, **Cajero**, **Barista** o **Cliente** para probar de inmediato cualquier endpoint.
- **Consola Interactiva**: Envía solicitudes reales y visualiza en tiempo real el código de estado HTTP, tiempo de respuesta en milisegundos y el JSON devuelto con resaltado sintáctico.
- **Modo Sandbox**: Permite modificar payloads JSON de prueba antes de enviarlos.

---
**La Casa del Buen Café — Ingeniería de Software © 2026**
