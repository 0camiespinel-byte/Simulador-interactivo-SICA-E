# SICA-E · Sistema Inteligente de Control de Acceso Escolar

Aplicación PHP/MySQL para la Institución Educativa Brighton Pamplona,
preparada para desplegarse en Vercel mediante FrankenPHP.

## Despliegue rápido

1. Importa `database/schema.sql` en una base de datos MySQL externa.
2. En Vercel, importa este repositorio y conserva `Dockerfile.vercel`,
   `Caddyfile` y `vercel.json` en la raíz.
3. Configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`,
   `DB_CHARSET=utf8mb4` y `DB_AUTO_INIT=false` en las variables de entorno.
4. Después de comprobar la instalación, elimina o protege `public/install.php`.

Consulta `README_VERCEL.md` para detalles sobre sesiones, fotos y almacenamiento
persistente en producción.