# ☕ La Casa del Buen Café — App Multi-Dispositivo & Monitoreo en XAMPP

El sistema está configurado para ejecutarse en **XAMPP (Apache + MySQL)** en tu computador y poder **abrirse desde cualquier celular, tablet u otro equipo** conectado a la red, permitiéndote **monitorear en tiempo real** cada compra, comanda o cambio de inventario directamente desde tu PC.

---

## 📱 Cómo abrir la página desde CUALQUIER Celular o Tablet

Tu computador tiene asignada en tu red Wi-Fi la dirección IP: **`10.3.235.77`**

### 1. Desde tu red Wi-Fi local (Cualquier celular o tablet):
1. Asegúrate de que el celular esté conectado a la **misma red Wi-Fi** que este computador.
2. Abre el navegador (Chrome, Safari, etc.) o la cámara del celular y escanea el código QR desde el botón **"Conectar Celulares / QR"** de la página.
3. O escribe directamente en el navegador del celular:
   * 🛍️ **Para que los clientes compren**:  
     👉 **`http://10.3.235.77/La_casa_del_buen_cafe/`**
   * 👨‍🍳 **Para el barista o cajero (Terminal POS y Comandas)**:  
     👉 **`http://10.3.235.77/La_casa_del_buen_cafe/admin.php`**

---

## 🖥️ Cómo Monitorear en Tiempo Real desde tu PC con XAMPP

1. Abre **XAMPP Control Panel** e inicia:
   * **Apache** (debe quedar en verde).
   * **MySQL** (debe quedar en verde).
2. En tu PC abre el **Portal de Administración**:
   👉 **`http://localhost/La_casa_del_buen_cafe/admin.php`**
3. **Monitoreo Automático Activo**:
   * Cuando un cliente realice una compra desde su celular, tu computador **hará sonar una campana audible (Chime 🔔)** y mostrará una alerta emergente instantánea con el folio, cliente y total pagado.
   * La tabla de **Comandas** y el **Dashboard** se actualizan automáticamente en tiempo real (mediante sondeo en segundo plano a `api_live_status.php`).
4. **Monitoreo desde phpMyAdmin**:
   * Puedes ver todas las tablas (`pedidos`, `detalle_pedidos`, `productos`, `clientes`) en vivo en:
     👉 **`http://localhost/phpmyadmin/index.php?route=/database/structure&db=la_casa_del_buen_cafe`**

---

## 🛡️ Si el celular no carga la página (Permiso en Firewall de Windows)

Si al ingresar desde el celular dice *"No se puede acceder a este sitio"* o se queda cargando, Windows Defender Firewall está bloqueando las conexiones entrantes al puerto 80 de Apache.

### Solución en 1 minuto:
1. En Windows presiona la tecla `Windows` y escribe **Permitir una aplicación a través de Firewall de Windows**.
2. Haz clic en **Cambiar la configuración** (arriba a la derecha).
3. Busca en la lista **Apache HTTP Server** (o haz clic en *Permitir otra aplicación...* y selecciona `C:\xampp\apache\bin\httpd.exe`).
4. Marca las dos casillas: **Privada** y **Pública**.
5. Haz clic en **Aceptar**. ¡Listo! El celular ya podrá entrar.

---

## 🌐 ¿Quieres que personas fuera de tu Wi-Fi (con datos móviles 4G/5G) puedan comprar?

Si deseas que clientes desde cualquier parte de la ciudad compren desde su celular sin estar en tu Wi-Fi, puedes crear un enlace público seguro gratuito ejecutando en PowerShell:

```powershell
npx localtunnel --port 80
```

Te generará un enlace público en internet (ejemplo: `https://buen-cafe-demo.loca.lt`) que abrirá tu tienda alojada en tu XAMPP local desde cualquier parte del mundo.
