FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

EXPOSE 10000

CMD php -S 0.0.0.0:${PORT:-10000} index.php
