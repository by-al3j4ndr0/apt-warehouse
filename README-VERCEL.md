# APT Warehouse — Vercel

La rama `vercel-nextjs-refactor` convierte la aplicación PHP original en una aplicación Next.js compatible con Vercel.

## Arquitectura

- Next.js App Router + React/TypeScript.
- Route Handlers para `/api/*`.
- MySQL mediante `mysql2` y `DATABASE_URL`.
- Sesión HTTP-only firmada con `AUTH_SECRET`.
- Sin `localhost`, archivos PHP ejecutables ni estado de sesión en disco.
- El pool de conexiones es reutilizable entre invocaciones cuando el runtime serverless conserva la instancia.

## Variables de entorno en Vercel

Configura:

- `DATABASE_URL`: URL completa de la base MySQL accesible desde Internet y con SSL según el proveedor.
- `AUTH_SECRET`: secreto aleatorio largo (mínimo 32 bytes recomendado).

No subas `.env.local` al repositorio.

## Despliegue

1. Importa `by-al3j4ndr0/apt-warehouse` en Vercel.
2. Selecciona la rama `vercel-nextjs-refactor` para el primer despliegue.
3. Añade `DATABASE_URL` y `AUTH_SECRET` en Project Settings → Environment Variables.
4. Ejecuta el despliegue.
5. Comprueba `/login`, `/`, `/clients`, `/deliveries` y los endpoints `/api/auth/*`, `/api/clients` y `/api/deliveries`.

## Base de datos

La base actual debe seguir existiendo en un servidor MySQL accesible desde Vercel. La aplicación conserva las tablas existentes (`auth_user`, `clients`, `shipments`, `delivery`, `origen`, `drivers`, `vehicules`).

## Seguridad

La versión PHP original contenía las credenciales de MySQL directamente en `api/db_connect.php`. Ese archivo fue eliminado de la rama de Vercel y las credenciales se leen exclusivamente de variables de entorno.

**Importante:** la contraseña que estuvo en el repositorio debe considerarse comprometida y debe rotarse en el servidor MySQL aunque el archivo haya sido eliminado del árbol actual, porque puede permanecer en el historial Git.

## Estado de la migración

El acceso, autenticación, dashboard, consulta de clientes, consulta de rutas y creación de rutas ya están preparados para el runtime de Vercel. Las pantallas PHP originales permanecen en la rama como referencia de la migración; Vercel ejecutará la aplicación Next.js por la presencia de `package.json` y la configuración de Next.js.
