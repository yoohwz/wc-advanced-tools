# WooCommerce Advanced Tools

WooCommerce administration utilities, developed **Premium-first** by YoOhw.

Development source for the first standalone Premium plugin. No commercial/public plugin release or license grant is implied by this public repository. AT-3 explicitly authorizes public-source development; never commit credentials, customer data or private exports.

Development follows [the engineering workflow](docs/workflow.md). Merge and release require separate Human authorization.

Product focus: safe import/export, bulk operations and data-health tools. A future Free/Freemium split is intentionally outside the initial Premium scope.

## Quick Order Export

Requires PHP 8.2+, WordPress 6.5+ and active WooCommerce 9.0+. Place this source directory at `wp-content/plugins/wc-advanced-tools/`, then activate **WooCommerce Advanced Tools**. Without a supported active WooCommerce version, **Tools → Advanced Tools** explains the dependency and export is unavailable.

Users with `manage_woocommerce` can open **WooCommerce → Advanced Tools**, select inclusive creation dates in the store timezone and an order status, then **Download CSV**. “All” includes registered WooCommerce statuses, not trash, draft placeholders or refunds. Defaults cover the last 30 calendar days.

The UTF-8, comma-separated CSV has one row per order, ordered by internal ID: `order_id`, `order_number` (public number), `created_at` (ISO 8601 with store timezone offset), `status` (slug without `wc-`), `currency` (code), `order_total` (stored decimal, decimal point, no thousands separator). Fields containing spreadsheet formulas are prefixed with an apostrophe. Customer/contact/address details, items, payment credentials and custom metadata are excluded. Keep downloaded order data private.

Each request selects at most 1,001 IDs and refuses more than **1,000 matching orders**. Empty results download column headings only. CSV preparation has a 15-second cooperative time budget and 2 MiB output limit; oversized fields, missing orders and preparation failures return an error before download headers. Narrow dates/status after a limit error. A complete CSV is prepared in a private temporary stream and closed after the request; no publicly accessible export file or background job is created. The plugin does not save orders or change stock, email, scheduled actions or store settings. Concurrent order edits are not a transactional snapshot: values reflect reads during preparation.

## Validation

CI runs syntax/whitespace plus actual WordPress 7.1.3 / WooCommerce 11.2.1 / PHP 8.2 / MariaDB 10.11 integration checks, separately using legacy storage and HPOS with synchronization disabled. Tests cover date/status filters and DST boundaries, CSV quoting/formula defense, empty/oversized results, read-only SQL observation, failed order reads, capability/nonce validation, real authenticated HTTP downloads/headers, and inactive/absent WooCommerce. This is one tested environment, not certification of every supported version, extension or hosting configuration.

To reproduce, supply an **empty disposable database** named `at3_test` and an unconfigured directory; run `AT_DISPOSABLE_TESTS=1 AT_WP_PATH=/tmp/at-wordpress AT_DB_HOST=127.0.0.1:3306 AT_DB_PASSWORD=... bash tests/run-runtime.sh`. The script creates synthetic users and orders and starts a temporary local PHP server on port 8093. Never run against an existing store or customer database. It downloads the pinned WordPress, WooCommerce and WP-CLI versions; no runtime dependencies are downloaded by the plugin itself.
