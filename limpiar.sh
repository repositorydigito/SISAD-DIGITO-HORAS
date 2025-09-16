#!/bin/bash

# Limpiar las vistas compiladas
php artisan view:clear

# Limpiar la caché de configuración
php artisan config:clear

# Limpiar la caché de rutas
php artisan route:clear

# Limpiar la caché de aplicación
php artisan cache:clear

echo "Todas las vistas y cachés de Laravel han sido limpiadas."
