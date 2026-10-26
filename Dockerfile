# Grove WordPress — šablona Grove Cloudu (25. 10. 2026)
#
# Jedna image pro všechny weby. Základ je oficiální image WordPressu s PHP 8.4;
# Grove Cloud staví s --pull a hlídá, kdy vyjde nová verze (oprava PHP,
# WordPressu nebo systému), a weby přestaví sám. Ty řešíš jen obsah.
FROM wordpress:php8.4-apache

COPY grove-php.ini /usr/local/etc/php/conf.d/zz-grove.ini
COPY grove-guard.php /usr/local/lib/grove/grove-guard.php
COPY grove-mu.php /usr/src/grove/grove-mu.php
COPY grove-entrypoint.sh /usr/local/bin/grove-entrypoint.sh

# Apache: bez verze v hlavičkách, statické soubory s cache na týden, skutečná IP návštěvníka z Caddy, port z $PORT
# (nad 1024 → běží jako www-data, ne root).
RUN set -eux; \
  chmod 0755 /usr/local/bin/grove-entrypoint.sh; \
  sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf; \
  a2enmod remoteip headers rewrite >/dev/null; \
  { echo 'RemoteIPHeader X-Real-IP'; \
    echo 'RemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16'; } > /etc/apache2/conf-available/grove-remoteip.conf; \
  a2enconf grove-remoteip >/dev/null; \
  { echo '<IfModule mod_headers.c>'; \
    echo '<FilesMatch "\\.(css|js|mjs|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|otf)$">'; \
    echo 'Header set Cache-Control "public, max-age=604800"'; \
    echo '</FilesMatch>'; \
    echo '</IfModule>'; } > /etc/apache2/conf-available/grove-cache.conf; \
  a2enconf grove-cache >/dev/null; \
  echo 'ServerName localhost' > /etc/apache2/conf-available/grove-servername.conf; \
  a2enconf grove-servername >/dev/null; \
  sed -ri 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf; \
  sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/' /etc/apache2/sites-available/000-default.conf; \
  chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/www/html

ENV PORT=8080
USER www-data
EXPOSE 8080
ENTRYPOINT ["grove-entrypoint.sh"]
CMD ["apache2-foreground"]
