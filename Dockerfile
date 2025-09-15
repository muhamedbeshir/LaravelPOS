# استخدام PHP 8.2 مع Apache
FROM php:8.2-apache

# تحديث الحزم وتثبيت الحزم المطلوبة
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# تمكين Apache rewrite module
RUN a2enmod rewrite

# تحديد دليل العمل
WORKDIR /var/www/html

# نسخ ملفات Composer أولاً للاستفادة من cache
COPY composer.json composer.lock ./

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# تثبيت تبعيات PHP
RUN composer install --no-dev --optimize-autoloader --no-scripts

# نسخ ملفات package.json للـ Node dependencies
COPY package*.json ./

# تثبيت تبعيات Node.js
RUN npm ci --only=production

# نسخ باقي ملفات المشروع
COPY . .

# تشغيل Vite build للإنتاج
RUN npm run build

# تنفيذ composer scripts بعد نسخ الملفات
RUN composer run-script post-autoload-dump

# إنشاء دليل storage وتحديد الصلاحيات
RUN mkdir -p /var/www/html/storage/logs \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# إعداد Apache Document Root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# إعداد Apache للعمل مع Laravel
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# إنشاء ملف start script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# انتظار قاعدة البيانات\n\
echo "Waiting for database..."\n\
until php artisan migrate:status > /dev/null 2>&1; do\n\
    echo "Database not ready, waiting..."\n\
    sleep 2\n\
done\n\
\n\
# تشغيل migrations إذا لم تكن موجودة\n\
echo "Running migrations..."\n\
php artisan migrate --force\n\
\n\
# تنظيف وتحسين Laravel\n\
echo "Optimizing Laravel..."\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
# بدء Apache\n\
echo "Starting Apache..."\n\
apache2-foreground' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# فتح المنفذ 80
EXPOSE 80

# تشغيل الأوامر عند بدء الحاوية
CMD ["/usr/local/bin/start.sh"]
