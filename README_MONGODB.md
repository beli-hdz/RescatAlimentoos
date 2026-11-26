# RescatAlimentos con MongoDB + Render

El proyecto usa **PHP + MongoDB Atlas**. En Render se despliega con Docker
(Render no trae PHP de forma nativa; el `Dockerfile` ya instala PHP, Apache y la extensión de MongoDB).

## 1. Configuración (variables de entorno)

| Variable | Para qué sirve |
|---|---|
| `MONGODB_URI` | Cadena de conexión de Atlas |
| `MONGODB_DATABASE` | `rescat_alimentos` |
| `APP_TIMEZONE` | `America/Monterrey` |
| `ADMIN_USERNAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME` | Crean el primer super administrador al arrancar (solo si aún no existe ninguno) |

Plantilla en `.env.example`. **Nunca subas tu `.env` a GitHub** (ya está en `.gitignore`).

## 2. Atlas

1. **Database Access**: usa un usuario con permisos de lectura/escritura sobre la base.
2. **Network Access**: Render usa IPs dinámicas, así que agrega `0.0.0.0/0` (cualquier IP). La seguridad queda en la contraseña del usuario de la base.

## 3. Probar en local (con Docker)

```bash
cp .env.example .env          # edita MONGODB_URI y ADMIN_*
docker build -t rescat .
docker run --rm -p 8080:10000 --env-file .env rescat
```

Abre http://localhost:8080 e inicia sesión con el usuario `ADMIN_USERNAME`.

## 4. Subir a Render

1. Sube el proyecto a GitHub (sin `.env`).
2. En Render: **New → Web Service** → tu repositorio → **Runtime: Docker**
   (o **New → Blueprint** para que use `render.yaml`).
3. En **Environment** captura las variables de la sección 1.
4. Deploy. Al arrancar, `scripts/setup_mongodb.php` crea los índices y el super admin.
5. Cuando ya entraste, puedes borrar `ADMIN_PASSWORD` de Render.

## 5. Estructura de la base de datos

| Colección | Reemplaza la tabla |
|---|---|
| `users` | `users` |
| `centers` | `centers` (centros y puntos de recolección) |
| `donations` | `donations` + `donation_attachments` (los adjuntos van dentro de cada donación, en `attachments`) |
| `delivery_updates` | `delivery_updates` |
| `forum_posts`, `forum_replies` | igual |
| `notifications` | `notifications` |
| `counters` | auxiliar: genera los ids numéricos (reemplaza `AUTO_INCREMENT`) |

Las tablas `products`, `orders` y `order_items` no se migraron: ningún archivo las usaba.

## 6. Limitaciones de Render que debes conocer

- **Las fotos y PDF subidos se pierden** en cada deploy o reinicio (el disco de Render es temporal).
  Opciones: Render Disk (de pago) montado en `/var/www/html/uploads`, o guardar los archivos en un servicio externo (Cloudinary, S3…).
- **Las sesiones** (inicio de sesión) se guardan en archivos temporales: un deploy cierra la sesión de todos.
- El plan gratuito se "duerme" tras un rato sin visitas; la primera carga tarda.

## 7. Comandos útiles

```bash
php scripts/probar_conexion.php   # verifica la conexión y lista las colecciones
php scripts/setup_mongodb.php     # recrea índices / super admin (se puede repetir)
```
