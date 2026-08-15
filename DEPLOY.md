# Deploying to Hostinger (Business shared plan)

Fresh-start deploy: empty database, only the login accounts + academy settings.
No demo students/fees. Target: **PHP 8.3+**, MySQL, HTTPS.

---

## 0. On your computer (once)

Assets are already built into `public/build/` (`npm run build`).
Because `/public/build`, `/vendor` and `.env` are git-ignored, you'll add
`vendor` and the build on the server, and create `.env` there.

---

## 1. hPanel — create the database
`Databases → MySQL Databases` → create a database + user (tick all privileges).
Write down: **DB name, DB user, DB password** (host is usually `localhost`).

## 2. hPanel — set PHP version
`Advanced → PHP Configuration` → choose **PHP 8.3**.
Enable extensions: `pdo_mysql, mbstring, openssl, gd, fileinfo, curl, zip, bcmath`.

## 3. hPanel — turn on SSH
`Advanced → SSH Access` → note the **host, port, username**. Connect:
```bash
ssh -p <PORT> <USER>@<HOST>
```

## 4. On the server — get the code
```bash
cd ~/domains/<yourdomain.com>          # or wherever your site lives
git clone https://github.com/FlutterOmiii/coaching_academy_fees_attendance.git app
cd app
composer install --no-dev --optimize-autoloader
```

## 5. Upload the built assets
`/public/build` is not in Git. Upload your local **`public/build/`** folder into
the server's **`app/public/build/`** using hPanel File Manager or SFTP.
(Alternative: locally run `git add -f public/build && git commit && git push`, then `git pull` on the server.)

## 6. Configure `.env`
```bash
cp .env.example .env
nano .env            # paste the values from .env.production (in this repo), fill every CHANGE_ME
php artisan key:generate
```
Set at least: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://yourdomain.com`,
the `DB_*` from step 1, `FILESYSTEM_DISK=public`, `SESSION_SECURE_COOKIE=true`.
**Do not add any `E2E_*` or `AWS_*` keys** — that keeps photos on the local disk.

## 7. Migrate + seed (fresh start = accounts & settings only)
```bash
php artisan migrate --force
php artisan db:seed --class=SettingSeeder --force   # academy name, currency, etc.
php artisan db:seed --class=AdminSeeder --force      # the 4 login accounts
php artisan storage:link                             # student photos
```
> Do NOT run `php artisan db:seed` with no class — that loads all the demo data.

## 8. Cache for production + permissions
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

## 9. Point the domain at `public/`
hPanel → your domain → set **Document Root** to `.../app/public`.
(If your plan can't change it: move the contents of `app/public/` into
`public_html/`, keep the rest of `app/` one level above, and edit the two
`require` paths in `public_html/index.php` to point up to `../app/...`.)

## 10. Enable SSL — **required**
hPanel → `Security → SSL` → install the free Let's Encrypt certificate, then
force HTTPS. The **camera on the Add-Student form only works over HTTPS**.

## 11. Log in and secure it
Visit `https://yourdomain.com/admin/login`.

**⚠️ Change the default passwords immediately** — every seeded account uses `password`:
| Email | Role |
|---|---|
| admin@admin.com | Owner |
| manager@academy.com | Admin |
| coach@academy.com | Coach |
| accounts@academy.com | Accountant |

Log in as the owner, change its password, and delete or re-password the others.

---

## Redeploying later (after code changes)
```bash
cd ~/domains/<yourdomain.com>/app
git pull
composer install --no-dev --optimize-autoloader
# re-upload public/build only if the CSS/JS changed
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
