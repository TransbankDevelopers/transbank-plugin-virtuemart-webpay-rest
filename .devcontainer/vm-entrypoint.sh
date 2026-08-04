#!/usr/bin/env bash
set -e

WEBROOT=/var/www/html

JOOMLA_DB_HOST="${JOOMLA_DB_HOST:-db}"
JOOMLA_DB_NAME="${JOOMLA_DB_NAME:-virtuemart}"
JOOMLA_DB_USER="${JOOMLA_DB_USER:-virtuemart}"
JOOMLA_DB_PASSWORD="${JOOMLA_DB_PASSWORD:-virtuemart}"
JOOMLA_ADMIN_USER="${JOOMLA_ADMIN_USER:-admin}"
JOOMLA_ADMIN_PASSWORD="${JOOMLA_ADMIN_PASSWORD:-admin}"
JOOMLA_ADMIN_EMAIL="${JOOMLA_ADMIN_EMAIL:-admin@example.com}"
JOOMLA_SITE_NAME="${JOOMLA_SITE_NAME:-Joomla Installation}"

if [[ ! -f "$WEBROOT/configuration.php" ]]; then
  echo "Ensuring Joomla database is present..."
  php /makedb.php "$JOOMLA_DB_HOST" "$JOOMLA_DB_USER" "$JOOMLA_DB_PASSWORD" "$JOOMLA_DB_NAME"

  echo "Copiando Joomla + VirtueMart a ${WEBROOT}..."
  tar cf - --one-file-system -C /usr/src/virtuemart . | tar xf - -C "$WEBROOT"

  # The installer runs as www-data and needs to be able to write configuration.php;
  # if the webroot is still owned by root, Joomla fails silently (createConfiguration()
  # still returns true, but never writes the file).
  chown -R www-data:www-data "$WEBROOT"

  cd "$WEBROOT"

  if [[ -e htaccess.txt && ! -e .htaccess ]]; then
    # The "Indexes" option is disabled in the base php:apache image
    sed -r 's/^(Options -Indexes.*)$/#\1/' htaccess.txt > .htaccess
    chown www-data:www-data .htaccess
  fi

  sed 's/default="localhost"/default="'"$JOOMLA_DB_HOST"'"/;
       s/default="127.0.0.1"/default="'"$JOOMLA_DB_HOST"'"/;
       s/\(name=.*db_user.*\)$/\1 default="'"$JOOMLA_DB_USER"'"/;
       s/\(name=.*db_pass.*\)$/\1 default="'"$JOOMLA_DB_PASSWORD"'"/;
       s/\(name=.*db_name.*\)$/\1 default="'"$JOOMLA_DB_NAME"'"/;
       ' installation/model/forms/database.xml > installation/model/forms/database.xml.new
  mv installation/model/forms/database.xml.new installation/model/forms/database.xml

  sed 's/\(name=.*site_name.*\)$/\1 default="'"$JOOMLA_SITE_NAME"'"/;
       s/\(name=.*admin_email.*\)$/\1 default="'"$JOOMLA_ADMIN_EMAIL"'"/;
       s/\(name=.*admin_user.*\)$/\1 default="'"$JOOMLA_ADMIN_USER"'"/;
       s/\(name=.*admin_password.*\)$/\1 default="'"$JOOMLA_ADMIN_PASSWORD"'"/;
       ' installation/model/forms/site.xml > installation/model/forms/site.xml.new
  mv installation/model/forms/site.xml.new installation/model/forms/site.xml

  echo "Instalando Joomla + VirtueMart via CLI (host de base de datos: $JOOMLA_DB_HOST)..."
  # Joomla's CLI installer requires a manual verification step when the
  # database host isn't localhost (meant for interactive installs).
  # JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1 is the official way to skip
  # it for automated/CI installs.
  runuser -u www-data -- env JOOMLA_INSTALLATION_DISABLE_LOCALHOST_CHECK=1 \
    php ./installation/install.php --name="$JOOMLA_SITE_NAME" \
    --admin-user="$JOOMLA_ADMIN_USER" --admin-pass="$JOOMLA_ADMIN_PASSWORD" --admin-email="$JOOMLA_ADMIN_EMAIL" \
    --db-host="$JOOMLA_DB_HOST" --db-user="$JOOMLA_DB_USER" --db-pass="$JOOMLA_DB_PASSWORD" --db-name="$JOOMLA_DB_NAME" \
    --sample="sample_virtuemart.sql"

  if [[ ! -f "$WEBROOT/configuration.php" ]]; then
    echo "ERROR: Joomla installation finished but configuration.php was not generated" >&2
    exit 1
  fi

  rm -rf "$WEBROOT/installation"

  echo
  echo "========================================================================"
  echo
  echo "VirtueMart instalado."
  echo "Admin: http://localhost:8081/administrator"
  echo "  user: $JOOMLA_ADMIN_USER"
  echo "  password: $JOOMLA_ADMIN_PASSWORD"
  echo
  echo "========================================================================"
fi

exec "$@"
