FROM php:8.2-fpm-alpine

# Instalar extensiones de PHP necesarias para Laravel y herramientas
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git

RUN docker-php-ext-install pdo pdo_mysql bcmath gd

# Configurar Directorio de Trabajo
WORKDIR /var/www/html

# Copiar el proyecto
COPY . .

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurar permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configuración básica de Nginx para Laravel
RUN echo 'server { \
    listen 80; \
    root /var/www/html/public; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/http.d/default.conf

EXPOSE 80

# Iniciar Nginx y PHP-FPM juntos
CMD php-fpm -D && nginx -g "daemon off;"