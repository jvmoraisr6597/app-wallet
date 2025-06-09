# Usando a imagem base do PHP com FPM
FROM php:8.2-fpm

# Instalar dependências do sistema e PHP
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    npm \
    nodejs \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis
    python3 \
    python3-pip \
    python3-venv \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar o Composer para o container
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir o diretório de trabalho no container
WORKDIR /var/www

# Copiar os arquivos do projeto para o container
COPY . .

# Instalar as dependências do Laravel via Composer
RUN /usr/bin/composer install --no-interaction --optimize-autoloader

# Expor a porta 9000 para o PHP-FPM
EXPOSE 9000

# Definir o comando padrão para rodar o PHP-FPM
CMD ["php-fpm"]
