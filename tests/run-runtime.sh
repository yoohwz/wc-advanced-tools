#!/usr/bin/env bash
# Creates synthetic users/orders. Use only with a fresh disposable database/site.
set -euo pipefail
: "${AT_DISPOSABLE_TESTS:?Set AT_DISPOSABLE_TESTS=1 for a disposable installation}"
: "${AT_WP_PATH:?Set an empty disposable WordPress path}"
: "${AT_DB_HOST:?Set the disposable database host}"
: "${AT_DB_PASSWORD?Set the disposable database password (may be empty locally)}"
AT_SOURCE="$(cd "$(dirname "$0")/.." && pwd)"
AT_CLI="${RUNNER_TEMP:-/tmp}/at-wp-cli.phar"
curl --fail --silent --show-error --location https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar -o "$AT_CLI"
wp_at() { php -d memory_limit=512M "$AT_CLI" --path="$AT_WP_PATH" "$@"; }
if [[ -e "$AT_WP_PATH/wp-config.php" ]]; then
  echo 'Refusing to use an existing configured WordPress site.' >&2
  exit 1
fi
mkdir -p "$AT_WP_PATH"
wp_at core download --version=7.1.3
wp_at config create --dbname="${AT_DB_NAME:-at3_test}" --dbuser=root --dbpass="$AT_DB_PASSWORD" --dbhost="$AT_DB_HOST" --skip-check --extra-php <<'PHP'
define( 'DISABLE_WP_CRON', true );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
PHP
if [[ -n "$(wp_at db query 'SHOW TABLES' --skip-column-names)" ]]; then
  echo 'Refusing to use a nonempty database.' >&2
  exit 1
fi
wp_at core install --url=http://127.0.0.1:8093 --title='AT disposable runtime' --admin_user=at3admin --admin_password=at3-disposable-only --admin_email=at3@example.invalid --skip-email
wp_at option update siteurl http://127.0.0.1:8093
wp_at option update home http://127.0.0.1:8093
wp_at plugin install woocommerce --version=11.2.1
ln -s "$AT_SOURCE" "$AT_WP_PATH/wp-content/plugins/wc-advanced-tools"
wp_at plugin activate wc-advanced-tools
wp_at eval-file "$AT_SOURCE/tests/woocommerce-unavailable.php"
wp_at plugin activate woocommerce
wp_at option update woocommerce_custom_orders_table_data_sync_enabled no
wp_at option update woocommerce_custom_orders_table_enabled no
wp_at eval-file "$AT_SOURCE/tests/runtime.php" legacy
# A separate table prefix gives HPOS fresh data, without syncing legacy fixtures.
wp_at config set table_prefix athpos_ --type=variable
wp_at core install --url=http://127.0.0.1:8093 --title='AT disposable HPOS runtime' --admin_user=at3admin --admin_password=at3-disposable-only --admin_email=at3@example.invalid --skip-email
wp_at option update siteurl http://127.0.0.1:8093
wp_at option update home http://127.0.0.1:8093
wp_at plugin activate woocommerce wc-advanced-tools
wp_at option update woocommerce_custom_orders_table_data_sync_enabled no
wp_at option update woocommerce_custom_orders_table_enabled yes
wp_at eval-file "$AT_SOURCE/tests/runtime.php" hpos
# Real authenticated HTTP download and browser-facing response headers.
php -S 127.0.0.1:8093 -t "$AT_WP_PATH" > "${RUNNER_TEMP:-/tmp}/at-http.log" 2>&1 &
AT_HTTP_PID=$!
trap 'kill "$AT_HTTP_PID" 2>/dev/null || true' EXIT
python3 "$AT_SOURCE/tests/http_checks.py"
wp_at plugin deactivate woocommerce
wp_at eval-file "$AT_SOURCE/tests/woocommerce-unavailable.php"
mv "$AT_WP_PATH/wp-content/plugins/woocommerce" "$AT_WP_PATH/woocommerce-absent"
wp_at eval-file "$AT_SOURCE/tests/woocommerce-unavailable.php"
