# Usamos PHP 8.2 con Apache, coincidiendo con tu versión de XAMPP
FROM php:8.2-apache

# Instalamos las extensiones necesarias para bases de datos
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitamos el mod_rewrite de Apache (muy útil si usas URLs amigables)
RUN a2enmod rewrite