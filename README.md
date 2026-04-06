# 🛠️ Sistema de Tickets - Backend (API)

Este es el Backend completo para el **Sistema de Tickets**. Funciona como una API RESTful estructurada para servir los datos de forma robusta e inteligente a nuestra capa de presentacion.

## 🚀 Tecnolog\u00edas Usadas

*   **Framework Principal**: Laravel 11.x (PHP 8+)
*   **Base de Datos**: MySQL (Gestionado t\u00edpicamente con Laragon)
*   **Autenticaci\u00f3n**: Laravel Sanctum (Autenticaci\u00f3n SPA basada en Cookies / Tokens)
*   **Seguridad**: Throttle (Protecci\u00f3n de rutas), Request Validators, Protecci\u00f3n CSRF
*   **Arquitectura REST**: JSON API Resources para transformar los modelos elegantemente
*   **Otros Componentes**: Eloquent ORM, Migrations, y Patr\u00f3n Repository/Service para Controladores

## ⚙\ufe0f Gu\u00eda de Instalaci\u00f3n y Arranque

### 1. Requisitos Previos
*   Instalar [Laragon](https://laragon.org/) (o similar) con PHP >= 8.2 y MySQL.
*   Tener `composer` instalado globalmente.

### 2. Configurar el Entorno
1. Duplica el archivo `.env.example` y ren\u00f3mbralo a `.env`:
   ```bash
   cp .env.example .env
   ```
2. Configura las variables de base de datos en tu `.env`:
   ```ini
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistema_ticketdb
   DB_USERNAME=root
   DB_PASSWORD=
   ```
   Aseg\u00farate de crear la base de datos `sistema_ticketdb` (con HeidiSQL, phpMyAdmin, o consola MySQL).

### 3. Instalar Dependencias
Instala todas las librer\u00edas PHP necesarias utilizando Composer:
```bash
composer install
```

### 4. Generar Clave y Migrar Base de Datos
Genera la clave sismica de la aplicacion de Laravel y prepara la base de datos:
```bash
php artisan key:generate
php artisan migrate --seed
# (Ejecuta el flag --seed si tienes un DatabaseSeeder preparado para usuarios base)
```

### 5. Encender Servidor
Para iniciar el entorno de desarrollo y exponer la API, ejecuta:
```bash
php artisan serve
```
La API estará disponible por defecto en `http://127.0.0.1:8000`.

---
*Hecho en Laravel para asegurar rendimiento, estructuracion logica y arquitectura escalable.*
