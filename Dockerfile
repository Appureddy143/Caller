# ============================================================
#  AdmissionConnect — Dockerfile
#  Base: php:8.2-apache (Debian Bookworm)
#  Connects to: Neon PostgreSQL (external, SSL required)
# ============================================================

FROM php:8.2-apache

# ── System dependencies ──────────────────────────────────────
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ───────────────────────────────────────────
# pdo_pgsql  → PostgreSQL via PDO (required for Neon)
# pgsql      → native PostgreSQL driver
# zip        → zip/unzip support
# opcache    → performance
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    opcache

# ── Apache modules ───────────────────────────────────────────
# mod_rewrite  → .htaccess URL routing
# mod_headers  → security headers in .htaccess
RUN a2enmod rewrite headers

# ── PHP configuration ────────────────────────────────────────
RUN { \
    echo "upload_max_filesize = 32M"; \
    echo "post_max_size = 32M"; \
    echo "memory_limit = 128M"; \
    echo "max_execution_time = 60"; \
    echo "session.cookie_httponly = 1"; \
    echo "session.use_strict_mode = 1"; \
    echo "session.cookie_secure = 0"; \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/apache2/php_errors.log"; \
} > /usr/local/etc/php/conf.d/app.ini

# ── OPcache configuration (performance) ─────────────────────
RUN { \
    echo "opcache.enable = 1"; \
    echo "opcache.memory_consumption = 128"; \
    echo "opcache.interned_strings_buffer = 8"; \
    echo "opcache.max_accelerated_files = 4000"; \
    echo "opcache.revalidate_freq = 60"; \
    echo "opcache.fast_shutdown = 1"; \
} > /usr/local/etc/php/conf.d/opcache.ini

# ── Apache VirtualHost configuration ─────────────────────────
# Allow .htaccess overrides and set document root
RUN { \
    echo "<VirtualHost *:80>"; \
    echo "    DocumentRoot /var/www/html"; \
    echo "    DirectoryIndex index.php index.html"; \
    echo ""; \
    echo "    <Directory /var/www/html>"; \
    echo "        Options -Indexes +FollowSymLinks"; \
    echo "        AllowOverride All"; \
    echo "        Require all granted"; \
    echo "    </Directory>"; \
    echo ""; \
    echo "    # Block access to sensitive files"; \
    echo "    <FilesMatch \"^\.env\">"; \
    echo "        Require all denied"; \
    echo "    </FilesMatch>"; \
    echo ""; \
    echo "    <Directory /var/www/html/includes>"; \
    echo "        Require all denied"; \
    echo "    </Directory>"; \
    echo ""; \
    echo "    ErrorLog \${APACHE_LOG_DIR}/error.log"; \
    echo "    CustomLog \${APACHE_LOG_DIR}/access.log combined"; \
    echo "</VirtualHost>"; \
} > /etc/apache2/sites-available/000-default.conf

# ── Copy application files ───────────────────────────────────
WORKDIR /var/www/html
COPY . .

# ── Fix file permissions ─────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -name "*.php" -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod 600 /var/www/html/.env 2>/dev/null || true

# ── Expose port ──────────────────────────────────────────────
EXPOSE 80

# ── Health check ─────────────────────────────────────────────
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Apache starts automatically via the base image CMD
