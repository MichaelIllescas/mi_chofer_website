# Mi Chofer - Sitio Web

Sitio web institucional para **Mi Chofer**, servicio de remises. Desarrollado con Astro, Bootstrap 5 y PHP para el backend de emails.

## Tecnologias

- **Frontend:** Astro 5, Bootstrap 5, Bootstrap Icons, AOS (animaciones)
- **Backend:** PHP (PHPMailer) para envio de emails via SMTP
- **Hosting:** Hostinger

## Estructura del proyecto

```
mi_chofer_website/
├── src/
│   ├── components/      # Componentes Astro (Hero, About, Contact, etc.)
│   ├── layouts/         # Layout principal
│   ├── pages/           # Paginas (index.astro)
│   ├── assets/          # Imagenes, videos, SVGs
│   └── js/              # JavaScript del navbar
├── public/
│   ├── js/              # Scripts del cliente (sendContact.js, loader.js)
│   ├── img/             # Imagenes publicas
│   ├── services/
│   │   ├── api/         # Endpoint PHP (send-email.php)
│   │   ├── email/       # EmailService + PHPMailer + templates HTML
│   │   └── php/         # Config (mail.php) y utilidades (helpers.php)
│   └── .htaccess        # Seguridad y CORS
├── .env                 # Variables de entorno (no se sube al repo)
├── astro.config.mjs
└── package.json
```

## Instalacion

```bash
npm install
```

## Desarrollo

```bash
npm run dev
```

## Build para produccion

```bash
npm run build
```

Los archivos generados se guardan en `dist/`.

## Deploy en Hostinger

1. Subir el contenido de `dist/` a `public_html/`
2. Subir la carpeta `public/services/` a `public_html/services/`
3. Colocar el archivo `.env` en la raiz del hosting (al mismo nivel que `public_html/`)
4. Verificar que `.htaccess` este en `public_html/`

## Variables de entorno (.env)

```env
SMTP_HOST=smtp.hostinger.com
SMTP_PORT=465
SMTP_SECURE=true
SMTP_USER=tu_email@dominio.com
SMTP_PASS=tu_password

RECIPIENT_EMAIL=contacto@michofer.com.ar
CONSULTATION_EMAIL=contacto@michofer.com.ar

NODE_ENV=production
DEBUG=false
```
