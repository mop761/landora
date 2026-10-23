# Landora

Plataforma web para crear, publicar y gestionar landing pages orientadas a la captación de leads. Proyecto Intermodular del Ciclo Formativo de Grado Superior en Desarrollo de Aplicaciones Web (DAW) — IES Cañaveral.

## Tecnologías
- PHP 8.4
- Laravel
- MySQL
- Blade, JavaScript (vanilla), HTML5, CSS3
- Chart.js (vía CDN)

## Requisitos del servidor
- PHP 8.4 o superior, con las extensiones: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, cURL, Fileinfo, BCMath
- Composer
- MySQL 8 / MariaDB
- Servidor web Apache o Nginx (con la raíz apuntando a la carpeta `public/`)

## Instalación
1. Clonar el repositorio:
   `git clone https://github.com/mop761/landora.git && cd landora`
2. Instalar dependencias:
   `composer install`
3. Crear el archivo de entorno y la clave de aplicación:
   `copy .env.example .env` (Windows) · `php artisan key:generate`
4. Configurar la base de datos en `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
5. Ejecutar las migraciones:
   `php artisan migrate`
6. Levantar el servidor de desarrollo:
   `php artisan serve` → abrir http://127.0.0.1:8000

## Autor
Mario Ortega Porras — Modalidad libre (IES Cañaveral).