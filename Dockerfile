FROM php:8.2-cli

WORKDIR /app
COPY . .

RUN docker-php-ext-install curl

EXPOSE 8080

CMD php -S 0.0.0.0:$PORT index.php
