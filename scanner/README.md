# Control QR de mercancías (PWA)

Módulo instalable en Android para verificar listados CSV mediante escáner Bluetooth en modo teclado (HID).

## Instalación
1. Publicar el repositorio en un servidor HTTPS.
2. Ejecutar el archivo database/migrations/20261120_create_scanner_events.sql en la base de datos apt_warehouse.
3. Iniciar sesión en apt-warehouse con un usuario autorizado como personal (is_staff = 1).
4. Abrir /scanner/ desde Chrome en Android y seleccionar Instalar aplicación o Añadir a pantalla de inicio.
5. Escribir un nombre de sesión, importar el CSV y conectar el lector Bluetooth.

## CSV
Se admiten delimitadores coma o punto y coma, encabezados con o sin acentos. El código debe estar en una columna llamada codigo, codigo del envio o hbl. Las columnas opcionales son destinatario, municipio y provincia.

## Operación
- La lectura se procesa al recibir Enter del escáner.
- El listado y los escaneos se conservan en IndexedDB del dispositivo.
- Un código válido escaneado anteriormente en el mismo manifiesto se marca como duplicado.
- Los códigos desconocidos se registran como NOT_FOUND.
- La exportación CSV se genera localmente.
- La sincronización requiere conexión, sesión activa y token CSRF válido.

## Seguridad y limitaciones
- La API requiere sesión autenticada, rol de personal y CSRF.
- La sincronización registra eventos y el usuario que los envió.
- El CSV se procesa localmente; el servidor no recibe el archivo maestro.
- En esta primera versión el resultado de validación se calcula en el dispositivo. La siguiente iteración debe centralizar manifiestos y deduplicación en servidor para operaciones simultáneas con varios teléfonos.
- El almacenamiento local pertenece al navegador/dispositivo; borrar sus datos elimina la copia local.
