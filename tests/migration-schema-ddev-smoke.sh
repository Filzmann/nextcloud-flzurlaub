#!/usr/bin/env bash
set -euo pipefail

ddev_project="${ADU_DDEV_PROJECT:-$(cd "$(dirname "$0")/../../nextcloud-dev" && pwd)}"

(cd "$ddev_project" && ddev exec -d /var/www/html/html \
    php /var/www/html/html/custom_apps/adurlaub/tests/integration/MigrationSchemaSmoke.php)
