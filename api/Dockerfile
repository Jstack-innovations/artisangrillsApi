FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /api

COPY . /api

EXPOSE 8080

CMD sh -c "php -d variables_order=EGPCS -S 0.0.0.0:$PORT index.php"
