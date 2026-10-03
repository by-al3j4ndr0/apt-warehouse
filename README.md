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
  - Consulta de envíos almacenados.
  - Registro de entradas y salidas.
  - Consulta de movimientos y detalles.
- 🚚 **Gestión de entregas**
  - Administración de rutas.
  - Gestión de entregas.
  - Administración de choferes y vehículos.
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
