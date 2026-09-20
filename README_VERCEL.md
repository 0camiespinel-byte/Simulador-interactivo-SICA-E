# SICA-E en Vercel

Esta versión conserva la aplicación PHP/MySQL original y añade un despliegue
compatible con Vercel mediante un contenedor FrankenPHP.

## Antes de desplegar

1. Crea una base de datos **MySQL externa**. Vercel no proporciona un servidor
   MySQL local para este proyecto.
2. Importa `database/schema.sql` en esa base de datos.
3. Sube el contenido de esta carpeta a un repositorio de GitHub.
4. En Vercel, importa el repositorio y deja el framework como **Other** si
   Vercel lo solicita. El proyecto ya incluye `vercel.json` y
   `Dockerfile.vercel`.
5. Añade estas variables en **Project Settings → Environment Variables**:

   - `DB_HOST`
   - `DB_PORT` (normalmente `3306`)
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASSWORD`
   - `DB_CHARSET=utf8mb4`
   - `DB_AUTO_INIT=false`

## Desarrollo local

Requiere Docker y Vercel CLI:

```bash
npm install --global vercel
vercel dev
```

## Importante para producción

- El sistema de archivos de Vercel no es permanente. Las fotos guardadas en
  `uploads/` pueden desaparecer cuando el contenedor se reinicia. Para fotos
  permanentes hay que integrar almacenamiento externo, como Vercel Blob o S3.
- Las sesiones PHP locales no se comparten entre instancias. Para tráfico
  real conviene cambiar las sesiones a Redis u otro almacén externo.
- Después de instalar y comprobar el sistema, elimina `public/install.php` o
  protégelo para no dejar visible el diagnóstico y las credenciales de prueba.
- Cambia las credenciales semilla incluidas en el `schema.sql` antes de usarlo
  con datos reales.