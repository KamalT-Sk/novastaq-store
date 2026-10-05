# Novastaq Store

The shop at **store.novastaq.com**: phones, laptops, gadgets and subscriptions, with an admin at `/admin`.
Laravel 12 (PHP 8.2+), MySQL in production, Paystack for payments.

## What's where

| Area | Path |
|---|---|
| Storefront pages | `resources/views/shop/` |
| Admin pages | `resources/views/admin/` |
| Payments (Paystack, swappable) | `app/Payments/` |
| Marks orders paid, takes stock | `app/Support/Fulfilment.php` |
| Store settings | `.env` (see "Settings" below) and `config/store.php` |

Prices are stored in kobo (₦1 = 100 kobo). The admin takes naira and converts.

## Run it locally

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite   # local uses SQLite
php artisan migrate --seed       # tables + starter categories
php artisan storage:link
php artisan store:make-admin you@example.com
php artisan serve                # http://localhost:8000, admin at /admin
```

Run the tests with `php artisan test`.

## Deploy to cPanel (store.novastaq.com)

1. **Subdomain**: cPanel → Domains → create `store.novastaq.com` with document root `store.novastaq.com/public`.
2. **Database**: cPanel → MySQL Databases → create a database and a user, and give the user all privileges.
3. **Code**: over SSH, `git clone <repo> ~/store.novastaq.com && cd ~/store.novastaq.com`, then
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env && php artisan key:generate
   ```
4. **`.env`** on the server:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://store.novastaq.com
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=your_db
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   PAYSTACK_PUBLIC_KEY=pk_live_...
   PAYSTACK_SECRET_KEY=sk_live_...
   ```
5. **Finish**:
   ```bash
   php artisan migrate --seed --force
   php artisan storage:link
   php artisan store:make-admin you@novastaq.com
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. **SSL**: cPanel → SSL/TLS Status → run AutoSSL for the subdomain.
7. **Paystack webhook**: Paystack dashboard → Settings → API Keys & Webhooks → Webhook URL
   `https://store.novastaq.com/paystack/webhook`. Subscriptions and late payment confirmations depend on it.

Compiled CSS and JS (`public/build`) are committed, so the server doesn't need Node.
After changing styles locally, run `npm run build` and commit.

**Updating later**: `git pull && composer install --no-dev && php artisan migrate --force && php artisan optimize`.

## Settings (`.env`)

| Key | Meaning |
|---|---|
| `STORE_DELIVERY_FEE` | Flat delivery fee in naira on gadget orders. `0` = "confirmed after order". |
| `STORE_SUPPORT_EMAIL` | Shown in the footer. |
| `STORE_WHATSAPP_URL` | "Chat with us" links. |
| `MAIN_SITE_URL` | Link back to novastaq.com. |

## Subscriptions

A product of type **Subscription** gets a Paystack plan the first time someone subscribes.
Changing its price or billing period in the admin creates a new plan for new subscribers; existing
subscribers stay on their old plan in Paystack.
