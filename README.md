# APT Warehouse

Sistema web de gestión de almacén y logística desarrollado en PHP. La aplicación centraliza la consulta y administración de clientes y envíos, el control de mercancía en almacén, la gestión de rutas de entrega y la carga de manifiestos.

## Características

- 🔐 **Autenticación y control de acceso**
  - Inicio y cierre de sesión.
  - Sesiones con cookies seguras, `HttpOnly` y `SameSite`.
  - Control de acceso para usuarios y personal autorizado.
  - Protección CSRF para operaciones que modifican datos.
  - Expiración de sesión por inactividad.
- 🔎 **Búsqueda y consulta**
  - Consulta de clientes.
  - Consulta y detalle de envíos.
  - Edición de información de envíos y clientes.
- 📦 **Gestión de almacén**
  - Control de entradas mediante escáner QR.
  - Consulta de manifiestos del sistema y carga alternativa mediante CSV.
  - Registro de escaneos online y almacenamiento local para sincronización posterior.
  - Consulta de envíos almacenados.
  - Registro de entradas y salidas.
  - Consulta de movimientos y detalles.
- 🚚 **Gestión de entregas**
  - Administración de rutas.
  - Gestión de entregas.
  - Administración de choferes y vehículos.
- 📱 **Control QR (PWA)**
  - Aplicación instalable para dispositivos móviles mediante Chrome cuando se cumplen los requisitos de instalación.
  - Lector Bluetooth HID en modo teclado: procesa con Enter o tras 400 ms sin nuevas teclas.
  - Consulta y selección de manifiestos existentes en apt-warehouse.
  - Escaneo sin conexión con almacenamiento local IndexedDB y sincronización posterior.
  - Validación definitiva en el servidor: el estado del envío en la base de datos es la fuente de verdad.
  - Para envíos del origen **6**, normaliza códigos de 12 caracteres con formato `ALB` + 9 dígitos: elimina los cuatro dígitos posteriores a `ALB` y conserva los últimos cinco. Ejemplo: `ALB123456789` → `ALB56789`. La regla solo se aplica a registros del origen 6; los demás orígenes y las cargas CSV no se transforman.
- 📄 **Manifiestos y documentos**
  - Carga de manifiestos mediante archivo.
  - Plantilla CSV incluida en `resources/files/upload_template.csv`.
  - Generación/exportación de documentos PDF.
- 🛡️ **Controles de seguridad**
  - Cabeceras HTTP de seguridad.
  - Uso de `utf8mb4` en la conexión MySQL.
  - Variables de entorno para la configuración de la base de datos.
  - Validación automática de sintaxis PHP mediante GitHub Actions.

## Arquitectura

El proyecto utiliza una arquitectura PHP tradicional organizada por funcionalidades:

```text
apt-warehouse/
├── api/                    # Autenticación, acceso a BD y endpoints de aplicación
├── delivery/               # Gestión de rutas y entregas
├── pdf/                    # Exportación de documentos PDF
├── resources/              # CSS, JavaScript, imágenes, fuentes y archivos auxiliares
├── search/                 # Búsqueda y consulta de clientes y envíos
├── scanner/                # PWA de Control QR, lector HID y sincronización offline
├── settings/               # Configuración, manifiestos, choferes y vehículos
├── visitors/               # Gestión relacionada con visitantes
├── warehouse/              # Operaciones y consultas del almacén
├── logs/                   # Directorio protegido para registros
├── uploads/                # Directorio protegido para archivos cargados
├── header.php              # Navegación principal
├── index.php               # Página principal autenticada
├── login.php               # Inicio de sesión
├── logout.php              # Cierre de sesión
├── .env.example            # Plantilla de configuración
└── .github/workflows/      # Automatización de comprobaciones
```

## Requisitos

- PHP **8.2** o compatible.
- Extensión PHP **mysqli**.
- MySQL/MariaDB.
- Servidor web compatible con PHP (por ejemplo, Apache).
- HTTPS recomendado, especialmente cuando `SESSION_SECURE_COOKIE=true`.

## Configuración

1. Clona el repositorio:

```bash
git clone https://github.com/by-al3j4ndr0/apt-warehouse.git
cd apt-warehouse
```

2. Configura las variables de entorno tomando como referencia `.env.example`:

```env
DB_HOST=localhost
DB_NAME=apt_warehouse
DB_USER=your_database_user
DB_PASSWORD=your_database_password
SESSION_SECURE_COOKIE=true
```

3. Crea la base de datos `apt_warehouse` y configura el usuario con los permisos necesarios.

4. Configura el servidor web para que el directorio del proyecto sea el document root o esté disponible mediante un Virtual Host.

5. Verifica que PHP pueda acceder a MySQL y que los directorios utilizados para cargas y registros tengan los permisos apropiados.

> **Importante:** no almacenes credenciales reales en el repositorio. Usa variables de entorno o el mecanismo de configuración seguro proporcionado por tu entorno de despliegue.

## Ejecución local

Con PHP instalado, puedes utilizar el servidor integrado para una prueba local:

```bash
php -S localhost:8000
```

Después abre:

```text
http://localhost:8000/login.php
```

Para un entorno real, utiliza un servidor web configurado con HTTPS y una configuración de producción adecuada.


## Control QR y PWA

El módulo está disponible en `/scanner/` para usuarios autenticados. Permite seleccionar un manifiesto existente desde la base de datos o importar un archivo CSV.

### Uso del lector

- Conecta el lector Bluetooth configurado como teclado HID.
- Selecciona y carga el manifiesto que vas a procesar.
- El escáner acepta códigos terminados en Enter. Si el lector no envía Enter, el sistema detecta el fin de la lectura tras 400 ms de inactividad.
- Los registros se guardan localmente en IndexedDB y pueden sincronizarse cuando vuelve la conexión.
- La sincronización valida cada HBL en el servidor y actualiza a estado `warehouse` solo los envíos que estén en `at_customs`. Los duplicados y códigos no encontrados se informan sin repetir la transición de estado.

### Normalización específica del origen 6

Al cargar un manifiesto del sistema, la API incluye el campo `origen` de cada envío. Si un código escaneado no coincide directamente, el módulo puede aplicar la transformación exclusiva del origen `6`:

```text
Código leído:       ALB123456789
Código normalizado: ALB56789
```

La regla requiere el patrón `ALB` seguido de nueve dígitos. Conserva los tres caracteres iniciales y los últimos cinco caracteres del código original, eliminando los cuatro caracteres intermedios. La coincidencia exacta tiene prioridad. Los demás orígenes y los manifiestos CSV mantienen el código original.

Los manifiestos del sistema que ya estaban guardados localmente antes de que se añadiera el campo de origen deben volver a seleccionarse y cargarse para actualizar sus datos.

### Instalación y funcionamiento offline

- Sirve la aplicación mediante **HTTPS** para que Chrome pueda ofrecer la instalación de la PWA (excepto entornos locales de desarrollo).
- Abre `/scanner/` en Chrome. Si no aparece la opción de instalación, revisa **DevTools → Application → Manifest → Installability**.
- La primera carga del manifiesto requiere conexión y una sesión autenticada. Una vez cargado, los escaneos se almacenan localmente y esperan a que haya conexión para sincronizarse.
- La PWA utiliza un service worker para almacenar recursos de la interfaz; el funcionamiento offline no sustituye la autenticación ni la validación del servidor al sincronizar.

## Seguridad

La aplicación incluye varias medidas de protección, entre ellas:

- Sesiones basadas exclusivamente en cookies.
- Modo estricto de sesiones.
- Cookies `HttpOnly`, `SameSite=Lax` y opción `Secure`.
- Protección contra CSRF.
- Expiración de sesiones por inactividad.
- Comprobaciones de autenticación y autorización.
- Cabeceras como `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy`.
- Exclusión de `.env`, registros y archivos de configuración sensibles mediante `.gitignore`.
- Comprobación automática de sintaxis PHP en CI.

Antes de desplegar en producción, revisa también la configuración del servidor web, permisos de archivos, HTTPS, credenciales de base de datos y políticas de acceso.

## Comprobaciones automáticas

El repositorio incluye un workflow de GitHub Actions en:

```text
.github/workflows/php-security.yml
```

El workflow se ejecuta en cambios dirigidos a `main` y en pull requests hacia `main`, utilizando PHP 8.2 y la extensión `mysqli` para comprobar la sintaxis de los archivos PHP.

También puedes ejecutar una comprobación local:

```bash
find . -type f -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l
```

## Estructura funcional

| Módulo | Responsabilidad |
|---|---|
| `api/` | Autenticación, conexión a BD y operaciones de datos |
| `search/` | Búsqueda y consulta de clientes y envíos |
| `warehouse/` | Control de mercancía y movimientos del almacén |
| `delivery/` | Rutas y entregas |
| `settings/` | Manifiestos, choferes y vehículos |
| `pdf/` | Exportación de información a PDF |
| `visitors/` | Gestión de visitantes |
| `resources/` | Recursos de interfaz y archivos auxiliares |

## Flujo general

```text
Usuario
   │
   ▼
login.php
   │
   ▼
api/auth.php
   │
   ├── Autenticación
   ├── Sesión segura
   └── CSRF
   │
   ▼
Aplicación
   ├── Clientes / Envíos
   ├── Almacén
   ├── Rutas / Entregas
   ├── Manifiestos
   └── Configuración
   │
   ▼
MySQL / MariaDB
```

## Estado del proyecto

Este README documenta la estructura y funcionalidades identificadas en la rama `main`. La implementación y sus capacidades pueden evolucionar junto con el repositorio.

## Licencia

No se ha identificado un archivo de licencia en el repositorio. Antes de redistribuir o utilizar el proyecto fuera de su entorno previsto, define y agrega la licencia correspondiente.

## Contribución

1. Crea una rama para tu cambio.
2. Realiza las modificaciones necesarias.
3. Ejecuta las comprobaciones de sintaxis PHP.
4. Verifica que no se incluyan credenciales, archivos `.env`, logs ni datos sensibles.
5. Abre un pull request hacia `main` describiendo los cambios realizados.


## Perfiles de acceso

El sistema utiliza las columnas `is_staff` y `is_superuser` de `auth_user` para determinar tres perfiles:

| Perfil | is_staff | is_superuser | Acceso |
|---|---:|---:|---|
| Visitante | 0 | 0 | Consulta de envíos limitada al origen asociado al usuario |
| Operador de consulta | 1 | 0 | Consulta global de clientes, envíos, almacén y deliveries; uso de `inOutShipments` y del escáner. No puede modificar datos administrativos |
| Administrador | 1 | 1 | Acceso completo, incluidas las operaciones de modificación y configuración |

Las cuentas deben tener `is_active = 1` para iniciar sesión. La combinación `is_staff = 0` y `is_superuser = 1` no está permitida.

La autorización se aplica en el servidor mediante los helpers de `api/auth.php`: `requireLogin(true)` para páginas de personal, `requireAdmin()` / `requireApiAdmin()` para operaciones administrativas y `requireVisitor()` / `requireApiVisitor()` para la vista limitada por origen. Los escaneos y su sincronización permanecen disponibles para el personal autorizado, ya que forman parte del flujo de entrada a almacén.
