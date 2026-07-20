# ===========================================================================
# Stage 1: Build aset frontend (Vite) — menghasilkan public/build/.
# Terpisah agar Node tidak ikut ke image final (image lebih ramping).
# ===========================================================================
FROM node:20-alpine AS assets

WORKDIR /app

# Dependencies dulu (layer cache): salin manifest npm saja.
COPY package.json package-lock.json ./
RUN npm ci

# Salin source yang dibutuhkan Vite lalu build.
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build
# Hasil: /app/public/build (manifest.json + assets/)

# ===========================================================================
# Stage 2: Aplikasi PHP-FPM
# ===========================================================================
FROM php:8.2-fpm

# ---------------------------------------------------------------------------
# System dependencies
# - ghostscript: dipakai PdfMergerService untuk normalisasi PDF sebelum merge (FPDI)
# - libzip/oniguruma/png/jpeg/freetype/icu: dependency ekstensi PHP di bawah
# ---------------------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    supervisor \
    ghostscript \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    iputils-ping \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------------------------
# PHP extensions
# - pdo_mysql   : koneksi MySQL
# - mbstring    : string multibyte (Laravel)
# - exif, gd    : intervention/image, manipulasi gambar
# - pcntl       : queue worker
# - bcmath      : perhitungan presisi
# - zip         : maatwebsite/excel, phpword
# - intl        : Carbon translatedFormat/isoFormat (locale 'id')
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Dependencies dulu (manfaatkan layer cache): salin composer files saja,
# install tanpa menjalankan scripts (artisan belum ada di tahap ini).
# ---------------------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Salin seluruh source, lalu selesaikan autoloader & scripts.
COPY . .

# Aset Vite hasil build dari stage `assets` (menimpa public/build lama dari host,
# atau mengisinya bila host tidak punya). Image jadi self-contained.
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-autoload-dump || true

# PHP config: batas upload sesuai kebutuhan (nginx juga perlu di-set >= ini)
RUN { \
        echo "upload_max_filesize=80M"; \
        echo "post_max_size=80M"; \
        echo "memory_limit=512M"; \
        echo "max_execution_time=300"; \
    } > /usr/local/etc/php/conf.d/app.ini

# Supervisor (php-fpm + queue + scheduler)
COPY docker/php/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Entrypoint: siapkan storage, cache config, migrasi (opsional) saat start
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permissions untuk folder yang ditulis runtime.
# public/temp: aplikasi menulis docx sementara di sini saat generate dokumen
# (temp/templates, temp/documentation-photo) — WAJIB writable oleh www-data.
RUN mkdir -p /var/www/html/public/temp/templates \
             /var/www/html/public/temp/documentation-photo \
    && chown -R www-data:www-data \
             /var/www/html/storage \
             /var/www/html/bootstrap/cache \
             /var/www/html/public/temp

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]
