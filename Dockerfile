FROM php:8.2-apache

# Встановлюємо mysqli
RUN docker-php-ext-install mysqli

# Вмикаємо mod_rewrite (на всякий)
RUN a2enmod rewrite

WORKDIR /var/www/html
