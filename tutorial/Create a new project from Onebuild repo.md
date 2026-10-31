# New OneBuild Project from GitHub + nginx vhost

Step-by-step guide to pull a OneBuild repo from GitHub and serve it at its own local domain without conflicting with existing sites.

## Placeholders

Replace these everywhere you see them:

| Placeholder | Example | Meaning |
|---|---|---|
| `NAME` | `shop` | Short lowercase name, used for folder, database and nginx file |
| `NAME.test` | `shop.test` | Local domain |
| `REPO_URL` | `git@github.com:you/onebuild.git` | Your GitHub repo |

---

## 0. Conflict checklist (do this first)

Run these. Every command should print **nothing** (or "No such file"). If something prints, pick a different `NAME`.

```bash
ls -d /var/www/NAME 2>/dev/null
ls /etc/nginx/sites-available/NAME.test 2>/dev/null
ls /etc/nginx/sites-enabled/NAME.test 2>/dev/null
grep -w "NAME.test" /etc/hosts
mysql -u root -p -e "SHOW DATABASES LIKE 'NAME_db';"
```

Rules that prevent conflicts:

- One unique `NAME` per project: folder, domain, nginx file and database all use it.
- One `server_name` per vhost. Two vhosts must never share the same domain.
- Never edit `sites-enabled` directly. Create files in `sites-available` and symlink them.
- Keep `default` enabled (see step 9), otherwise `localhost` falls into whichever project loads first.

---

## 1. Clone the repo

```bash
cd /var/www
sudo git clone REPO_URL NAME
sudo chown -R $USER:www-data /var/www/NAME
```

If the repo is private and `git clone` asks for credentials, use the SSH URL (`git@github.com:...`) or a personal access token.

## 2. Install dependencies (only if the project uses them)

```bash
cd /var/www/NAME
[ -f composer.json ] && composer install
[ -f package.json ] && npm install
```

## 3. Create the `.env`

```bash
cd /var/www/NAME
cp .env.example .env     # skip if the repo has no .env.example
nano .env
```

Set at least:

```
APP_URL=http://NAME.test
DB_NAME=NAME_db
DB_USER=dev
DB_PASS=your_password
```

Always use **`http://`** locally. `https://` makes the app redirect to a port nothing is listening on.

## 4. Create the database

Fresh, empty database:

```bash
mysql -u root -p -e "CREATE DATABASE NAME_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Then import the schema if the repo has one (adjust the path):

```bash
mysql -u root -p NAME_db < /var/www/NAME/database/schema.sql
```

Each project gets its **own** database. Sharing one makes projects overwrite each other's data.

## 5. Whitelist the domain in the app

OneBuild forces HTTPS for any host it doesn't treat as local. Open:

```bash
nano /var/www/NAME/config/security.php
```

Add the new domain to the `$isLocalhost` list, as plain strings only:

```php
$isLocalhost = in_array($httpHost, [
    'localhost',
    '127.0.0.1',
    'NAME.test',
    'www.NAME.test',
    // ...your other domains
]);
```

Do not paste markdown links such as `[x](https://x)` into PHP.

## 6. Add the domain to `/etc/hosts`

```bash
echo "127.0.0.1 NAME.test" | sudo tee -a /etc/hosts
```

Use the same IP as your other `.test` domains.

## 7. Create the nginx vhost

```bash
sudo nano /etc/nginx/sites-available/NAME.test
```

Paste:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name NAME.test;

    root /var/www/NAME/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Optional: if you want phpMyAdmin on this domain too, add this block **above** `location / {`:

```nginx
    location ^~ /phpmyadmin/ {
        root /usr/share;
        index index.php;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        }
    }
```

This claims `/phpmyadmin/` on that domain, so the app can't use that URL. Skipping it is cleaner, since `localhost:8080` already serves phpMyAdmin.

## 8. Enable the vhost

```bash
sudo ln -s /etc/nginx/sites-available/NAME.test /etc/nginx/sites-enabled/NAME.test
```

## 9. Make sure `default` is enabled

This keeps `localhost` from landing on one of your projects:

```bash
ls -l /etc/nginx/sites-enabled/default
```

If it's missing:

```bash
sudo ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
```

## 10. Test and reload

```bash
sudo nginx -t && sudo systemctl reload nginx
curl -I http://NAME.test
```

You want `200`, or a `302` whose `Location` is **not** `https://`. Then open `http://NAME.test` in the browser (type the `http://`).

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `ERR_CONNECTION_REFUSED` | App redirects to HTTPS | Step 3 (`http://` in `.env`) and step 5 (`security.php`) |
| Shows a different project | Missing hosts entry, typo in `server_name`, or `default` not enabled | Steps 6, 7 and 9 |
| `502 Bad Gateway` | Wrong PHP socket | `ls /run/php/` and match the `fastcgi_pass` path |
| `403` / permission denied | Wrong ownership | `sudo chown -R $USER:www-data /var/www/NAME` |
| `nginx -t` fails with "duplicate server_name" | Two vhosts use the same domain | Use a unique `NAME.test` |
| `nginx -t` fails with "conflicting server name" | Same domain in two files | `sudo nginx -T \| grep -n "server_name"` and remove the duplicate |
| Anything else | | `sudo tail -n 20 /var/log/nginx/error.log` |

## Useful commands

```bash
# List enabled sites
ls -l /etc/nginx/sites-enabled/

# Show every server block with its domain and root
sudo nginx -T 2>/dev/null | grep -nE "server_name|default_server|^\s*root "

# Disable a site without deleting it
sudo rm /etc/nginx/sites-enabled/NAME.test && sudo systemctl reload nginx
```

---

## Optional: one-command script

Save as `~/new-vhost.sh`, then run `bash ~/new-vhost.sh NAME REPO_URL`. It runs the conflict checks, then does steps 1, 6, 7, 8 and 10. You still need to do steps 2 to 5 yourself (`.env`, database, `security.php`).

```bash
#!/usr/bin/env bash
set -e
NAME="$1"; REPO="$2"
[ -z "$NAME" ] || [ -z "$REPO" ] && { echo "Usage: $0 NAME REPO_URL"; exit 1; }

# Conflict checks
[ -e "/var/www/$NAME" ] && { echo "ERROR: /var/www/$NAME already exists"; exit 1; }
[ -e "/etc/nginx/sites-available/$NAME.test" ] && { echo "ERROR: nginx file already exists"; exit 1; }
grep -qw "$NAME.test" /etc/hosts && { echo "ERROR: $NAME.test already in /etc/hosts"; exit 1; }

# Clone
sudo git clone "$REPO" "/var/www/$NAME"
sudo chown -R "$USER":www-data "/var/www/$NAME"

# Hosts
echo "127.0.0.1 $NAME.test" | sudo tee -a /etc/hosts >/dev/null

# nginx vhost
sudo tee "/etc/nginx/sites-available/$NAME.test" >/dev/null <<EOF
server {
    listen 80;
    listen [::]:80;

    server_name $NAME.test;

    root /var/www/$NAME/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -s "/etc/nginx/sites-available/$NAME.test" "/etc/nginx/sites-enabled/$NAME.test"
[ -e /etc/nginx/sites-enabled/default ] || sudo ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

sudo nginx -t && sudo systemctl reload nginx
echo "Done. Now: edit .env, create the database, add $NAME.test to config/security.php"
echo "Then test: curl -I http://$NAME.test"
```
