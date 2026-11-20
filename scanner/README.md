# Control QR de mercancías (PWA)

Módulo instalable en Android para verificar manifiestos existentes en apt-warehouse o listados CSV mediante escáner Bluetooth en modo teclado (HID).

## Instalación
1. Publicar el repositorio en un servidor HTTPS.
2. Ejecutar en apt_warehouse las migraciones `20261120_add_at_customs_shipment_status.sql`, `20261120_create_scanner_events.sql` y `20261120_expand_scanner_manifest_name.sql` (en ese orden).
3. Iniciar sesión en apt-warehouse con un usuario autorizado como personal (is_staff = 1).
4. Abrir /scanner/ desde Chrome en Android y seleccionar Instalar aplicación o Añadir a pantalla de inicio.
5. Seleccionar un manifiesto existente o, si se necesita, escribir un nombre de sesión e importar un CSV; después conectar el lector Bluetooth.

## CSV
Se admiten delimitadores coma o punto y coma, encabezados con o sin acentos. El código debe estar en una columna llamada codigo, codigo del envio o hbl. Las columnas opcionales son destinatario, municipio y provincia.

## Operación
- La lectura se procesa al recibir Enter del escáner.
- El listado y los escaneos se conservan en IndexedDB del dispositivo.
- El servidor es la autoridad: solo acepta como entrada los envíos cuyo estado actual sea `at_customs` y los cambia a `warehouse`.
- Si el envío ya está en `warehouse`, se registra como duplicado. Los bloqueos transaccionales evitan que dos operadores registren la misma entrada simultáneamente.
- La aplicación web `warehouse/inOutShipments.php` y la PWA usan el mismo endpoint y comparten el estado real de `shipments`.
- Los códigos desconocidos se registran como NOT_FOUND.
- La exportación CSV se genera localmente.
- La sincronización requiere conexión, sesión activa y token CSRF válido.

## Seguridad y limitaciones
- La API requiere sesión autenticada, rol de personal y CSRF.
- La sincronización registra el resultado validado por el servidor y el usuario que lo envió.
- El CSV local sirve para preparar la sesión y mostrar datos al operador; no autoriza por sí mismo una entrada. La base de datos decide si el HBL existe y si está en `at_customs`. En los manifiestos seleccionados desde el sistema también comprueba que el HBL pertenezca a ese manifiesto.
- El modo de salida para transferencias entre almacenes queda pendiente. La salida a distribución sigue siendo responsabilidad del módulo `Deliveries`.
- El CSV se procesa localmente; el servidor no recibe el archivo maestro.
- El almacenamiento local pertenece al navegador/dispositivo; borrar sus datos elimina la copia local.


## Correcciones de operación y panel web
- El campo de escaneo procesa el código automáticamente al recibir Enter o cuando termina la ráfaga de escritura HID (400 ms sin nuevas teclas). El módulo web de entradas usa el mismo comportamiento.
- La PWA incorpora identidad de instalación (nombre, ámbito y URL de inicio dedicados), icono SVG y botón de instalación cuando Chrome emite `beforeinstallprompt`.
- Para instalar en Chrome, el sitio debe servirse por HTTPS (o localhost en desarrollo). En Android, abre `/scanner/` en Chrome y usa el menú `Instalar aplicación` o el botón de instalación que aparece cuando la PWA es elegible. Si no aparece, revisar Chrome DevTools > Application > Manifest > Installability y verificar que el icono sea accesible.
- La tabla de pendientes en `warehouse/inOutShipments.php` usa paginación de 25 filas y búsqueda por HBL, manifiesto, destinatario, municipio y provincia. El resumen muestra los pendientes globales y las entradas aceptadas en los últimos 7 días.
- La consulta de pendientes acepta `page`, `per_page` y `search`; el resumen semanal cuenta eventos `FOUND` en `scanner_events` usando UTC.
