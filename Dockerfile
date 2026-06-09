FROM php:8.2-apache

# Instalamos las extensiones necesarias para bases de datos
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitamos el mod_rewrite de Apache 
RUN a2enmod rewrite