#!/bin/bash
# Grove WordPress — start kontejneru. Pak předá řízení oficiálnímu entrypointu.
set -euo pipefail

# Soli WordPressu (přihlášení, cookies) musí být stejné mezi nasazeními, jinak
# by každé nasazení všechny odhlásilo. Odvozujeme je z hesla databáze, které
# je tajné a pro každý web jiné. Vlastní WORDPRESS_* soli mají přednost.
if [ -n "${WORDPRESS_DB_PASSWORD:-}" ]; then
  for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do
    v="WORDPRESS_${k}"
    if [ -z "${!v:-}" ]; then
      export "$v=$(printf '%s' "grove:${k}:${WORDPRESS_DB_PASSWORD}" | sha256sum | cut -d' ' -f1)"
    fi
  done
fi

# Bezpečná výchozí nastavení. Jádro WordPressu aktualizuje Grove Cloud novou
# image (proto WP_AUTO_UPDATE_CORE=false); pluginy a šablony se aktualizují
# samy (mu-plugin níž). Editor souborů v administraci je vypnutý.
export WORDPRESS_CONFIG_EXTRA="${WORDPRESS_CONFIG_EXTRA:-}
define('DISALLOW_FILE_EDIT', true);
define('WP_AUTO_UPDATE_CORE', false);
define('FORCE_SSL_ADMIN', true);
define('WP_POST_REVISIONS', 20);"

# Jádro WordPressu nakopírujeme sami (každé nasazení = nový kontejner = čisté
# jádro z image). Oficiální entrypoint by kopíroval i samotnou složku
# wp-content a pokusil se jí změnit práva — na trvalém disku Grove Cloudu
# (vlastník root) to jako www-data selže a první start kontejneru spadne.
# wp-content doplníme jen o to, co v ní chybí (cp -n nepřepíše pluginy,
# šablony ani nahrané soubory).
if [ ! -e /var/www/html/index.php ] || [ ! -e /var/www/html/wp-includes/version.php ]; then
  tar -C /usr/src/wordpress --exclude=./wp-content -cf - . | tar -C /var/www/html -xf - --no-same-owner --no-overwrite-dir
  mkdir -p /var/www/html/wp-content
  cp -Rn /usr/src/wordpress/wp-content/. /var/www/html/wp-content/ 2>/dev/null || true
fi

# mu-plugin Grove: wp-content je na trvalém disku, proto ho kopírujeme při
# každém startu (nová verze šablony = nová verze pluginu).
if [ -d /var/www/html/wp-content ]; then
  mkdir -p /var/www/html/wp-content/mu-plugins 2>/dev/null || true
  cp -f /usr/src/grove/grove-mu.php /var/www/html/wp-content/mu-plugins/grove-cloud.php 2>/dev/null || true
fi

exec docker-entrypoint.sh "$@"
