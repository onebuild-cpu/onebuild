# LMDE Local Development Cheatsheet

**Complete guide for nginx, MariaDB, PHP, phpMyAdmin, and per-project vhosts on LMDE (Debian 13 "Trixie" base)**

---

## Table of Contents

1. [Installation](#1-installation)
2. [Nginx — start / stop / restart](#2-nginx--start--stop--restart)
3. [MariaDB — start / stop / restart](#3-mariadb--start--stop--restart)
4. [Per-project vhost setup](#4-per-project-vhost-setup)
5. [Quick "new project" one-liner](#5-quick-new-project-one-liner)
6. [Maintenance commands](#6-maintenance-commands)
7. [One-line cheatsheet](#7-one-line-cheatsheet)
8. [Bonus — self-signed HTTPS vhost](#8-bonus--self-signed-https-vhost)
9. [Service reference table](#9-service-reference-table)
10. [Typical workflow](#10-typical-workflow)

---

## 1. Installation

### Install everything at once

```bash
sudo apt update
sudo apt install -y nginx mariadb-server php php-fpm php-mysql phpmyadmin
```

### Install individually

```bash
# Nginx web server
sudo apt install -y nginx

# MariaDB server
sudo apt install -y mariadb-server

# PHP + extensions needed for MariaDB and common apps
sudo apt install -y php php-fpm php-mysql php-cli php-curl php-mbstring php-xml php-zip

# phpMyAdmin (select Apache2 when prompted, or skip web server reconfig)
sudo apt install -y phpmyadmin
```

### Enable services on boot

```bash
sudo systemctl enable nginx
sudo systemctl enable mariadb
sudo systemctl enable php8.4-fpm   # adjust version to match `ls /run/php/`
```

### Verify installation

```bash
nginx -v
mariadb --version
php -v
ls /run/php/                       # shows your PHP-FPM socket name
```

### Secure MariaDB (optional but recommended)

```bash
sudo mariadb-secure-installation
```

---

## 2. Nginx — start / stop / restart

```bash
sudo systemctl start nginx      # start
sudo systemctl stop nginx       # stop
sudo systemctl restart nginx    # restart (full stop+start)
sudo systemctl reload nginx     # reload config WITHOUT dropping connections
sudo systemctl status nginx     # check if running
```

### After editing any nginx config

```bash
sudo nginx -t                   # syntax check — ALWAYS run first
sudo systemctl reload nginx     # apply changes
```

### Logs

```bash
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log
```

---

## 3. MariaDB — start / stop / restart

```bash
sudo systemctl start mariadb
sudo systemctl stop mariadb
sudo systemctl restart mariadb
sudo systemctl status mariadb
sudo systemctl reload mariadb   # reload grants/config without full restart
```

### Quick DB access

```bash
sudo mysql                      # socket auth (no password, uses your Linux user)
mysql -u root -p                # password auth
```

### Create a database + user per project

```bash
sudo mysql
```

```sql
CREATE DATABASE myproject CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'myproject'@'localhost' IDENTIFIED BY 'strongpassword';
GRANT ALL PRIVILEGES ON myproject.* TO 'myproject'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Reset root password (if forgotten)

```bash
sudo systemctl stop mariadb
sudo mariadbd --skip-grant-tables --skip-networking &
mysql -u root
```

```sql
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YourNewPassword';
EXIT;
```

```bash
sudo mysqladmin shutdown
sudo systemctl start mariadb
```

---

## 4. Per-project vhost setup

### Folder convention

```
/var/www/
├── onebuild/        → http://onebuild.test
├── myproject/       → http://myproject.test
└── another/         → http://another.test
```

### Step 1 — Create the project folder

```bash
sudo mkdir -p /var/www/myproject
echo "<?php echo 'Hello from myproject';" | sudo tee /var/www/myproject/index.php
sudo chown -R $USER:www-data /var/www/myproject
sudo chmod -R 775 /var/www/myproject
```

### Step 2 — Create the nginx vhost

```bash
sudo nano /etc/nginx/sites-available/myproject
```

Paste (adjust `myproject`, `root`, and PHP socket version):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name myproject.test;

    root /var/www/myproject;
    index index.php index.html;

    access_log /var/log/nginx/myproject.access.log;
    error_log  /var/log/nginx/myproject.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;   # ← adjust to your PHP version
    }

    location ~ /\.ht {
        deny all;
    }
}
```

> **Find your PHP-FPM socket version:**
> ```bash
> ls /run/php/
> ```
> You'll see something like `php8.4-fpm.sock` — use that exact filename.

### Step 3 — Enable the site + test config

```bash
sudo ln -s /etc/nginx/sites-available/myproject /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Step 4 — Map the domain in `/etc/hosts`

```bash
echo "127.0.0.1 myproject.test" | sudo tee -a /etc/hosts
```

### Step 5 — Browse

```
http://myproject.test
```

You should see `Hello from myproject`.

---

## 5. Quick "new project" one-liner

Clones an existing vhost, renames it, and wires it up:

```bash
PROJ=myproject
sudo mkdir -p /var/www/$PROJ && sudo chown -R $USER:www-data /var/www/$PROJ
sudo cp /etc/nginx/sites-available/onebuild /etc/nginx/sites-available/$PROJ
sudo sed -i "s/onebuild/$PROJ/g" /etc/nginx/sites-available/$PROJ
sudo ln -s /etc/nginx/sites-available/$PROJ /etc/nginx/sites-enabled/
echo "127.0.0.1 $PROJ.test" | sudo tee -a /etc/hosts
sudo nginx -t && sudo systemctl reload nginx
```

---

## 6. Maintenance commands

```bash
# List enabled sites
ls -l /etc/nginx/sites-enabled/

# Disable a site (without deleting config)
sudo rm /etc/nginx/sites-enabled/myproject

# Delete a site completely
sudo rm /etc/nginx/sites-enabled/myproject
sudo rm /etc/nginx/sites-available/myproject
sudo systemctl reload nginx

# See which vhost a request hits
sudo tail -f /var/log/nginx/access.log

# Reload after any config change
sudo nginx -t && sudo systemctl reload nginx
```

---

## 7. One-line cheatsheet

```bash
# Nginx
sudo systemctl restart nginx && sudo nginx -t

# MariaDB
sudo systemctl restart mariadb

# PHP-FPM (adjust version)
sudo systemctl restart php8.4-fpm

# New project folder
mkdir -p /var/www/newproj && sudo chown -R $USER:www-data /var/www/newproj
```

---

## 8. Bonus — self-signed HTTPS vhost

Generate a self-signed certificate:

```bash
sudo mkdir -p /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/myproject.key \
  -out /etc/nginx/ssl/myproject.crt \
  -subj "/CN=myproject.test"
```

Add to your vhost (or a new one):

```nginx
server {
    listen 443 ssl;
    server_name myproject.test;

    ssl_certificate     /etc/nginx/ssl/myproject.crt;
    ssl_certificate_key /etc/nginx/ssl/myproject.key;

    root /var/www/myproject;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    }
}

# Optional: redirect HTTP → HTTPS
server {
    listen 80;
    server_name myproject.test;
    return 301 https://$host$request_uri;
}
```

Reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

> Your browser will warn about the self-signed cert — accept it for local dev.

---

## 9. Service reference table

| Service | Start | Stop | Restart | Reload | Status |
|---|---|---|---|---|---|
| nginx | `systemctl start nginx` | `systemctl stop nginx` | `systemctl restart nginx` | `systemctl reload nginx` | `systemctl status nginx` |
| mariadb | `systemctl start mariadb` | `systemctl stop mariadb` | `systemctl restart mariadb` | `systemctl reload mariadb` | `systemctl status mariadb` |
| php-fpm | `systemctl start php8.4-fpm` | `systemctl stop php8.4-fpm` | `systemctl restart php8.4-fpm` | `systemctl reload php8.4-fpm` | `systemctl status php8.4-fpm` |

*(All prefixed with `sudo` in practice. Adjust `php8.4` to your installed version.)*

---

## 10. Typical workflow

| Task | Command |
|---|---|
| New project | Steps 1–4 in section 4 |
| Reload nginx after edits | `sudo nginx -t && sudo systemctl reload nginx` |
| Restart nginx | `sudo systemctl restart nginx` |
| Restart MariaDB | `sudo systemctl restart mariadb` |
| Check nginx errors | `sudo tail -f /var/log/nginx/myproject.error.log` |
| Check PHP-FPM | `sudo systemctl status php8.4-fpm` |
| Open phpMyAdmin | `http://localhost/phpmyadmin` |

---

**Adjust the PHP version (`8.4`) wherever it ppears if your `ls /run/php/` shows a different one.**