# Local WordPress staging

This Compose stack is reproducible and intentionally uses disposable local credentials. It provisions MariaDB 10.11, WordPress 6.7/PHP 8.3 Apache, WP-CLI, persistent core/database/uploads volumes, and mounts this repository's `wp-content`. The local stack is a development fallback; production certification still requires an authorized staging URL, Redis, SSH/DB access, and real external probes.

```bash
docker compose up -d db wordpress
# Complete the browser installer at http://localhost:8080/wp-admin/install.php
# Then install WooCommerce from Plugins, activate Bamero and its custom/setup plugins.
docker compose --profile tools run --rm wpcli plugin list
docker compose down
```

Do not use these credentials outside local development. The stack does not configure real payment, SMTP, or external API credentials.

## Provision the real catalog

After WordPress and WooCommerce are installed and the local stack is running, execute:

```bash
bash staging/provision-catalog.sh
```

The command creates or updates ten published WooCommerce products, six product categories, local catalog artwork, and ten customer accounts identified by mobile number. It is repeatable by SKU/mobile and does not create UI-only cards or fake API responses. The homepage reads the published records directly from WooCommerce through `front-page.php`.
