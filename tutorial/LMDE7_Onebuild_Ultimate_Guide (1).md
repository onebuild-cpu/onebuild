---
title: "The Ultimate LMDE 7 + Onebuild Guide"
subtitle: "From a blank PC to a live website on Dewaweb"
---

# The Ultimate LMDE 7 + Onebuild Guide

**Install, debloat, and prepare LMDE 7 "Gigi" - build a local PHP server - learn the Onebuild MVC by building a blog - put your code on GitHub - deploy to Dewaweb shared hosting**

Target machine: Core i3 (3rd gen) / 4 GB DDR3 / GeForce 8600 GT
Base system: Linux Mint Debian Edition 7 "Gigi" (built on Debian 13 "trixie")
Production host: Dewaweb (cPanel + LiteSpeed Enterprise)

---

## How to Use This Book

* Work through the chapters **in order**. Each chapter ends with a checkpoint. Do not skip a checkpoint: the next chapter assumes it is true.
* Lines starting with `$` are typed in a terminal. Do not type the `$`.
* Text in `<angle brackets>` is something you replace with your own value.
* Open a terminal with **Ctrl+Alt+T**.
* Your normal user needs `sudo` rights (the default on LMDE).
* Chapter 12 is a printed tutorial: every code file is labelled `FILE: ...` and says NEW FILE or EDIT FILE.

## The Journey in Four Parts

| Part | Chapters | Result |
|---|---|---|
| I. Install and prepare LMDE | 1 - 7 | A lean, fast desktop with your apps and ZRAM |
| II. The local web server | 8 - 10 | Nginx, PHP 8.5, MariaDB, and `name.test` domains |
| III. Learn Onebuild | 11 - 13 | Onebuild running locally, a full blog (CRUD) built by you, and your own new project |
| IV. Git, production, deployment | 14 - 18 | Your code on GitHub and your site live on Dewaweb |

## What You Will End Up With

| Category | Software |
|---|---|
| Desktops | Cinnamon (default) and XFCE |
| Editors / IDE | Mousepad, Visual Studio Code |
| Browser | Chromium (Google Chrome optional) |
| Media | VLC, Cheese |
| Office | LibreOffice Writer, Calc, Impress only |
| Web stack | Nginx, PHP 8.5 (FPM), MariaDB, Adminer |
| Memory | ZRAM compressed swap |
| Dev workflow | Git, SSH key, `name.test` local domains |
| Framework | Onebuild (simple PHP MVC with Bootstrap and jQuery) |
| Hosting | Dewaweb shared hosting (cPanel, LiteSpeed) |

## Table of Contents

**Part I: Install and Prepare LMDE**

0. Read This First
1. Install LMDE 7 on the Computer
2. Prepare the System and Take a Snapshot
3. Install the Applications You Want
4. Install the XFCE Desktop
5. Debloat: Remove Everything Else
6. Services and Hardware Notes
7. ZRAM: Compressed Swap in RAM

**Part II: The Local Web Server**

8. Nginx, LiteSpeed, and Dewaweb: What Is Really Different
9. Web Stack: Nginx, PHP 8.5, MariaDB
10. Local Domains: `anything.test`

**Part III: Learn Onebuild**

11. Pull the Onebuild Repository and Run It
12. Learn the Onebuild MVC: Build a Blog (CRUD Tutorial)
13. Create Your Own New PHP Project

**Part IV: Git, Production, and Deployment**

14. Git and GitHub: Create a Repo and Push
15. Prepare Onebuild for Production
16. Deploy to Dewaweb Shared Hosting
17. Daily Routine
18. Troubleshooting and Recovery

**Appendices**

- A. Command Cheat Sheet
- B. How to Print This Book

<div style="page-break-after: always;"></div>

# Chapter 0: Read This First

## 0.1 Why we do NOT remove literally everything

The desktop itself (Cinnamon), the file manager (Nemo), the terminal, the network manager, the login screen, and the update tools are all "applications". If you purge them, the next boot ends at a black screen or a text prompt.

So the rule of this book is:

> **Remove every user application. Keep the minimum system that makes the computer usable.**

**What we keep on purpose:**

| Kept | Reason |
|---|---|
| Cinnamon, Nemo, GNOME Terminal | Your desktop, file manager, terminal |
| LightDM + Slick Greeter | Login screen |
| NetworkManager | Internet connection |
| Update Manager, Timeshift | Safe updates and snapshots |
| System Monitor, Settings | You need to watch RAM |
| CUPS (printing) | You want to **print this book** |

## 0.2 Important facts about LMDE

1. LMDE 7 is built on **Debian 13 "trixie"**.
2. LMDE reports its own codename, `gigi`, in some commands (for example `lsb_release -sc`). Third-party repositories do **not** know `gigi`. Whenever a repository asks for a codename, **type `trixie`**. This book already does that.
3. Debian 13 ships **PHP 8.4**. PHP 8.5 comes from the trusted third-party repository `packages.sury.org` (maintained by the person who packages PHP for Debian). Chapter 9 adds it.

## 0.3 About your hardware

* **4 GB RAM** is tight with a browser plus an IDE plus a database. That is why this book uses ZRAM, light database settings, and starts the web stack only when you need it.
* **GeForce 8600 GT**: use the default open-source **nouveau** driver. The only proprietary driver for this card (340.xx) does not work with modern kernels. Do not try to install it.
* If Chromium or VS Code shows flickering or a blank window, see Section 6.3 (disable GPU acceleration).

## 0.4 About the tutorial in Chapter 12

Chapter 12 is the "Build a Blog with Onebuild" CRUD tutorial. **All the PHP, SQL, and HTML code is exactly as in your tutorial file.** Only a few lines about the working environment (where to find the terminal, which database tool to use, where the error log is, how to enable the `gd` extension) were adjusted so they match this LMDE setup.

## 0.5 Honest limits of this book

* The commands for LMDE, Nginx, PHP, MariaDB, ZRAM, and Git are standard and explained step by step, but you should still read each simulation or test output before moving on.
* **Dewaweb:** it is publicly documented as cPanel hosting on LiteSpeed Enterprise with selectable PHP versions. Which exact PHP versions your plan offers (for example whether 8.5 is already available), and whether your plan includes SSH or cPanel's Git tool, **depends on your plan**. Chapter 16 tells you where to look and gives a method that works even if you have none of these.
* The Onebuild repository's own `.htaccess` file was not visible while writing this book. Chapters 8 and 16 therefore include tests that prove your site is safe, rather than assuming.

<div style="page-break-after: always;"></div>

# PART I: INSTALL AND PREPARE LMDE

# Chapter 1: Install LMDE 7 on the Computer

> **Warning.** The "automated installation" **erases the whole disk**. Copy anything you want to keep to another disk or a USB drive first.

## 1.1 Check the requirements

| Item | Needed | Your machine |
|---|---|---|
| CPU | 64-bit only | Core i3 3rd gen is 64-bit: OK |
| RAM | 2 GB minimum, 4 GB recommended | 4 GB: OK |
| Disk | 20 GB minimum, 100 GB recommended | Check your disk size |
| Screen | 1024 x 768 | OK |

LMDE 7 does not exist in a 32-bit version.

## 1.2 Download the ISO

1. On another computer (or your current system) open **https://linuxmint.com/download_lmde.php**.
2. Choose **LMDE 7 "Gigi" Cinnamon 64-bit** and download the `.iso` file (about 2.8 GB). Pick a mirror near you (Asia).
3. On that same page, find the official **SHA256 checksum** for the ISO.

## 1.3 Verify the download

A damaged download causes strange installer errors. In a terminal, go to the folder with the ISO and run:

```bash
$ sha256sum lmde-7-cinnamon-64bit.iso
```

(Use the real file name.) Compare the long result with the checksum on the official page. They must be **identical**. If they differ, download again. On Windows, use PowerShell: `Get-FileHash <file> -Algorithm SHA256`.

## 1.4 Make the installation USB stick

You need a USB stick of **at least 4 GB**. Everything on it will be erased.

| Tool | Works on | Notes |
|---|---|---|
| balenaEtcher | Windows, macOS, Linux | Easiest: choose ISO, choose USB, Flash |
| Rufus | Windows | If asked, choose "DD Image mode" |
| USB Image Writer | Linux Mint | Right-click the ISO, "Make Bootable USB Stick" |

Be very careful to select the **USB stick** and not a hard disk.

## 1.5 Boot the computer from the USB

1. Plug the stick into the desktop and restart it.
2. As soon as the screen lights up, tap the **boot menu key**. It depends on the motherboard: commonly **F12**, **F11**, **F8**, or **Esc**. The **setup** key is usually **Del** or **F2**.
3. Pick the USB stick (if two entries appear, try the one that starts with "UEFI" first, otherwise the plain name).
4. If the computer has UEFI and refuses to boot the stick, enter setup and **disable Secure Boot**, then try again.

## 1.6 The live session

The USB boots into a working LMDE desktop without changing the disk. Check that your keyboard, mouse, and (if you use it) network work.

**Black screen or garbled graphics?** This can happen with the old GeForce 8600 GT.

* If the boot menu has a **compatibility mode** entry, use it.
* Otherwise, at the boot menu press the edit key (**Tab** in BIOS mode, **e** in UEFI mode), add the word `nomodeset` at the end of the line that starts with `linux`, and boot (Enter or F10).
* After installation, the default **nouveau** driver is used and `nomodeset` is no longer needed.

## 1.7 Run the installer

Double-click **Install Linux Mint** on the live desktop and follow the pages:

1. **Language.**
2. **Keyboard layout.** Use the test box to type a few symbols.
3. **Timezone.** For Jakarta choose the entry for Asia / Jakarta.
4. **Installation type:**
   * **Automated installation:** erases the disk and installs everything. Best for a dedicated development computer.
   * **Manual partitioning:** only if you want dual-boot or a custom layout.
   * Leave **disk encryption** off unless you need it; it slows an old CPU.
5. **Swap.** If the installer creates a swap partition or swap file, keep it. ZRAM (Chapter 7) will take priority over it.
6. **User account.** Pick your name, the computer name, a username, and a password. Keep **automatic login off**.
7. **Bootloader (GRUB).** Install it on the **whole disk** (for example `/dev/sda`), not on a partition number such as `/dev/sda1`.
8. Wait. Installation takes roughly 10 to 20 minutes.
9. When it asks, choose **Restart now**, then **remove the USB stick** when told.

## 1.8 First boot

1. Log in and close the Welcome window.
2. Connect to the internet (cable is best).
3. Optional but useful in Indonesia: **Menu -> Software Sources** and choose a fast mirror for the base system.
4. Continue to Chapter 2 (update everything).

**Checkpoint:** LMDE boots to the login screen, you can log in, and the network works.

<div style="page-break-after: always;"></div>

# Chapter 2: Prepare the System and Take a Snapshot

## 2.1 Update everything

```bash
$ sudo apt update && sudo apt upgrade -y
```

If the upgrade says some packages are "kept back", run:

```bash
$ sudo apt full-upgrade -y
```

## 2.2 Install the basic tools used later

```bash
$ sudo apt install -y curl wget git gpg ca-certificates \
    lsb-release apt-transport-https unzip acl
```

## 2.3 Take a Timeshift snapshot (your undo button)

Timeshift must be configured once through its window before the command line works:

1. Open **Menu -> Timeshift**. Run the setup wizard. Choose **RSYNC**, pick the disk for snapshots, finish.
2. Then create the snapshot:

```bash
$ sudo timeshift --create --comments "before debloat" --tags D
```

If anything goes wrong in later chapters, you can restore this snapshot (Chapter 18).

## 2.4 Reboot

```bash
$ sudo reboot
```

**Checkpoint:** system updated, snapshot exists.

<div style="page-break-after: always;"></div>

# Chapter 3: Install the Applications You Want

> **Order matters.** Install what you want first, then remove the rest in Chapter 5. That way apt never removes something you still need.

## 3.1 Mousepad, VLC, Cheese

```bash
$ sudo apt install -y mousepad vlc cheese
```

## 3.2 LibreOffice: Writer, Calc, Impress only

```bash
$ sudo apt install -y libreoffice-writer libreoffice-calc \
    libreoffice-impress libreoffice-gtk3
```

Optional (Indonesian interface and help):

```bash
$ sudo apt install -y libreoffice-l10n-id libreoffice-help-id
```

If LibreOffice was already installed (it is on a fresh LMDE), the other parts (Base, Draw, Math) are removed in Chapter 5.

## 3.3 Browser: Chromium (recommended)

```bash
$ sudo apt install -y chromium
```

**Optional: Google Chrome instead of (or beside) Chromium.** Chrome adds its own update repository.

```bash
$ wget -O chrome.deb \
  https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
$ sudo apt install -y ./chrome.deb
$ rm chrome.deb
```

On 4 GB RAM, using **only one** browser is wise.

## 3.4 Visual Studio Code

```bash
$ wget -O vscode.deb \
  "https://code.visualstudio.com/sha/download?build=stable&os=linux-deb-x64"
$ sudo apt install -y ./vscode.deb
$ rm vscode.deb
```

The package adds Microsoft's repository, so VS Code updates through the normal `apt upgrade`.

**Low-RAM tips for VS Code:**

* Install only the extensions you need. Good PHP set: *PHP Intelephense*, *GitLens* (optional).
* Do not open more than one VS Code window.
* Close the integrated terminal panel when not in use.

**Lighter alternative IDE:** `sudo apt install -y geany` (starts in under a second).

**Checkpoint:** you can launch Mousepad, VLC, Cheese, LibreOffice Writer, Chromium, and VS Code from the menu.

<div style="page-break-after: always;"></div>

# Chapter 4: Install the XFCE Desktop

You will have **both** Cinnamon and XFCE and can choose at login.

```bash
$ sudo apt install -y xfce4 xfce4-terminal xfce4-whiskermenu-plugin
```

* `xfce4` includes the file manager Thunar and the panel.
* If the installer asks which display manager to use, choose **lightdm**.

## 4.1 Switch desktops

1. Log out.
2. On the login screen, click the **session icon** (small icon near your username or the bottom/top bar).
3. Select **Xfce Session** or **Cinnamon**, then log in.

## 4.2 Which one to use?

Cinnamon idles near 600 to 900 MB of RAM; XFCE idles near 400 to 500 MB. On your hardware, **use XFCE for development days** and keep Cinnamon as the fallback.

Check memory at any time:

```bash
$ free -h
```

**Checkpoint:** you can log in to both desktops.

<div style="page-break-after: always;"></div>

# Chapter 5: Debloat: Remove Everything Else

> **Read before starting.** This chapter uses a script that (1) removes only packages that are actually installed, (2) simulates first, and (3) **aborts automatically** if apt would remove anything protected (desktop, network, login, or the apps you chose in Chapter 3).

## 5.1 Look at what is installed (optional)

```bash
$ apt-mark showmanual | less
```

Press `q` to quit.

## 5.2 Create the debloat script

Create the file `~/debloat.sh`:

```bash
$ mousepad ~/debloat.sh
```

Paste exactly this, then save:

```bash
#!/bin/bash
# Usage:  ./debloat.sh          (simulation only)
#         ./debloat.sh --run    (really remove)

CANDIDATES="
firefox-esr firefox thunderbird hexchat hexchat-common
transmission-gtk transmission-common celluloid rhythmbox rhythmbox-data
drawing warpinator webapp-manager gnome-calculator gnome-calendar
simple-scan sticky thingy bulky xviewer xreader pix gnome-font-viewer
mintstick onboard gnome-logs seahorse hypnotix gucharmap mintwelcome
libreoffice-base libreoffice-base-core libreoffice-base-drivers
libreoffice-math libreoffice-draw
"

PROTECT='^(cinnamon|nemo|lightdm|slick-greeter|network-manager|'
PROTECT+='gnome-terminal|mintupdate|timeshift|xfce4|thunar|xorg|'
PROTECT+='xserver|libreoffice-(writer|calc|impress|core|common)|'
PROTECT+='vlc|chromium|mousepad|cheese|nginx|mariadb|php|code|git)'

# 1. Keep only candidates that are really installed
INSTALLED=""
for p in $CANDIDATES; do
  if dpkg-query -W -f='${Status}' "$p" 2>/dev/null | grep -q "ok installed"
  then INSTALLED="$INSTALLED $p"; fi
done
echo "Installed candidates:$INSTALLED"
[ -z "$INSTALLED" ] && { echo "Nothing to remove."; exit 0; }

# 2. Simulate and collect everything apt WOULD remove
sudo apt-get -s purge $INSTALLED | awk '/^Remv/ {print $2}' \
  > /tmp/will_remove.txt
echo "apt would remove $(wc -l < /tmp/will_remove.txt) packages."
echo "Full list saved in /tmp/will_remove.txt"

# 3. Safety check
if grep -E "$PROTECT" /tmp/will_remove.txt; then
  echo
  echo "ABORT: the protected packages above would be removed."
  echo "Remove the candidate that drags them in, then run again."
  exit 1
fi

# 4. Really remove only with --run
if [ "$1" = "--run" ]; then
  sudo apt-get purge -y $INSTALLED
  echo "Done. Now do Section 5.4 (autoremove check)."
else
  echo "Simulation OK. Run again with --run to remove."
fi
```

Make it executable:

```bash
$ chmod +x ~/debloat.sh
```

## 5.3 Run the simulation, then the real thing

```bash
$ ~/debloat.sh
$ less /tmp/will_remove.txt
```

Read the list. If you are happy:

```bash
$ ~/debloat.sh --run
```

**If the script aborts** and the protected line shown is `libreoffice-impress`, then Impress depends on Draw on your system. Delete `libreoffice-draw` from the `CANDIDATES` list and run again. For any other abort, delete the candidate that causes it.

## 5.4 Mark your keepers, then autoremove carefully

`autoremove` deletes packages apt thinks are orphans. Desktop parts installed through a "meta package" can look like orphans, so first mark the important ones as manually installed:

```bash
$ for p in cinnamon cinnamon-session nemo gnome-terminal lightdm \
    slick-greeter network-manager network-manager-gnome mintupdate \
    mintinstall timeshift xfce4 xfce4-terminal thunar vlc chromium \
    mousepad cheese libreoffice-writer libreoffice-calc \
    libreoffice-impress code git; do
    dpkg-query -W -f='${Status}' $p 2>/dev/null | grep -q "ok installed" \
      && sudo apt-mark manual $p
  done
```

Now **simulate** autoremove and read it:

```bash
$ sudo apt-get -s autoremove --purge | grep '^Remv'
```

If you see `cinnamon`, `nemo`, `lightdm`, `network-manager`, `xfce4`, or `mintupdate` in that list, **stop** and run `sudo apt-mark manual <name>` for it. When the list only contains things you do not need:

```bash
$ sudo apt autoremove --purge -y
$ sudo apt clean
```

## 5.5 Optional: remove Flatpak

Skip this if you ever want Flatpak apps.

```bash
$ flatpak uninstall --all -y
$ sudo apt-get -s purge flatpak | grep '^Remv'
```

If the simulation lists `mintinstall` (Software Manager), either keep Flatpak or accept losing Software Manager. If you accept:

```bash
$ sudo apt purge -y flatpak
$ sudo apt autoremove --purge -y
```

## 5.6 Reboot and verify

```bash
$ sudo reboot
```

After login:

```bash
$ free -h
$ systemctl --failed
```

**Checkpoint:** desktop boots, network works, your chosen apps start, and `systemctl --failed` shows nothing important.

<div style="page-break-after: always;"></div>

# Chapter 6: Services and Hardware Notes

## 6.1 Disable background services you do not use

```bash
$ sudo systemctl disable --now avahi-daemon cups-browsed ModemManager
```

* **Do NOT disable `cups`** if you want to print this book from this computer.
* Keep `bluetooth` enabled if you use Bluetooth devices. If not:
  `sudo systemctl disable --now bluetooth`

If a service name is "not found", ignore it. That just means it is not installed.

## 6.2 See what uses your RAM

```bash
$ ps aux --sort=-%mem | head -n 10
```

## 6.3 If Chromium or VS Code looks glitchy (old GPU)

Start them without GPU acceleration:

```bash
$ chromium --disable-gpu
$ code --disable-gpu
```

To make it permanent for VS Code: press **Ctrl+Shift+P**, run **Preferences: Configure Runtime Arguments**, and add:

```json
"disable-hardware-acceleration": true
```

<div style="page-break-after: always;"></div>

# Chapter 7: ZRAM: Compressed Swap in RAM

## 7.1 What ZRAM does

ZRAM creates a swap device **inside RAM** that stores data compressed (typically 2:1 to 3:1). When memory fills up, the system compresses idle pages instead of freezing while it writes to a slow disk. On a 4 GB machine this is the single most useful tweak.

We use `systemd-zram-generator`, the modern Debian method.

## 7.2 Install

```bash
$ sudo apt install -y systemd-zram-generator
```

## 7.3 Configure

```bash
$ sudo mousepad /etc/systemd/zram-generator.conf
```

Contents:

```ini
[zram0]
zram-size = ram / 2
compression-algorithm = zstd
swap-priority = 100
fs-type = swap
```

* `zram-size = ram / 2` creates a 2 GB ZRAM device (about 4 GB of effective swap after compression). RAM is only used as the device fills.
* If you still run out of memory, you can raise it to `ram * 0.75`.
* `swap-priority = 100` makes the system prefer ZRAM over any swap file or swap partition on disk.

## 7.4 Tune the kernel for ZRAM

With ZRAM, a **high** swappiness is correct, because swapping to compressed RAM is cheap.

```bash
$ sudo mousepad /etc/sysctl.d/99-zram.conf
```

Contents:

```ini
vm.swappiness = 100
vm.page-cluster = 0
vm.vfs_cache_pressure = 50
```

## 7.5 Apply and verify

```bash
$ sudo sysctl --system
$ sudo systemctl daemon-reload
$ sudo systemctl start systemd-zram-setup@zram0.service
$ zramctl
$ swapon --show
```

Expected: `zramctl` shows a device `/dev/zram0` with algorithm `zstd`, and `swapon --show` lists `/dev/zram0` with priority `100`.

If nothing appears, reboot once and run the two check commands again.

**Checkpoint:** ZRAM is active at the top of the `swapon --show` list.

<div style="page-break-after: always;"></div>

# PART II: THE LOCAL WEB SERVER

# Chapter 8: Nginx, LiteSpeed, and Dewaweb: What Is Really Different

You asked: *Dewaweb uses LiteSpeed. Is that the same as Nginx? Should I install LiteSpeed on my LMDE instead of Nginx?*

## 8.1 The short answer

* **They are not the same program.** Both are fast, "event-driven" web servers, but they work differently (see 8.2).
* **Do not install LiteSpeed on your 4 GB LMDE.** Keep Nginx. What matters for a smooth deployment is not the brand of the web server, but five things you can control (see 8.3).
* The one real difference that can bite you is **`.htaccess`**: LiteSpeed reads it, Nginx never does. Section 8.4 shows how to handle that.

## 8.2 The three servers side by side

| | Nginx (your LMDE) | LiteSpeed Enterprise (Dewaweb) | OpenLiteSpeed (free edition) |
|---|---|---|---|
| Reads `.htaccess` | **No** | **Yes** (it is a drop-in Apache replacement and loads Apache configuration) | Partly (rewrite rules and a subset, must be enabled) |
| How PHP runs | PHP-FPM | LSAPI (LSPHP) | LSAPI |
| Config style | Text files in `/etc/nginx` | Managed by cPanel/WHM | Own web admin screen |
| Licence | Free, open source | Paid commercial licence (held by the host) | Free, open source |
| Who changes settings | You | Dewaweb and cPanel | You |
| Fit for your 4 GB PC | Good, light | Not offered as a free home install | Possible, but more to learn and maintain |

## 8.3 What actually decides whether your site behaves the same

Forget the server brand and match these five things:

1. **PHP version and extensions.** Your local PHP must be the same major version as the one you select on Dewaweb, and have the same extensions (`pdo_mysql`, `mbstring`, `gd` with WebP, `curl`, `zip`, `intl`). Chapter 10 lets you run a different PHP version per site, so you can test the exact version you will use online.
2. **`.htaccess` rules.** Any rule that matters (routing, blocking files, headers) must exist in an Nginx equivalent locally. See 8.4.
3. **File permissions.** Locally PHP runs as `www-data`. On cPanel/LiteSpeed it runs as **your hosting account's user**. So local needs group-write on `storage`, while online simple `755` folders are enough. Never use `777` online.
4. **Limits.** Upload size, memory, and time limits. Set them locally to match (Chapter 9, Section 9.9) and check them in cPanel.
5. **HTTP vs HTTPS.** Locally you use plain `http://onebuild.test`. Online you use `https://`. Settings that depend on HTTPS (secure cookies, redirects) can behave differently, so always test on a staging subdomain before going live (Section 8.5).

## 8.4 Translating `.htaccess` into Nginx

Open the `.htaccess` file in the root of your Onebuild project and read it. Then use this table. The `newsite` helper from Chapter 10 already includes the first, second, and last rows.

| `.htaccess` idea | Nginx equivalent | Already in `newsite`? |
|---|---|---|
| `RewriteRule` sending everything to `index.php` | `try_files $uri $uri/ /index.php?$query_string;` | Yes |
| Hide dotfiles such as `.env`, `.git` | `location ~ /\.(?!well-known) { deny all; }` | Yes |
| No PHP execution in `uploads` | `location ^~ /uploads/ { location ~ \.php$ { return 403; } }` | Yes |
| `Options -Indexes` (no folder listing) | `autoindex off;` (this is already the default) | Yes (default) |
| `Header set ...` (security headers) | `add_header Name "value" always;` | No |
| `ExpiresByType` (browser cache) | `expires 30d;` inside a `location` for static files | No |
| `php_value upload_max_filesize` | Edit `php.ini` or the FPM pool (Chapter 9) | Done in 9.9 |
| Blocking `app/`, `config/`, `storage/` | Not needed locally, because the site root is the `public` folder | n/a |

Note: when Onebuild adds security headers (for example the nonce rules in the tutorial) from **PHP code**, they work in every server and need nothing here. Only headers written in `.htaccess` need an Nginx copy.

## 8.5 The safety net: a staging subdomain on Dewaweb

Create a free subdomain such as `staging.yourdomain.com` on Dewaweb and deploy there **first** (Chapter 16). It runs the real LiteSpeed server with the real PHP, real `.htaccess` handling, and real HTTPS. Anything your local Nginx cannot show you appears there, before your visitors see it.

## 8.6 When would OpenLiteSpeed make sense?

Only if your project relies heavily on `.htaccess` rules that are hard to translate, or you want an exact copy of production at home. The price on a 4 GB machine is more RAM use, a second admin tool to learn, and manual maintenance. For Onebuild, whose routing is one front controller in the `public` folder, Nginx with the `try_files` line is enough. (If you ever need OpenLiteSpeed, it can be added in a later edition of this guide.)

**Checkpoint:** you know that LiteSpeed is not Nginx, that you keep Nginx, and that you will test on a staging subdomain.

<div style="page-break-after: always;"></div>

# Chapter 9: Web Stack: Nginx, PHP 8.5, MariaDB

## 9.1 Install Nginx and MariaDB

```bash
$ sudo apt install -y nginx mariadb-server
```

## 9.2 Add the PHP 8.5 repository (Sury)

Debian 13 only has PHP 8.4, so add the Sury repository. **We write `trixie` by hand because LMDE's own codename is `gigi`.**

```bash
$ sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb \
    https://packages.sury.org/debsuryorg-archive-keyring.deb
$ sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
$ echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" \
    | sudo tee /etc/apt/sources.list.d/php-sury.list
$ sudo apt update
```

Check that PHP 8.5 is offered:

```bash
$ apt-cache policy php8.5-fpm
```

You should see a "Candidate" starting with `8.5`.

## 9.3 Install PHP 8.5 with FPM and common extensions

```bash
$ sudo apt install -y php8.5-fpm php8.5-cli php8.5-mysql \
    php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip \
    php8.5-intl php8.5-gd php8.5-bcmath
```

Make 8.5 the default `php` command and verify:

```bash
$ sudo update-alternatives --set php /usr/bin/php8.5
$ php -v
```

Optional: Composer (only if a project has a `composer.json`):

```bash
$ sudo apt install -y composer
$ php -v
```

If `php -v` now shows 8.4, run the `update-alternatives` line again.

## 9.4 Make PHP-FPM light on RAM

Edit the pool file:

```bash
$ sudo mousepad /etc/php/8.5/fpm/pool.d/www.conf
```

Find and set these values (they already exist in the file; change them):

```ini
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
```

Restart:

```bash
$ sudo systemctl restart php8.5-fpm
```

## 9.5 Make MariaDB light on RAM

```bash
$ sudo mousepad /etc/mysql/mariadb.conf.d/99-lowmem.cnf
```

Contents:

```ini
[mysqld]
innodb_buffer_pool_size = 128M
key_buffer_size         = 16M
max_connections         = 30
performance_schema      = OFF
```

Restart and secure it:

```bash
$ sudo systemctl restart mariadb
$ sudo mariadb-secure-installation
```

Answers for a development machine: current root password is empty (press Enter); `unix_socket` authentication: **Y**; remove anonymous users: **Y**; disallow remote root login: **Y**; remove test database: **Y**; reload privileges: **Y**.

## 9.6 Create a development database user

On Debian, the MariaDB root account is used through `sudo`, not a password.

```bash
$ sudo mariadb
```

Inside the MariaDB prompt (replace the password):

```sql
CREATE USER 'dev'@'localhost' IDENTIFIED BY 'ChangeThisPassword';
GRANT ALL PRIVILEGES ON *.* TO 'dev'@'localhost';
EXIT;
```

This user can only connect from your own machine (`localhost`), which is fine for local development. Never use these settings on a public server.

## 9.7 Remove the Nginx default site and allow home folder access

```bash
$ sudo rm -f /etc/nginx/sites-enabled/default
$ chmod 711 ~
```

`chmod 711 ~` lets the web server pass *through* your home folder to reach `~/www` without being able to list your files.

## 9.8 Test

```bash
$ sudo systemctl enable --now nginx php8.5-fpm mariadb
$ sudo nginx -t
$ systemctl status nginx php8.5-fpm mariadb --no-pager | grep Active
```

All three should say `active (running)`.

## 9.9 Raise the upload limits (needed for the blog pictures)

PHP's default upload limit is small. Create a small extra settings file:

```bash
$ sudo mousepad /etc/php/8.5/fpm/conf.d/99-dev.ini
```

Contents:

```ini
upload_max_filesize = 8M
post_max_size = 8M
```

Restart PHP-FPM:

```bash
$ sudo systemctl restart php8.5-fpm
```

(Nginx has its own limit, `client_max_body_size`, which the `newsite` helper in Chapter 10 already sets to 8 MB. Without it, Nginx answers "413 Request Entity Too Large" for any upload above 1 MB.)

**Checkpoint:** `php -v` shows 8.5, Nginx config test passes, MariaDB runs.

<div style="page-break-after: always;"></div>

# Chapter 10: Local Domains: `anything.test`

## 10.1 The idea

For each project you want:

1. A project folder, with a `public` folder inside it.
2. A tiny Nginx "site" file saying: *when someone asks for `name.test`, serve this folder*.
3. One line in `/etc/hosts` saying: *`name.test` is this computer*.

`.test` is a domain reserved for testing, so it can never collide with a real website.

We automate all three with one helper command called **`newsite`**.

## 10.2 Create the helper command

```bash
$ sudo mousepad /usr/local/bin/newsite
```

Paste:

```bash
#!/bin/bash
# Usage: newsite <name> <path-to-public-folder> [php-version]
# Example: newsite onebuild ~/www/onebuild/public
#          newsite onebuild ~/www/onebuild/public 8.4
set -e

NAME="$1"
ROOT="$2"
PHPVER="${3:-8.5}"

if [ -z "$NAME" ] || [ -z "$ROOT" ]; then
  echo "Usage: newsite <name> <path-to-public-folder> [php-version]"
  exit 1
fi

ROOT="$(realpath "$ROOT")"
DOMAIN="$NAME.test"
SOCK="/run/php/php${PHPVER}-fpm.sock"
CONF="/etc/nginx/sites-available/$DOMAIN"

if [ ! -d "$ROOT" ]; then
  echo "Folder not found: $ROOT"
  exit 1
fi

if [ ! -S "$SOCK" ]; then
  echo "Note: PHP $PHPVER is not running yet (no $SOCK)."
  echo "Start it with: sudo systemctl start php${PHPVER}-fpm"
fi

cat > "$CONF" <<EOT
server {
    listen 80;
    server_name $DOMAIN;
    root $ROOT;
    index index.php index.html;
    client_max_body_size 8M;

    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log  /var/log/nginx/$DOMAIN.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$SOCK;
    }

    # Never run PHP files inside the uploads folder
    location ^~ /uploads/ {
        location ~ \.php\$ {
            return 403;
        }
    }

    # Hide dotfiles such as .env and .git
    location ~ /\.(?!well-known) {
        deny all;
    }
}
EOT

ln -sf "$CONF" "/etc/nginx/sites-enabled/$DOMAIN"

if ! grep -q "[[:space:]]$DOMAIN\$" /etc/hosts; then
  echo "127.0.0.1 $DOMAIN" >> /etc/hosts
fi

nginx -t
systemctl reload nginx
echo "Ready: http://$DOMAIN  ->  $ROOT  (PHP $PHPVER)"
```

Make it executable:

```bash
$ sudo chmod +x /usr/local/bin/newsite
```

From now on, always run it with `sudo`:

```bash
$ sudo newsite myproject ~/www/myproject/public
```

Then open **http://myproject.test** in the browser.

**Note on the rewrite rule:** the line `try_files $uri $uri/ /index.php?$query_string;` sends every URL that is not a real file to `index.php`. That is how a PHP MVC front controller works. It replaces the `.htaccess` file, which only Apache reads. Nginx ignores `.htaccess`.

## 10.3 Remove a site

```bash
$ sudo rm /etc/nginx/sites-enabled/myproject.test
$ sudo rm /etc/nginx/sites-available/myproject.test
$ sudo mousepad /etc/hosts      # delete the "127.0.0.1 myproject.test" line
$ sudo systemctl reload nginx
```

## 10.4 Create your projects folder

```bash
$ mkdir -p ~/www
```

## 10.5 Choose the PHP version per site

The third (optional) argument of `newsite` picks the PHP version for that one site:

```bash
$ sudo newsite myapp ~/www/myapp/public          # PHP 8.5 (default)
$ sudo newsite myapp ~/www/myapp/public 8.4      # PHP 8.4 for this site
```

The other PHP version must be installed and started (`php8.4-fpm`, see Section 15.2). This is how you test with the same PHP version that your hosting offers.

## 10.6 Install Adminer (your database screen)

Adminer is a single-file database manager, a very light alternative to phpMyAdmin. Install it as a local site called `db.test`:

```bash
$ mkdir -p ~/www/tools/adminer
$ wget -O ~/www/tools/adminer/index.php https://www.adminer.org/latest.php
$ sudo newsite db ~/www/tools/adminer
```

Open **http://db.test**. Log in with:

| Field | Value |
|---|---|
| System | MySQL / MariaDB |
| Server | `localhost` |
| Username | `dev` |
| Password | the one you set in Section 9.6 |

Use it only on your own computer. **Never put Adminer on a live website.**

**Checkpoint:** `newsite` exists, `~/www` exists, and `http://db.test` opens Adminer.

<div style="page-break-after: always;"></div>

# PART III: LEARN ONEBUILD

# Chapter 11: Pull the Onebuild Repository and Run It

Repository: `https://github.com/onebuild-cpu/onebuild`
Described by its author as: *a very simple basic PHP MVC with Bootstrap and jQuery.*

From the repository's file list, the top level contains: `app`, `bin`, `config`, `database`, `public`, `storage`, `tutorial`, `views`, plus `.env.example`, `.gitignore`, `.htaccess`, `dump_file.py` and `project_dump.md`. Important consequences:

* The folder served to the web must be **`public`**, not the project root.
* `.env.example` means you copy it to `.env` and fill in your database details.
* `database` probably holds your SQL schema; `storage` must be writable.
* `tutorial` is worth reading.
* `bin` holds command-line helpers (for example the one that creates the first admin account).

## 11.1 Clone

```bash
$ cd ~/www
$ git clone https://github.com/onebuild-cpu/onebuild.git
$ cd onebuild
$ ls -la
```

Cloning a public repository over HTTPS needs no login.

## 11.2 Read the project's own files

```bash
$ ls tutorial database config bin public
```

Open the files in `tutorial` with Mousepad or VS Code. Check that `public/index.php` exists. It is the front controller: every address goes through it, and it contains the route list you will edit in Chapter 12.

## 11.3 Create the `.env` file

```bash
$ cp .env.example .env
$ mousepad .env
```

Fill in the database name, user (`dev`), and password from Section 9.6. If there is a setting for the site URL or base URL, set it to `http://onebuild.test`. Use the exact key names that exist in the file.

While learning on this computer, also set:

```
APP_ENV=development
```

so error messages show on screen. **Never keep this on the live website** (Chapter 15).

## 11.4 Create the database and import the tables

```bash
$ sudo mariadb -e "CREATE DATABASE onebuild CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$ ls database
```

If `database` contains a `.sql` file (replace the filename):

```bash
$ mariadb -u dev -p onebuild < database/<file>.sql
```

Use the same database name you typed into `.env` (the `DB_NAME` line).

## 11.5 Composer (only if the project has `composer.json`)

```bash
$ ls composer.json && composer install
```

## 11.6 Folders the web server must write to

Git does not store empty folders, so make sure the upload folder exists, then give the web server (`www-data`) write access:

```bash
$ mkdir -p public/uploads/images
$ sudo chgrp -R www-data storage public/uploads
$ chmod -R 775 storage public/uploads
```

## 11.7 Check that PHP can make WebP pictures

The blog in Chapter 12 converts every uploaded picture to WebP. Verify:

```bash
$ php -r 'var_dump(function_exists("imagewebp"));'
```

It must print `bool(true)`. If not: `sudo apt install php8.5-gd` and `sudo systemctl restart php8.5-fpm`.

## 11.8 Publish it as `onebuild.test`

```bash
$ sudo systemctl start nginx php8.5-fpm mariadb
$ sudo newsite onebuild ~/www/onebuild/public
```


(`newsite` is created in Chapter 10.)

Open **http://onebuild.test** in Chromium. The home page should appear.

## 11.9 Create the first administrator

The admin pages you build in Chapter 12 refuse everybody except the roles **root** and **admin**. Create a root account once, in the project folder:

```bash
$ cd ~/www/onebuild
$ php bin/add_root.php "+628123456789" "YourStrongPassword"
```

Replace the number and password with your own. Keep the quotes. Then open **http://onebuild.test/login**, sign in, and you should reach `/dashboard`.

## 11.10 If it does not work

| Symptom | Fix |
|---|---|
| "Server not found" | `grep test /etc/hosts`; run the `newsite` command again |
| 502 Bad Gateway | `sudo systemctl status php8.5-fpm`; start it |
| 403 Forbidden | Home folder permission: `chmod 711 ~` |
| 404 on every page | Wrong folder: the site must point to `.../public` |
| White page / 500 | `sudo tail -n 30 /var/log/nginx/onebuild.test.error.log` |
| Database error | Re-check `.env` values and that the database exists |
| `add_root.php` cannot connect | Same database settings as above; make sure MariaDB is running |

**Checkpoint:** `http://onebuild.test` shows the application and you can sign in at `/login`.

<div style="page-break-after: always;"></div>

# Chapter 12: Learn the Onebuild MVC: Build a Blog (CRUD Tutorial)

> **Environment notes for LMDE (read first).**
> * Where the tutorial says "terminal", press **Ctrl+Alt+T** and run `cd ~/www/onebuild`.
> * Where it mentions a database tool, use **Adminer** at `http://db.test` (installed in Chapter 10). Log in with user `dev` and the password from Section 9.6.
> * Edit files with **VS Code** (`code ~/www/onebuild`) or Mousepad.
> * After a PHP change, just save and reload the page. After a `php.ini` change, run `sudo systemctl restart php8.5-fpm`.
> * Your error log is `/var/log/nginx/onebuild.test.error.log`.
> * The tutorial text starts below. Its code is unchanged.

## Read this first

**What you will build.** A small blog inside your Onebuild site:

- a **Categories** manager (add, list, edit, delete)
- an **Articles** manager (the same four actions, plus a picture upload)
- two **public pages** where visitors read the articles

Programmers call the four actions **CRUD**: **C**reate, **R**ead, **U**pdate, **D**elete. Almost every website is CRUD on different things (products, customers, invoices). Learn it once and you can reuse it forever.

**How long it takes.** About 3 to 4 hours, in several sessions. The site works at every CHECKPOINT, so you can stop after any Part and continue another day.

**How to follow this paper**

1. Every file starts with a label such as `FILE: app/Models/CategoryModel.php` and says **NEW FILE** or **EDIT FILE**.
2. Type the code exactly as printed. PHP is strict about these characters: `;` `{ }` `( )` `[ ]` `' '` `$` `->` `=>`. One missing character gives an error page.
3. Indentation (the spaces at the start of a line) is only for reading. Keep it anyway, it helps you spot mistakes.
4. Lines that start with `//` or `*`, or that sit between `/**` and `*/`, are comments written for humans. You may skip typing them.
5. Names are case-sensitive. The file `AdminCategoryController.php` must contain `class AdminCategoryController`. Same letters, same capitals.
6. Use a code editor such as VS Code (free). Do not use Word.
7. **Typo detector.** After you save a PHP file, open a terminal in your project folder (press Ctrl+Alt+T, then type `cd ~/www/onebuild`) and run `php -l app/Helpers/BlogHelper.php`, using the path of the file you just made. It answers `No syntax errors detected`, or tells you which line has the problem. It works for view files too.

## Before you start (checklist)

Tick each box.

- [ ] Your Onebuild home page opens in the browser.
- [ ] You can sign in at `/login` and reach `/dashboard`.
- [ ] Your account has the role **root** or **admin**. The admin pages you will build refuse everybody else. (A root account is created by running `php bin/add_root.php "+628123456789" "YourPassword"` once in the project folder.)
- [ ] While learning on your own computer, open the file `.env` and set `APP_ENV=development`. Error messages then show on screen instead of a blank page. Never leave this on a real website: set it back to `production` when you publish.
- [ ] You have a database tool (Adminer at `http://db.test`, installed in Chapter 10) and you know which database Onebuild uses. It is the `DB_NAME` line in `.env`.

## The big picture (read once, 5 minutes)

Think of a restaurant.

- **Route** = the menu board at the door. It says "this address is handled by that waiter". Onebuild keeps its routes in `public/index.php`.
- **Controller** = the waiter. It receives the request, checks you are allowed, fetches what is needed and brings it to the table. Controllers live in `app/Controllers/`.
- **Model** = the pantry keeper. The only one who touches the database. Models live in `app/Models/`.
- **View** = the plate. The HTML page the visitor sees. Views live in `views/`.

What happens when you open the page `/dashboard/categories`:

```text
Browser asks for  /dashboard/categories
        |
        v
public/index.php    route list says: AdminCategoryController -> index()
        |
        v
AdminCategoryController::index()
        1. Is this person an admin?             (requireAdmin)
        2. Ask the model for all categories     (CategoryModel -> database)
        3. Hand the list to the view
        |
        v
views/admin/categories/index.php   builds the HTML page and sends it back
```

**One rule you will see everywhere.** Anything with a form needs **two** controller methods. One **shows** the form (the browser *asks* for a page: this is called GET). The other **receives** the filled form (the browser *sends* data: this is called POST).

- Create = `create()` shows the empty form, `store()` saves it
- Read = `index()` shows the list
- Update = `edit()` shows the filled form, `update()` saves it
- Delete = `delete()` removes the item (a small form with a Delete button triggers it)

**Words you will meet**

- **Slug**: the web-friendly version of a name. "My First Post!" becomes `my-first-post` and is used in the address `/blog/my-first-post`.
- **Session**: the server's memory of who is logged in.
- **Flash message**: a green or red message shown once (for example "Category created.") and then forgotten. Onebuild's top menu already displays them. You only write `$_SESSION['success'] = '...'` or `$_SESSION['error'] = '...'`.
- **CSRF token**: a secret code hidden inside every form. It proves the form came from your own site and not from a trick page. Every form you build contains one, and every action that receives a form checks it.
- **Layout**: a file holding the parts that every page shares (top menu, sidebar, footer).

**What every Onebuild model can already do** (free, you inherit it):

- `findAll('name ASC')`: all rows, sorted
- `findById(5)`: one row, or `null` when it does not exist
- `insert([...])`: add a row (the dates are filled in for you)
- `update(5, [...])`: change a row
- `delete(5)`: remove a row
- `query('SELECT ...', [values])`: your own SQL, returns many rows
- `queryOne('SELECT ...', [values])`: your own SQL, returns one row or `null`

**Files you will make (NEW) or change (EDIT)**

```text
database (Adminer)                               2 new tables
app/Helpers/BlogHelper.php                      NEW
app/Models/CategoryModel.php                    NEW
app/Models/ArticleModel.php                     NEW
app/Controllers/AdminCategoryController.php     NEW
app/Controllers/AdminArticleController.php      NEW
app/Controllers/BlogController.php              NEW
views/layouts/admin_top.php                     NEW
views/layouts/admin_bottom.php                  NEW
views/admin/categories/  _form, create, edit, index    NEW (4 files)
views/admin/articles/    _form, create, edit, index    NEW (4 files)
views/blog/              index, show                   NEW (2 files)
views/dashboard/_sidebar.php                    EDIT
views/layouts/navbar.php                        EDIT
public/index.php                                EDIT
```

## Part 1: The database tables

A table is like an Excel sheet: columns across the top, one row for each item.

**Step 1.1** Open Adminer (`http://db.test`), log in with user `dev`, and click your Onebuild database.

**Step 1.2** Click **SQL command** in the left menu. Paste this, then press **Execute**.

```sql
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

The lines that matter:

- `id ... AUTO_INCREMENT`: the database numbers every row 1, 2, 3... for you.
- `slug ... UNIQUE`: two rows can never have the same slug.
- `category_id`: the link that says which category an article belongs to.
- `ON DELETE RESTRICT`: the database refuses to delete a category that still has articles. This protects you from wiping out articles by accident.
- `created_at`, `updated_at`: dates that fill themselves in.

**CHECKPOINT 1.** In the list of tables you now see `categories` and `articles`, both empty.

## Part 2: The helper file

A **helper** is a toolbox of small functions that belong to no single page. Ours has five tools:

- `makeSlug()` turns "My First Post!" into `my-first-post`.
- `imageFolder()` says where pictures are stored.
- `saveImageAsWebp()` checks an uploaded picture, converts it to WebP (a small, fast format) and saves it under a random name.
- `deleteImage()` removes a picture file.
- `excerpt()` cuts a long text short for the list page.

**Step 2.1** Create a new folder named `Helpers` inside the `app` folder.

**Step 2.2** Create the file below.

**FILE: `app/Helpers/BlogHelper.php`  (NEW FILE)**

```php
<?php

namespace App\Helpers;

use Exception;

class BlogHelper
{
    /**
     * Turns any text into a clean web-address word.
     * Example: "My First Post!" becomes "my-first-post"
     */
    public static function makeSlug(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9 -]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');

        if ($text === '') {
            return 'n-a';
        }

        return $text;
    }

    /**
     * The folder on the server where article images are stored.
     */
    public static function imageFolder(): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/images';
    }

    /**
     * Takes an uploaded image (JPG, PNG or WebP), converts it to WebP,
     * saves it in the folder, and returns the new file name.
     * If something is wrong it throws an Exception with a friendly message.
     */
    public static function saveImageAsWebp(array $file, string $folder): string
    {
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if ($file['size'] > $maxSize) {
            throw new Exception('The image is too big. Maximum size is 2 MB.');
        }

        $info = getimagesize($file['tmp_name']);
        if ($info === false) {
            throw new Exception('The uploaded file is not a valid image.');
        }

        $mime = $info['mime'];
        $image = false;

        if ($mime === 'image/jpeg') {
            $image = imagecreatefromjpeg($file['tmp_name']);
        } elseif ($mime === 'image/png') {
            $image = imagecreatefrompng($file['tmp_name']);
            if ($image !== false) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/webp') {
            $image = imagecreatefromwebp($file['tmp_name']);
        } else {
            throw new Exception('Only JPG, PNG and WebP images are allowed.');
        }

        if ($image === false) {
            throw new Exception('The image could not be read. Please try another file.');
        }

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = 'img_' . bin2hex(random_bytes(8)) . '.webp';
        $saved = imagewebp($image, $folder . '/' . $filename, 80);

        if ($saved === false) {
            throw new Exception('The image could not be saved.');
        }

        return $filename;
    }

    /**
     * Deletes an image file from the folder (does nothing if it is not there).
     */
    public static function deleteImage(string $folder, string $filename): void
    {
        if ($filename === '') {
            return;
        }

        $path = $folder . '/' . basename($filename);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Returns the first part of a long text, for the blog list page.
     */
    public static function excerpt(string $text, int $length = 120): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }
}
```

What to notice:

- `namespace App\Helpers;` tells Onebuild where the file lives. The namespace `App\Helpers` means the folder `app/Helpers`. Because of this, you never have to write `require` for your own classes: Onebuild finds them by name.
- `static` means you can use the tool directly, without making an object first: `BlogHelper::makeSlug('Hello')`.
- `throw new Exception('...')` rings an alarm that carries a message. The controller will catch it and show the message to the user.
- Images are saved in `public/uploads/images`. That folder already exists in Onebuild and already blocks the running of any program placed inside it, which keeps uploads safe.

Run the typo detector: `php -l app/Helpers/BlogHelper.php`

## Part 3: The models

A model is the only place that talks to the database. Both of ours inherit all the free methods (`findAll`, `findById`, `insert`, `update`, `delete`) and add a few of their own.

**FILE: `app/Models/CategoryModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class CategoryModel extends Model
{
    protected string $table = 'categories';

    /**
     * Is this slug already used by another category?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * How many articles belong to this category?
     */
    public function countArticles(int $categoryId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM articles WHERE category_id = ?",
            [$categoryId]
        );

        return (int) $row['total'];
    }
}
```

**FILE: `app/Models/ArticleModel.php`  (NEW FILE)**

```php
<?php

namespace App\Models;

use App\Core\Model;

class ArticleModel extends Model
{
    protected string $table = 'articles';

    /**
     * Is this slug already used by another article?
     */
    public function slugExists(string $slug, int $ignoreId = 0): bool
    {
        $row = $this->queryOne(
            "SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1",
            [$slug, $ignoreId]
        );

        return $row !== null;
    }

    /**
     * All articles, newest first, together with their category name.
     */
    public function getAllWithCategory(): array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                ORDER BY articles.id DESC";

        return $this->query($sql);
    }

    /**
     * One article (found by its slug) together with its category name.
     */
    public function findWithCategoryBySlug(string $slug): ?array
    {
        $sql = "SELECT articles.*, categories.name AS category_name
                FROM articles
                LEFT JOIN categories ON articles.category_id = categories.id
                WHERE articles.slug = ?
                LIMIT 1";

        return $this->queryOne($sql, [$slug]);
    }
}
```

What to notice:

- `extends Model` means "start with everything a normal Onebuild model can do".
- `protected string $table = 'articles';` tells the model which table it manages.
- Each `?` in the SQL is a blank that is filled safely with the matching value from the list `[...]`. **Never paste user text directly into SQL.** The `?` blanks are what protect your site from SQL injection (a classic hacking trick).
- `LEFT JOIN` borrows the category name from the other table, so the list page can show a name instead of a number.
- `?array` means "returns an array, or `null` when nothing was found".

Run the typo detector on both files.

## Part 4: Shared layout pieces

Every admin page needs the same frame: header, top menu, sidebar, and a footer. Instead of repeating 15 lines in eight files, we make two small files: one **opens** the frame, one **closes** it. Each page puts its own content in between, like a picture in a picture frame.

**FILE: `views/layouts/admin_top.php`  (NEW FILE)**

```php
<?php
// Opens every admin page: header + top menu + sidebar + start of the main area.
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/navbar.php';
?>
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css" nonce="<?= $nonce ?>">

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/../dashboard/_sidebar.php'; ?>

        <section class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
```

**FILE: `views/layouts/admin_bottom.php`  (NEW FILE)**

```php
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
```

What to notice:

- `require_once` means "paste that file here".
- `<?= $base ?>` prints your website's base address. Put it in front of every link and every file path, so the site works no matter which folder or domain it runs on.
- `<?= htmlspecialchars(...) ?>` prints text safely. **Always** wrap text that comes from the database or from a visitor in `htmlspecialchars()`. Without it a visitor could inject harmful code into your page.
- `$nonce` is a one-time pass that Onebuild's security rules require on every inline `<script>` and `<link>`. Remember this: **an inline `<script>` without `nonce="<?= $nonce ?>"` is blocked by the browser.**

**Step 4.3** Add two links to the sidebar.

**FILE: `views/dashboard/_sidebar.php`  (EDIT FILE)**

Open the file and find this existing block (the "Overview" menu item):

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
```

Directly **under** that block (after its closing `</li>`) add:

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/categories">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'articles' ? 'active' : '' ?>" href="<?= $base ?>/dashboard/articles">
                    <i class="bi bi-journal-text"></i> Articles
                </a>
            </li>
```

(The `? 'active' : ''` part copies the style already used in that file: it adds the word `active` to highlight the menu item of the page you are on.)

**CHECKPOINT 4.** Sign in and open `/dashboard`. The sidebar now shows **Categories** and **Articles**. Clicking them gives a "404" page for now. That is correct: you have not made those pages yet.

## Part 5: Category CRUD

We build the complete cycle for categories first because it is the smallest one. Articles (Part 6) use exactly the same pattern, with a few extras.

**Step 5.1** The controller. One class with six actions you can see in the browser (`index`, `create`, `store`, `edit`, `update`, `delete`) plus two helper methods that tidy up and check the form data.

**FILE: `app/Controllers/AdminCategoryController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\CategoryModel;

class AdminCategoryController extends Controller
{
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all categories
     */
    public function index(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');

        $this->view('admin/categories/index', [
            'pageTitle'   => 'Categories',
            'currentPage' => 'categories',
            'categories'  => $categories,
        ]);
    }

    /**
     * Page: empty form to add a category
     */
    public function create(): void
    {
        $this->requireAdmin();

        $this->view('admin/categories/create', [
            'pageTitle'   => 'New Category',
            'currentPage' => 'categories',
            'category'    => ['name' => '', 'slug' => ''],
        ]);
    }

    /**
     * Action: save the new category (the form sends data here)
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->readForm();
        $error = $this->validateCategory($category, 0);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/create', [
                'pageTitle'   => 'New Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->insert([
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category created.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Page: form filled with the current values of one category
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/categories/edit', [
            'pageTitle'   => 'Edit Category',
            'currentPage' => 'categories',
            'category'    => $category,
        ]);
    }

    /**
     * Action: save the changes (the edit form sends data here)
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->categoryModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $category = $this->readForm();
        $category['id'] = $id;
        $error = $this->validateCategory($category, $id);

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/categories/edit', [
                'pageTitle'   => 'Edit Category',
                'currentPage' => 'categories',
                'category'    => $category,
            ]);
            return;
        }

        $this->categoryModel->update($id, [
            'name' => $category['name'],
            'slug' => $category['slug'],
        ]);

        $_SESSION['success'] = 'Category updated.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Action: delete one category
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/categories');
            return;
        }
        CsrfProtection::requireValidToken();

        $category = $this->categoryModel->findById($id);
        if ($category === null) {
            $_SESSION['error'] = 'Category not found.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $total = $this->categoryModel->countArticles($id);
        if ($total > 0) {
            $_SESSION['error'] = 'This category still has ' . $total . ' article(s). Move or delete them first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->categoryModel->delete($id);

        $_SESSION['success'] = 'Category deleted.';
        $this->redirect('/dashboard/categories');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $name = Validator::sanitize($_POST['name'] ?? '', 100);
        $slug = Validator::sanitize($_POST['slug'] ?? '', 100);

        if ($slug === '') {
            $slug = $name;
        }
        $slug = BlogHelper::makeSlug($slug);

        return ['name' => $name, 'slug' => $slug];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateCategory(array $category, int $ignoreId): string
    {
        if ($category['name'] === '') {
            return 'Category name is required.';
        }

        if ($this->categoryModel->slugExists($category['slug'], $ignoreId)) {
            return 'This slug is already used by another category. Please type a different slug.';
        }

        return '';
    }
}
```

How to read it:

1. `__construct` runs when Onebuild creates the controller. `parent::__construct($pdo)` hands the database connection to the Onebuild base controller. Then we create our model.
2. The first line of every action is `$this->requireAdmin();`. Anybody who is not logged in as admin or root is sent to the login page. **Forget this line and that page is open to the whole world.**
3. `store`, `update` and `delete` receive a form, so they check two things first: the request really is a POST, and the secret CSRF code is correct (`CsrfProtection::requireValidToken()`). A wrong or missing code stops everything with error 419.
4. `$_SESSION['success'] = '...'` stores a flash message. `$this->redirect(...)` then sends the browser back to the list, where the message appears once. Redirecting after saving also means that pressing the Refresh key will not save twice.
5. When the data is wrong we do **not** redirect. We show the form again with what the user already typed (`'category' => $category`), so nothing has to be retyped.
6. `readForm()` collects and cleans the typed values. `Validator::sanitize()` is a Onebuild tool that trims spaces, removes HTML tags and cuts the text to a maximum length. `$_POST['name'] ?? ''` means "the field `name`, or an empty text if it is missing". If the slug field is empty, the name is used to make one.
7. `delete()` refuses to remove a category that still contains articles.
8. The number in `edit(int $id)` comes from the address (`/dashboard/categories/edit/3`). Step 5.3 shows how the route passes it in.

Run the typo detector.

**Step 5.2** The four views. Create the folders `views/admin` and `views/admin/categories` first.

The form is needed twice (create and edit), so we write it once in `_form.php`. A file name that starts with an underscore is a *piece* that other pages paste in. It is not a page by itself.

**FILE: `views/admin/categories/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="name" class="form-label">Category Name</label>
        <input type="text" class="form-control" id="name" name="name"
               value="<?= htmlspecialchars($category['name']) ?>" maxlength="100" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($category['slug']) ?>" maxlength="100">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the name.</div>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/categories" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/categories/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/store';
$submitLabel = 'Save Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/categories/update/' . (int) $category['id'];
$submitLabel = 'Update Category';
?>

<div class="row">
    <div class="col-lg-6">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/categories/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/categories/create" class="btn btn-primary">Add New Category</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No categories yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= (int) $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['slug']) ?></td>
                        <td>
                            <a href="<?= $base ?>/dashboard/categories/edit/<?= (int) $cat['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/categories/delete/<?= (int) $cat['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this category?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What to notice in the views:

- `<?php if (...): ?> ... <?php else: ?> ... <?php endif; ?>` and `<?php foreach (...): ?> ... <?php endforeach; ?>` are PHP's way of writing "if" and "repeat for each row" in the middle of HTML. Every `if` and `foreach` must be closed by its `endif` and `endforeach`.
- `<?= \App\Core\CsrfProtection::getHiddenInput() ?>` prints the hidden secret code. **Every form needs it**, or the controller answers with error 419.
- **Delete is a tiny form with a button, not a plain link.** A plain link can be triggered by a trick page on another website. A form with the secret code cannot.
- The little `<script>` at the bottom shows the "Are you sure?" popup. It is plain JavaScript (no jQuery needed) and it carries `nonce="<?= $nonce ?>"`, which Onebuild's security rules demand.
- There is no `style="..."` anywhere. Onebuild's security rules block inline styles, so we use Bootstrap's ready-made classes (`mb-3`, `col-lg-6`, `d-inline` and so on) instead.

**Step 5.3** Tell Onebuild which address belongs to which method. This is the route list.

**FILE: `public/index.php`  (EDIT FILE)**

Scroll down to the end of the file and find these lines:

```php
// Protected route example
$router->addRoute('dashboard', 'DashboardController', 'index');
$router->addRoute('dashboard/profile', 'DashboardController', 'profile');

// Dispatch request - FIXED FOR SUBDIRECTORY
```

Add the following block **between** the `dashboard/profile` line and the `// Dispatch request` comment:

```php
// Admin: categories
$router->addRoute('dashboard/categories', 'AdminCategoryController', 'index');
$router->addRoute('dashboard/categories/create', 'AdminCategoryController', 'create');
$router->addRoute('dashboard/categories/store', 'AdminCategoryController', 'store');
$router->addRoute('dashboard/categories/edit/([0-9]+)', 'AdminCategoryController', 'edit');
$router->addRoute('dashboard/categories/update/([0-9]+)', 'AdminCategoryController', 'update');
$router->addRoute('dashboard/categories/delete/([0-9]+)', 'AdminCategoryController', 'delete');

```

How a route reads: `addRoute('address', 'ControllerName', 'methodName')`. The address has no slash at the start. `([0-9]+)` means "one or more digits", and whatever digits are found are handed to the method: the address `dashboard/categories/edit/3` calls `edit(3)`.

**CHECKPOINT 5. Try everything.**

1. Open a private/incognito browser window and visit `/dashboard/categories`. You must be sent to the login page. (This proves the admin lock works.)
2. In your normal window, click **Categories** in the sidebar. You see the table with "No categories yet."
3. Click **Add New Category**. Type `Technology News`, leave Slug empty, click **Save Category**. You return to the list with a green message, and the slug shows `technology-news`.
4. Add `Technology News` again. This time you stay on the form, see a red message that the slug is already used, and your typed text is still there.
5. Click **Edit** on the row. Change the name to `Tech` and the slug to `tech`. Click **Update Category**.
6. Add a category named `Temporary`. Click its **Delete** button. A popup asks "Delete this category?". Click Cancel: nothing happens. Click Delete again and then OK: it disappears.

Keep the `Tech` category for the next Part. If the popup does not appear when you click Delete, the `nonce="<?= $nonce ?>"` part of the script line is probably mistyped.

## Part 6: Article CRUD

Articles follow the very same pattern as categories. Three things are new:

- a **drop-down list** to pick the category,
- a **picture upload** (converted to WebP and saved in `public/uploads/images`),
- on edit, the old picture is **kept** unless you choose a new one (and the old file is removed when replaced or when the article is deleted).

**Step 6.1** The controller.

**FILE: `app/Controllers/AdminArticleController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use Exception;
use App\Core\Controller;
use App\Core\CsrfProtection;
use App\Core\Validator;
use App\Helpers\BlogHelper;
use App\Models\ArticleModel;
use App\Models\CategoryModel;

class AdminArticleController extends Controller
{
    private ArticleModel $articleModel;
    private CategoryModel $categoryModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel  = new ArticleModel($pdo);
        $this->categoryModel = new CategoryModel($pdo);
    }

    /**
     * Page: list of all articles
     */
    public function index(): void
    {
        $this->requireAdmin();

        $articles = $this->articleModel->getAllWithCategory();

        $this->view('admin/articles/index', [
            'pageTitle'   => 'Articles',
            'currentPage' => 'articles',
            'articles'    => $articles,
        ]);
    }

    /**
     * Page: empty form to write a new article
     */
    public function create(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->findAll('name ASC');
        if (empty($categories)) {
            $_SESSION['error'] = 'Please create a category first.';
            $this->redirect('/dashboard/categories');
            return;
        }

        $this->view('admin/articles/create', [
            'pageTitle'   => 'New Article',
            'currentPage' => 'articles',
            'categories'  => $categories,
            'article'     => [
                'title'       => '',
                'slug'        => '',
                'category_id' => 0,
                'content'     => '',
                'image'       => '',
            ],
        ]);
    }

    /**
     * Action: save the new article
     */
    public function store(): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->readForm();
        $article['image'] = '';
        $error = $this->validateArticle($article, 0);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/create', [
                'pageTitle'   => 'New Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $this->articleModel->insert([
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $newImage,
        ]);

        $_SESSION['success'] = 'Article published.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Page: form filled with the current values of one article
     */
    public function edit(int $id): void
    {
        $this->requireAdmin();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $this->view('admin/articles/edit', [
            'pageTitle'   => 'Edit Article',
            'currentPage' => 'articles',
            'categories'  => $this->categoryModel->findAll('name ASC'),
            'article'     => $article,
        ]);
    }

    /**
     * Action: save the changes
     */
    public function update(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $existing = $this->articleModel->findById($id);
        if ($existing === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        $article = $this->readForm();
        $article['id'] = $id;
        $article['image'] = $existing['image'];
        $error = $this->validateArticle($article, $id);

        $newImage = '';
        if ($error === '') {
            try {
                $newImage = $this->handleUpload();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if ($error !== '') {
            $_SESSION['error'] = $error;
            $this->view('admin/articles/edit', [
                'pageTitle'   => 'Edit Article',
                'currentPage' => 'articles',
                'categories'  => $this->categoryModel->findAll('name ASC'),
                'article'     => $article,
            ]);
            return;
        }

        $imageName = $existing['image'];
        if ($newImage !== '') {
            BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $existing['image']);
            $imageName = $newImage;
        }

        $this->articleModel->update($id, [
            'category_id' => $article['category_id'],
            'title'       => $article['title'],
            'slug'        => $article['slug'],
            'content'     => $article['content'],
            'image'       => $imageName,
        ]);

        $_SESSION['success'] = 'Article updated.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Action: delete one article (and its image file)
     */
    public function delete(int $id): void
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/dashboard/articles');
            return;
        }
        CsrfProtection::requireValidToken();

        $article = $this->articleModel->findById($id);
        if ($article === null) {
            $_SESSION['error'] = 'Article not found.';
            $this->redirect('/dashboard/articles');
            return;
        }

        BlogHelper::deleteImage(BlogHelper::imageFolder(), (string) $article['image']);
        $this->articleModel->delete($id);

        $_SESSION['success'] = 'Article deleted.';
        $this->redirect('/dashboard/articles');
    }

    /**
     * Reads the form fields and cleans them up.
     */
    private function readForm(): array
    {
        $title      = Validator::sanitize($_POST['title'] ?? '', 255);
        $slug       = Validator::sanitize($_POST['slug'] ?? '', 255);
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $content    = trim($_POST['content'] ?? '');

        if ($slug === '') {
            $slug = $title;
        }
        $slug = BlogHelper::makeSlug($slug);

        return [
            'title'       => $title,
            'slug'        => $slug,
            'category_id' => $categoryId,
            'content'     => $content,
        ];
    }

    /**
     * Checks the data. Returns an error message, or '' when everything is fine.
     */
    private function validateArticle(array $article, int $ignoreId): string
    {
        if ($article['title'] === '') {
            return 'Title is required.';
        }

        if ($this->categoryModel->findById($article['category_id']) === null) {
            return 'Please choose a category.';
        }

        if ($article['content'] === '') {
            return 'Content is required.';
        }

        if (strlen($article['content']) > 60000) {
            return 'Content is too long.';
        }

        if ($this->articleModel->slugExists($article['slug'], $ignoreId)) {
            return 'This slug is already used by another article. Please type a different slug.';
        }

        return '';
    }

    /**
     * Saves the uploaded image (if any) and returns its file name.
     * Returns '' when no file was chosen. Throws an Exception on problems.
     */
    private function handleUpload(): string
    {
        if (!isset($_FILES['image'])) {
            return '';
        }

        $code = $_FILES['image']['error'];

        if ($code === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if ($code !== UPLOAD_ERR_OK) {
            throw new Exception('The image could not be uploaded (error code ' . $code . ').');
        }

        return BlogHelper::saveImageAsWebp($_FILES['image'], BlogHelper::imageFolder());
    }
}
```

What is new compared to the category controller:

- `try { ... } catch (Exception $e) { ... }` means "try this; if the helper rings its alarm (`throw`), do not crash, run the catch part instead". Here the catch part stores the alarm's message (`$e->getMessage()`) so it can be shown in red.
- `handleUpload()` looks at `$_FILES['image']`, PHP's box holding the uploaded picture. It returns the new file name, or an empty text when no picture was chosen.
- In `update()`: no new picture means the old one stays. A new picture replaces the old one, and the old file is deleted so your folder does not fill up with unused pictures.
- `create()` sends you to the Categories page when there is no category yet, because an article needs one.
- `(int) $_POST['category_id']` forces the value to be a whole number. Never trust what arrives from a browser.

Run the typo detector.

**Step 6.2** The four views. Create the folder `views/admin/articles` first.

**FILE: `views/admin/articles/_form.php`  (NEW FILE)**

```php
<form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data">
    <?= \App\Core\CsrfProtection::getHiddenInput() ?>

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title"
               value="<?= htmlspecialchars($article['title']) ?>" maxlength="255" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug (optional)</label>
        <input type="text" class="form-control" id="slug" name="slug"
               value="<?= htmlspecialchars($article['slug']) ?>" maxlength="255">
        <div class="form-text">The word used in the web address. Leave it empty and it is created from the title.</div>
    </div>

    <div class="mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">-- Choose a category --</option>
            <?php foreach ($categories as $cat): ?>
                <?php
                $selected = '';
                if ((int) $cat['id'] === (int) $article['category_id']) {
                    $selected = 'selected';
                }
                ?>
                <option value="<?= (int) $cat['id'] ?>" <?= $selected ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="image" class="form-label">Featured Image (JPG, PNG or WebP, max 2 MB)</label>
        <?php if (!empty($article['image'])): ?>
            <div class="mb-2">
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-thumbnail" width="150" alt="Current image">
                <div class="form-text">Current image. Choose a new file only if you want to replace it.</div>
            </div>
        <?php endif; ?>
        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="mb-3">
        <label for="content" class="form-label">Content</label>
        <textarea class="form-control" id="content" name="content" rows="10" required><?= htmlspecialchars($article['content']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><?= $submitLabel ?></button>
    <a href="<?= $base ?>/dashboard/articles" class="btn btn-secondary">Cancel</a>
</form>
```

**FILE: `views/admin/articles/create.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/store';
$submitLabel = 'Publish Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/edit.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<?php
$formAction  = $base . '/dashboard/articles/update/' . (int) $article['id'];
$submitLabel = 'Update Article';
?>

<div class="row">
    <div class="col-lg-8">
        <?php require __DIR__ . '/_form.php'; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

**FILE: `views/admin/articles/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../../layouts/admin_top.php'; ?>

<div class="mb-3">
    <a href="<?= $base ?>/dashboard/articles/create" class="btn btn-primary">Write New Article</a>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped align-middle">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No articles yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($articles as $art): ?>
                    <tr>
                        <td>
                            <?php if (!empty($art['image'])): ?>
                                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                     width="60" class="img-thumbnail" alt="Thumbnail">
                            <?php else: ?>
                                <span class="badge bg-secondary">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($art['title']) ?></td>
                        <td><?= htmlspecialchars($art['category_name'] ?? 'Uncategorized') ?></td>
                        <td><?= htmlspecialchars(date('d M Y', strtotime($art['created_at']))) ?></td>
                        <td>
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-sm btn-info" target="_blank">View</a>
                            <a href="<?= $base ?>/dashboard/articles/edit/<?= (int) $art['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

                            <form method="POST" action="<?= $base ?>/dashboard/articles/delete/<?= (int) $art['id'] ?>"
                                  class="d-inline js-confirm" data-message="Delete this article?">
                                <?= \App\Core\CsrfProtection::getHiddenInput() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script nonce="<?= $nonce ?>">
// Ask "Are you sure?" before any Delete button sends its form.
document.querySelectorAll('.js-confirm').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!confirm(form.dataset.message)) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_bottom.php'; ?>
```

What is new in these views:

- A form that uploads a file **must** have `enctype="multipart/form-data"` in its `<form>` tag. Without it the picture never arrives.
- The drop-down: for every category we print an `<option>`. The one whose number matches the article's category gets the word `selected`, so the right choice is pre-picked when you edit.
- The "View" button on the list opens the public page of the article (you build it in Part 7).

**Step 6.3** Add the article routes.

**FILE: `public/index.php`  (EDIT FILE)**

Directly under the categories block you added in Step 5.3 (and still above the `// Dispatch request` comment), add:

```php
// Admin: articles
$router->addRoute('dashboard/articles', 'AdminArticleController', 'index');
$router->addRoute('dashboard/articles/create', 'AdminArticleController', 'create');
$router->addRoute('dashboard/articles/store', 'AdminArticleController', 'store');
$router->addRoute('dashboard/articles/edit/([0-9]+)', 'AdminArticleController', 'edit');
$router->addRoute('dashboard/articles/update/([0-9]+)', 'AdminArticleController', 'update');
$router->addRoute('dashboard/articles/delete/([0-9]+)', 'AdminArticleController', 'delete');

```

**CHECKPOINT 6. Try everything.**

1. Click **Articles** in the sidebar. You see "No articles yet."
2. Click **Write New Article**. Title: `My First Post!`. Category: `Tech`. Choose a small JPG or PNG picture. Content: type two short lines. Click **Publish Article**. You return to the list with a thumbnail and a green message.
3. Look inside the folder `public/uploads/images`. You find one new file whose name ends with `.webp`. That is your converted picture.
4. Write another article with the same title. You get the red "slug already used" message and your text is kept.
5. Try to upload a text file that you renamed to `picture.jpg`. You get "not a valid image".
6. Click **Edit** on the first article. Change the title, choose **no** new picture, click **Update Article**. The thumbnail is still there.
7. Edit it again, this time choose a different picture. Check the folder: there is still exactly one `.webp` file (the old one was removed).
8. Go to **Categories** and try to delete `Tech`. You get a red message: the category still has an article.
9. Back in **Articles**, make sure you end with **two articles** in the `Tech` category (one with a picture, one without). You need them for Part 7. You can test **Delete** on a third, temporary article.

## Part 7: The public blog pages

These are the pages ordinary visitors see. There is **no** `requireAdmin()` here on purpose: everybody may read. Only two actions are needed: a list (`index`) and one full article (`show`).

**Step 7.1** The controller.

**FILE: `app/Controllers/BlogController.php`  (NEW FILE)**

```php
<?php

namespace App\Controllers;

use PDO;
use App\Core\Controller;
use App\Models\ArticleModel;

class BlogController extends Controller
{
    private ArticleModel $articleModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->articleModel = new ArticleModel($pdo);
    }

    /**
     * Public page: list of all articles
     */
    public function index(): void
    {
        $articles = $this->articleModel->getAllWithCategory();

        $this->view('blog/index', [
            'pageTitle'       => 'Blog',
            'metaDescription' => 'Latest articles',
            'articles'        => $articles,
        ]);
    }

    /**
     * Public page: one full article
     */
    public function show(string $slug): void
    {
        $article = $this->articleModel->findWithCategoryBySlug($slug);

        if ($article === null) {
            http_response_code(404);
            $this->view('errors/404', ['pageTitle' => 'Article not found']);
            return;
        }

        $this->view('blog/show', [
            'pageTitle'       => $article['title'],
            'metaDescription' => 'Read: ' . $article['title'],
            'article'         => $article,
        ]);
    }
}
```

If no article matches the address, we answer with Onebuild's existing 404 page and the proper "404" code. The `pageTitle` text becomes the title in the browser tab.

**Step 7.2** The two views. Create the folder `views/blog` first. These pages use Onebuild's normal public frame (header, navbar, footer), exactly like `views/home/index.php` does.

**FILE: `views/blog/index.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <h1 class="mb-4">Latest Articles</h1>

    <?php if (empty($articles)): ?>
        <p class="text-muted">No articles have been published yet.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($articles as $art): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <?php if (!empty($art['image'])): ?>
                            <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($art['image']) ?>"
                                 class="card-img-top" alt="<?= htmlspecialchars($art['title']) ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($art['category_name'] ?? 'General') ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($art['title']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars(\App\Helpers\BlogHelper::excerpt($art['content'], 120)) ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="<?= $base ?>/blog/<?= htmlspecialchars($art['slug']) ?>" class="btn btn-outline-primary btn-sm">Read more</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

**FILE: `views/blog/show.php`  (NEW FILE)**

```php
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="<?= $base ?>/blog" class="btn btn-outline-secondary btn-sm mb-3">&larr; Back to articles</a>

            <span class="badge bg-info text-dark mb-2"><?= htmlspecialchars($article['category_name'] ?? 'General') ?></span>
            <h1 class="fw-bold"><?= htmlspecialchars($article['title']) ?></h1>
            <p class="text-muted small">Published on <?= htmlspecialchars(date('d M Y', strtotime($article['created_at']))) ?></p>

            <?php if (!empty($article['image'])): ?>
                <img src="<?= $base ?>/uploads/images/<?= htmlspecialchars($article['image']) ?>"
                     class="img-fluid rounded my-3 w-100" alt="<?= htmlspecialchars($article['title']) ?>">
            <?php endif; ?>

            <div class="mt-4 fs-5">
                <?= nl2br(htmlspecialchars($article['content'])) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
```

What to notice: `nl2br(htmlspecialchars(...))` first makes the text safe, then turns your line breaks into `<br>` so paragraphs look right. The `??` in `$art['category_name'] ?? 'General'` means "use this value, or `General` if there is none".

**Step 7.3** Add a **Blog** link to the top menu.

**FILE: `views/layouts/navbar.php`  (EDIT FILE)**

Find the Home menu item:

```php
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
```

Directly **under** it add:

```php
                <li class="nav-item">
                    <a class="nav-link <?= stripos($requestUri, 'blog') === 0 ? 'active' : '' ?>" href="<?= $base ?>/blog">Blog</a>
                </li>
```

**Step 7.4** Add the two public routes.

**FILE: `public/index.php`  (EDIT FILE)**

Under the articles block, still above `// Dispatch request`, add:

```php
// Blog (public pages)
$router->addRoute('blog', 'BlogController', 'index');
$router->addRoute('blog/([a-z0-9-]+)', 'BlogController', 'show');

```

The pattern `([a-z0-9-]+)` means "letters, digits and dashes", which is exactly what a slug is made of. The address `blog/my-first-post` calls `show('my-first-post')`.

**CHECKPOINT 7. The final test.**

1. Open a private/incognito window (you are not logged in) and go to `/blog`. You see a card for each article, with picture, category badge, title and the start of the text.
2. Click **Read more**. You see the full article. The address in the browser ends with the slug, for example `/blog/my-first-post`.
3. Type `<b>hello</b>` inside an article's content and save. On the public page the visible text is literally `<b>hello</b>` and nothing turns bold. That is the `htmlspecialchars` protection working.
4. Visit `/blog/does-not-exist`. You get the 404 page.
5. In the private window visit `/dashboard/articles`. You are sent to the login page.
6. The top menu now has a **Blog** link. Your original home page, login and profile page still work.

**Congratulations. You have built a complete CRUD application.**

## Part 8: What happens when you click "Publish Article"

Read this slowly once. It ties everything together.

1. The browser sends the form (POST) to the address `/dashboard/articles/store`, with your text, the picture and the secret CSRF code.
2. `public/index.php` looks through the route list and finds `dashboard/articles/store`. It creates `AdminArticleController` and calls `store()`.
3. `store()` first runs `requireAdmin()` (are you an admin?) and `requireValidToken()` (is the secret code correct?).
4. `readForm()` collects and cleans the text. `validateArticle()` checks it (title present? real category? slug free?).
5. `handleUpload()` passes the picture to `BlogHelper::saveImageAsWebp()`, which checks that it really is an image, converts it and saves it.
6. `ArticleModel::insert()` (inherited from Onebuild's Model) writes the new row into the `articles` table.
7. The controller stores "Article published." in the session and redirects to `/dashboard/articles`.
8. The list page runs `index()`, asks `ArticleModel` for the rows, hands them to `views/admin/articles/index.php`, and the top menu shows the green message once.

Route, controller, model, view. Every page you will ever build in Onebuild is a variation of this chain.

## When something goes wrong

First: make sure `APP_ENV=development` is set in `.env` (local computer only). Then find your symptom.

**A white page, or "Something went wrong" / Error 500**

PHP found a problem it could not recover from. Run the typo detector (`php -l path/to/file.php`) on the file you edited last. If it says "No syntax errors", the message is hidden in your server's PHP error log (on LMDE: `sudo tail -n 30 /var/log/nginx/onebuild.test.error.log`). Read the last line.

**"Parse error: syntax error, unexpected ..." with a line number**

A typo. Look at that line **and the line above it**. Usual suspects: a missing `;` at the end of a line, a missing `)` or `}` or `]`, a missing closing quote, `->` typed as `-` or `=>` typed as `=`.

**A page you made shows the 404 page**

Onebuild shows 404 whenever it cannot match a route **or** cannot find the controller class or method. Check, in this order: (1) the route line in `public/index.php` is spelled exactly like the address and has **no** slash at the start; (2) the controller file name equals the class name, capitals included; (3) the method name in the route equals the real method name.

**"Class ... not found" or "View not found"**

The file is in the wrong folder or has the wrong name. `AdminCategoryController` must be in `app/Controllers/`, models in `app/Models/`, the helper in `app/Helpers/`, views under `views/`. Also check the `namespace` line at the top of the file.

**"Table ... doesn't exist" (an SQLSTATE message)**

You ran the SQL of Part 1 in the wrong database. The right one is named in the `DB_NAME` line of `.env`.

**"CSRF token validation failed" (error 419)**

The form is missing the line `<?= \App\Core\CsrfProtection::getHiddenInput() ?>`, or you left the form open for more than an hour (reload the page and try again), or the picture was bigger than your server allows and the browser's whole form was dropped. Try a smaller picture.

**"The image could not be uploaded (error code 1)" or "(error code 2)"**

The picture is larger than the server's limit. Choose a smaller picture, or raise `upload_max_filesize` and `post_max_size` in `/etc/php/8.5/fpm/conf.d/99-dev.ini` (Section 9.9) and run `sudo systemctl restart php8.5-fpm`.

**"Call to undefined function imagecreatefromwebp()" or "imagewebp()"**

PHP's picture extension is switched off. Install it with `sudo apt install php8.5-gd` and run `sudo systemctl restart php8.5-fpm`.

**"Access denied. Admin privileges required."**

You are logged in, but your role is not `root` or `admin`. Open the `users` table in your database tool and look at the `role` column of your user.

**The "Are you sure?" popup does not appear when I click Delete**

The `<script>` line at the bottom of the list view is missing `nonce="<?= $nonce ?>"` or has a typo in it. The browser silently refuses scripts without the nonce.

**Pictures do not show up**

Open the folder `public/uploads/images`: is the `.webp` file there? If the folder is empty on a live server, the folder is probably not writable (give it permission 755).

**I fixed the file but nothing changed**

Save the file (Ctrl+S), then reload the browser page with Ctrl+F5.

## Make your own CRUD: the 10-step recipe

To manage anything else (products, clients, events...), copy the pattern. Example: **Products**.

1. Create the table in the database (`products` with the columns you need).
2. Copy `CategoryModel.php` to `ProductModel.php`. Change the class name, `$table`, and the SQL inside.
3. Copy `AdminCategoryController.php` to `AdminProductController.php`. Change the class name, the model, the view folder names, the address text (`/dashboard/products`) and the messages.
4. Adjust `readForm()` and `validateProduct()` to your fields.
5. Copy the four views of `views/admin/categories/` into `views/admin/products/` and change the fields and the table columns.
6. Add six routes to `public/index.php`.
7. Add a link in `views/dashboard/_sidebar.php` with its own `$currentPage` word.
8. Test: create, edit, delete, empty form, duplicate value, no login.
9. Read your controller once more and ask: is `requireAdmin()` the first line of every action? Does every form carry the CSRF code? Is every printed value wrapped in `htmlspecialchars()`?
10. Before going live, set `APP_ENV=production` in the live `.env`.

## Ideas for practice

- **Draft or published.** Add a column `status` to `articles` (`'draft'` or `'published'`), add a drop-down in the form, and show only published articles on `/blog`. Hint: the base Model already has `findAllPublished()`, which looks for `status = 'published'`.
- **Dashboard numbers.** Show "Total articles" and "Total categories" on the dashboard overview page. Hint: add a `countAll()` method to each model using `queryOne('SELECT COUNT(*) AS total FROM ...')`.
- **Articles per category.** Add the route `blog/category/([a-z0-9-]+)` that lists only the articles of one category.

If you get stuck on any step, write down the file name and copy the exact error message. That is all anyone needs to help you.

## Save your blog in Git

Once Git is set up (Section 14.1), save this work:

```bash
$ cd ~/www/onebuild
$ git status
$ git add .
$ git commit -m "Add blog: categories, articles, public pages"
```

Pushing it to your own GitHub repository is covered in Section 14.4.


<div style="page-break-after: always;"></div>

# Chapter 13: Create Your Own New PHP Project

## 13.1 Create the folder structure

```bash
$ mkdir -p ~/www/myapp/public
$ cd ~/www/myapp
```

Only the `public` folder is served to browsers. Put code you do not want exposed (config, classes, `.env`) **next to** `public`, not inside it.

A simple layout:

```
myapp/
  app/          <- your PHP classes
  config/       <- settings
  views/        <- templates
  public/       <- web root  (index.php lives here)
  .env          <- secrets (never commit)
  .gitignore
```

## 13.2 First page

```bash
$ mousepad public/index.php
```

```php
<?php
echo "<h1>Hello from myapp.test</h1>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";
```

## 13.3 Publish it

```bash
$ sudo newsite myapp ~/www/myapp/public
```

Open **http://myapp.test**. You should see the heading and "PHP version: 8.5.x".

## 13.4 Test PHP with the database

```bash
$ mousepad public/db.php
```

```php
<?php
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'dev', 'ChangeThisPassword');
    echo "Database connection OK";
} catch (PDOException $e) {
    echo "Failed: " . $e->getMessage();
}
```

Open **http://myapp.test/db.php**. Delete this file when done testing, since it contains a password.

## 13.5 Open the project in VS Code

```bash
$ code ~/www/myapp
```

**Checkpoint:** `myapp.test` works and connects to the database.

<div style="page-break-after: always;"></div>

# PART IV: GIT, PRODUCTION, AND DEPLOYMENT

# Chapter 14: Git and GitHub: Create a Repo and Push

> **For this book's journey, Scenario B (Section 14.4) is the important one:** you cloned Onebuild in Chapter 11, you built the blog in Chapter 12, and now you push that work to **your own** GitHub repository. That repository is what you deploy to Dewaweb in Chapter 16.

## 14.1 Tell Git who you are (once)

```bash
$ git config --global user.name "Your Name"
$ git config --global user.email "you@example.com"
$ git config --global init.defaultBranch main
```

Use the same email as your GitHub account (or GitHub's private `noreply` address from your GitHub email settings).

## 14.2 Create an SSH key (once)

GitHub no longer accepts account passwords for pushing. An SSH key is the simplest permanent method.

```bash
$ ssh-keygen -t ed25519 -C "you@example.com"
```

Press Enter to accept the default location. Set a passphrase or leave it empty. Then show the **public** key:

```bash
$ cat ~/.ssh/id_ed25519.pub
```

Copy the whole line (starts with `ssh-ed25519`).

On github.com: click your profile picture -> **Settings** -> **SSH and GPG keys** -> **New SSH key** -> give it a title (for example "LMDE desktop"), paste the key, **Add SSH key**.

Test:

```bash
$ ssh -T git@github.com
```

The first time, type `yes` to trust GitHub's fingerprint. You should see "Hi <username>! You've successfully authenticated".

## 14.3 Scenario A: push your new project (`myapp`) to a new repository

**Step 1: create the empty repo on GitHub.**
On github.com click **+** -> **New repository**. Name it `myapp`. Choose Public or Private. **Do NOT** tick "Add a README", ".gitignore" or "license" (the repo must be empty). Click **Create repository**.

**Step 2: prepare the local project.**

```bash
$ cd ~/www/myapp
$ mousepad .gitignore
```

Put at least this in `.gitignore`:

```
.env
vendor/
node_modules/
storage/*.log
.vscode/
```

**Step 3: first commit and push.**

```bash
$ git init
$ git add .
$ git status
$ git commit -m "Initial commit"
$ git branch -M main
$ git remote add origin git@github.com:<your-username>/myapp.git
$ git push -u origin main
```

Always read `git status` before committing and confirm `.env` is **not** listed.

## 14.4 Scenario B: take the cloned `onebuild` and push it to YOUR OWN new repo

You cannot push to `onebuild-cpu/onebuild` (it belongs to someone else). Two options:

**Option 1: your own independent copy.** Create a new empty repo on GitHub (for example `onebuild-mine`), then:

```bash
$ cd ~/www/onebuild
$ git remote rename origin upstream
$ git remote add origin git@github.com:<your-username>/onebuild-mine.git
$ git push -u origin main
```

`upstream` still points to the original, so you can pull its updates later with `git pull upstream main`.

**Option 2: GitHub Fork.** On the original repo's page click **Fork**, then clone your fork instead.

## 14.5 Daily Git workflow

```bash
$ git status                 # what changed?
$ git add .                  # stage everything
$ git commit -m "Describe the change"
$ git push                   # upload
$ git pull                   # download updates
$ git log --oneline -n 10    # recent history
```

Good habits:

* Commit small and often, with clear messages.
* Never commit `.env`, passwords, or API keys. If you did, change those secrets at once.

**Checkpoint:** your project is visible on your GitHub profile.

<div style="page-break-after: always;"></div>

# Chapter 15: Prepare Onebuild for Production

Do this chapter on your LMDE machine **before** you touch Dewaweb.

## 15.1 Find out what Dewaweb offers

Log in to your Dewaweb **cPanel** (the link and password come in your welcome email) and look in the search box at the top for:

| Search for | Why |
|---|---|
| **MultiPHP Manager** | Which PHP versions can you select? Write down the highest one. |
| **Select PHP Version** or **PHP Extensions** | Are `gd`, `pdo_mysql`, `mbstring`, `curl`, `zip`, `intl` enabled? |
| **Git Version Control** | If it exists, you can deploy straight from GitHub. |
| **Terminal** or **SSH Access** | If it exists, you can run `git pull` and `php bin/add_root.php` on the server. |
| **MySQL Database Wizard** and **phpMyAdmin** | You need them in Chapter 16. |

Write down your findings. They decide which deployment method you use in Chapter 16.

## 15.2 Test locally with the same PHP version as Dewaweb

If Dewaweb's highest version is lower than 8.5 (for example 8.4), test locally with that version.

```bash
$ sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-gd php8.4-bcmath
$ sudo systemctl start php8.4-fpm
$ sudo newsite onebuild ~/www/onebuild/public 8.4
```

Open http://onebuild.test and click through every page, including all of Chapter 12's admin screens, login, and an image upload. Fix every warning. (The third argument, `8.4`, is the PHP version for that site.) Switch back any time:

```bash
$ sudo newsite onebuild ~/www/onebuild/public 8.5
```

## 15.3 The production `.env` (prepare it now)

On the server you will create a **different** `.env`. Prepare its content in a text file on your desktop (not in the project):

```
APP_ENV=production
DB_NAME=<cpanel-user>_onebuild
DB_USER=<cpanel-user>_onebuild
DB_PASS=<a long random password>
```

plus every other key that appears in `.env.example`, using the same key names. Notes:

* `APP_ENV` **must** be `production` online. This hides error details from visitors.
* On cPanel, database and user names are prefixed with your account name. You choose the exact names in Chapter 16.
* If there is a site URL setting, it will be `https://yourdomain.com` (or the staging subdomain).
* **Never commit `.env` to Git.** Confirm with `git status` that `.env` is not listed.

## 15.4 Dependencies

If the project has a `composer.json`:

```bash
$ cd ~/www/onebuild
$ ls composer.json && composer install --no-dev --optimize-autoloader
```

If the server has no Composer, the `vendor/` folder must be uploaded with the code (see 16.6).

## 15.5 Decide what should not be uploaded

The repository contains developer-only files: `dump_file.py`, `project_dump.md`, and the `tutorial` folder. They do no harm outside the web root, but you may leave them out of a deployment ZIP. Create a file named `.gitattributes` in the project root:

```
dump_file.py   export-ignore
project_dump.md export-ignore
tutorial        export-ignore
.gitattributes  export-ignore
```

`git archive` (used in 16.6, Method 1) then skips them.

## 15.6 Export your database for the server

Structure for every table, plus the data of the `users` table (so your admin account comes with it):

```bash
$ mkdir -p ~/deploy
$ mariadb-dump -u dev -p --no-data onebuild > ~/deploy/schema.sql
$ mariadb-dump -u dev -p --no-create-info onebuild users > ~/deploy/users.sql
```

(`--no-data` writes table structures only; `--no-create-info` writes rows only.) Later, in Chapter 16 you import `schema.sql` first, then `users.sql`. If you prefer to start the live site with your test articles, export the whole database with `mariadb-dump -u dev -p onebuild > ~/deploy/full.sql` instead.

> Your password is stored only as a hash in `users.sql`, but still treat the file as private and delete it from the server after importing.

## 15.7 Security tests, locally

With the stack running, run these. Each result must match.

```bash
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/.env
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/.git/config
```

Both must print **404** or **403**, never `200`. Then prove that a PHP file in the uploads folder cannot run:

```bash
$ echo '<?php echo "DANGER";' > ~/www/onebuild/public/uploads/images/t.php
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/uploads/images/t.php
$ rm ~/www/onebuild/public/uploads/images/t.php
```

It must print **403** (or 404). If it prints `200` and you see `DANGER`, stop: the uploads protection is missing (re-check the `newsite` template in Chapter 10).

## 15.8 Commit and push

Commit your finished work and push it to your own GitHub repository (Chapter 14, Scenario B). Always run `git status` first and confirm `.env` is not in the list.

## 15.9 Make a backup point

```bash
$ sudo timeshift --create --comments "ready to deploy" --tags D
```

**Checkpoint:** the site works locally on the same PHP version as Dewaweb, security tests pass, the production `.env` content and SQL files are ready, and your code is on GitHub.

<div style="page-break-after: always;"></div>

# Chapter 16: Deploy to Dewaweb Shared Hosting

Dewaweb's cloud hosting runs **cPanel** on **LiteSpeed Enterprise** with selectable PHP versions. LiteSpeed reads `.htaccess`, so Apache-style rules just work there.

> Menu names below are the standard cPanel names. If your screen looks different, type the name into the **search box at the top of cPanel**. Anything marked **(if available)** depends on your plan; ask Dewaweb support if you cannot find it.

## 16.1 The plan in one picture

```
Your LMDE PC            GitHub                Dewaweb (cPanel + LiteSpeed)
------------            ------                ----------------------------
code + test   --push--> your repo --deploy--> ~/onebuild/            (project)
http://x.test                                 ~/onebuild/public       (web root)
                                              https://yourdomain.com
```

**Golden rule:** only the `public` folder should be reachable from the internet. Everything else (`app`, `config`, `database`, `.env`, `.git`) stays one level above it.

## 16.2 Domain, DNS, and a staging subdomain

1. Point your domain to Dewaweb as your welcome email says (usually by changing the domain's **nameservers**). DNS changes can take from minutes to many hours.
2. While waiting, you can test through a **subdomain** such as `staging.yourdomain.com`. Create it in 16.3.
3. Do the full deployment on the staging subdomain first. When everything passes, repeat the steps for the real domain (or move it).

## 16.3 Create the web root that points to `public`

**Layout A (recommended):** the project lives outside the web root, and the (sub)domain's **document root** is the project's `public` folder.

In cPanel open **Domains** (older versions: **Subdomains**), then:

1. Click **Create A New Domain** (or Create Subdomain).
2. Domain: `staging.yourdomain.com`.
3. **Untick** "Share document root" (if shown).
4. Document Root: `onebuild_staging/public`. (This is relative to your home folder.)
5. Submit. cPanel creates the folders `onebuild_staging/public`.

For the **main domain** you have two options:

* In **Domains -> Manage**, change the document root of the domain to `onebuild/public` (if your cPanel allows editing it), or
* with SSH or Terminal **(if available)**: put the project in `~/onebuild` and replace the default folder with a link:
  ```bash
  $ mv ~/public_html ~/public_html.bak
  $ ln -s ~/onebuild/public ~/public_html
  ```

**Layout B (only if neither works):** upload the whole project into `public_html` and rely on the `.htaccess` in the project root. Use Layout B only after the security tests in 16.12 all pass, because here `.env`, `app/`, and `config/` sit inside the web root.

## 16.4 Choose the PHP version

1. cPanel -> **MultiPHP Manager**.
2. Tick your domain or subdomain, choose the PHP version you tested locally in 15.2, click **Apply**.
3. cPanel -> **MultiPHP INI Editor** -> select the domain, and set:
   * `upload_max_filesize` = `8M`
   * `post_max_size` = `8M`
   * `memory_limit` = `256M` (or what the plan allows)
4. Make sure the extensions are on (cPanel -> **Select PHP Version** or **PHP Extensions**, if available): `gd`, `pdo_mysql` (or `mysqli`), `mbstring`, `curl`, `zip`, `intl`, `xml`.

## 16.5 Create the database

1. cPanel -> **MySQL Database Wizard**.
2. Step 1: database name `onebuild`. cPanel adds your account prefix, so the real name is like `youruser_onebuild`.
3. Step 2: create a user, for example `onebuild`, with a **long generated password**. Save it.
4. Step 3: tick **ALL PRIVILEGES**.
5. Write down the three real values (database, user, password). They go into the server `.env`.

**Import the tables:** cPanel -> **phpMyAdmin** -> click your database in the left list -> **Import** -> choose `~/deploy/schema.sql` -> **Go**. Repeat with `~/deploy/users.sql`. (If a file is large, compress it as `.zip` or `.gz` first; phpMyAdmin accepts them.)

## 16.6 Upload the code

Pick **one** method. They all end with the project folder in your home directory, for example `/home/youruser/onebuild_staging`.

### Method 1: ZIP upload (always works)

On your LMDE PC, in the project folder:

```bash
$ cd ~/www/onebuild
$ git status                     # must be clean, .env not listed
$ git archive --format=zip --output=$HOME/deploy/onebuild.zip HEAD
```

`git archive` packs only what is committed (no `.git`, no `.env`) and honours the `.gitattributes` from 15.5.

If the project uses Composer and the server has no Composer, add the `vendor` folder: `zip -r ~/deploy/onebuild.zip vendor`.

In cPanel:

1. **File Manager** -> go to your home folder (not `public_html`).
2. Create (or open) the folder `onebuild_staging`. Upload `onebuild.zip` into it.
3. Right-click the zip -> **Extract**. Then delete the zip.
4. Make sure `onebuild_staging/public/index.php` exists.

### Method 2: cPanel "Git Version Control" **(if available)**

1. cPanel -> **Git Version Control** -> **Create**.
2. Turn on **Clone a Repository**.
3. Clone URL: `https://github.com/<your-username>/<your-repo>.git`. (A private repository needs an SSH deploy key. Easiest is to keep the repo public while you learn, as long as it contains **no secrets**.)
4. Repository Path: `/home/youruser/onebuild_staging` (the folder must not already contain files).
5. Click **Create**. To update later, open **Manage** and use **Update from Remote**.

Method 2 gives you the whole repository including `.git` in a folder outside the web root. That is safe in Layout A.

### Method 3: SSH **(if available)**

```bash
$ ssh <user>@<server> -p <port>        # values from Dewaweb
$ git clone https://github.com/<your-username>/<your-repo>.git onebuild_staging
```

## 16.7 Create the server `.env`

1. In **File Manager**, open **Settings** (top right) and tick **Show Hidden Files (dotfiles)**.
2. Go into `onebuild_staging`, click **+ File**, name it `.env`.
3. Right-click -> **Edit**, paste the content you prepared in 15.3 (with the real database name, user, and password from 16.5), and make sure it says `APP_ENV=production`.
4. Save. Set its permission to **600** (right-click -> Change Permissions).

## 16.8 Folder permissions

On cPanel with LiteSpeed, PHP runs as **your account**, so normal permissions are enough. **Never use 777.**

| What | Permission |
|---|---|
| Folders | `755` |
| Files | `644` |
| `.env` | `600` |
| `storage/` and `public/uploads/` | `755` (writable by your account) |

Make sure `public/uploads/images` exists. If it does not, create it in File Manager.

## 16.9 Turn on HTTPS

1. cPanel -> **SSL/TLS Status** -> select the domain -> **Run AutoSSL**. Wait a few minutes.
2. In **Domains**, switch on **Force HTTPS Redirect** for the domain.
3. If Onebuild has a site URL setting in `.env`, make it `https://...`.

## 16.10 Create the administrator account

* If you imported `users.sql` in 16.5, your local admin already exists. Sign in with the same login and password you used locally.
* Otherwise, with SSH or Terminal **(if available)**:
  ```bash
  $ cd ~/onebuild_staging
  $ php bin/add_root.php "+628123456789" "YourStrongPassword"
  ```

After the first sign in, **change the password** to a new one.

## 16.11 Does the site work?

Open `https://staging.yourdomain.com` and run this checklist:

- [ ] Home page opens, with Bootstrap styles.
- [ ] `/login` works and you reach `/dashboard`.
- [ ] `/blog` shows your articles (or "No articles yet").
- [ ] Create a category and an article with a picture. The image appears.
- [ ] Edit and delete work.
- [ ] A deliberately wrong address shows the 404 page, not a PHP error.

## 16.12 Security tests (mandatory)

Run these from your LMDE terminal against the **live** address:

```bash
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/.env
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/.git/config
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/config/
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/app/
```

Every line must print **403** or **404**. If any prints `200`, take the site offline (rename the document root) and fix it before anything else. In Layout A these paths are not even inside the web root, so they cannot be served.

Also check:

- [ ] Open a page that causes an error (for example change nothing, just visit a wrong URL). You must **not** see file paths, SQL, or stack traces. If you do, `APP_ENV` is not `production`.
- [ ] Look at the address bar: the padlock shows HTTPS.

## 16.13 Go live

When staging is perfect:

1. Point the real domain at the project (16.3, main-domain options).
2. Make a production `.env` for it (the URL setting becomes your real domain).
3. Repeat 16.4, 16.9, and 16.12 for the real domain.

## 16.14 How to update the live site later

1. Change and test on LMDE.
2. `git add . && git commit -m "message" && git push`.
3. On the server:
   * Method 2: cPanel -> Git Version Control -> Manage -> **Update from Remote**.
   * Method 3: `ssh` in, `cd onebuild_staging && git pull`.
   * Method 1: build a new ZIP and upload over the folder (do **not** delete `.env` or `public/uploads`).
4. If you changed the database, apply the SQL changes in **phpMyAdmin**. Keep a list of every `ALTER TABLE` or `CREATE TABLE` you ran locally.
5. Test the checklist in 16.11 again.

**Never** edit files directly on the server unless it is an emergency. Fix locally, push, deploy.

## 16.15 Backups and rollback

* Before every update, export the database: phpMyAdmin -> your database -> **Export**.
* Dewaweb provides its own backups, but keep your own copy too: download a database export weekly.
* Roll back code: with Git, `git checkout <older-commit>` on the server (or deploy an older ZIP).
* The folder `public/uploads/images` holds pictures that are **not** in Git. Download a copy of it before any risky change.

## 16.16 Problems on the server

| Symptom | Likely cause and fix |
|---|---|
| 500 Internal Server Error | Open the file `error_log` in the site folder (File Manager) or cPanel -> **Errors**. Often a wrong `.env` or missing PHP extension |
| White page | `APP_ENV=production` hides errors. Check `error_log` |
| Every page except home gives 404 | The document root is not the `public` folder, or `.htaccess` is missing from `public` |
| "Database connection failed" | Wrong name/user/password. Remember the `youruser_` prefix. Re-check that the user was added to the database with ALL PRIVILEGES |
| Pictures do not upload | Folder missing or not writable (`public/uploads/images`, 755). Also check `upload_max_filesize` (16.4) |
| "Class not found" | A file was not uploaded or a file name has different capital letters. Linux is case-sensitive |
| Styles missing | Wrong base URL setting, or the browser cached the old site (Ctrl+F5) |
| Works locally, not online | Compare PHP versions (16.4 vs 15.2), extensions, and `.htaccess` rules (Chapter 8) |

**Checkpoint:** your Onebuild site runs over HTTPS on Dewaweb and passes every security test.

<div style="page-break-after: always;"></div>

# Chapter 17: Daily Routine

## 17.1 Do not let the web stack start by itself

Nginx, PHP-FPM, and MariaDB use RAM. Start them only when developing:

```bash
$ sudo systemctl disable nginx php8.5-fpm mariadb
```

## 17.2 Shortcuts

```bash
$ mousepad ~/.bashrc
```

Add at the bottom:

```bash
alias devon='sudo systemctl start nginx php8.5-fpm mariadb'
alias devoff='sudo systemctl stop nginx php8.5-fpm mariadb'
alias devstatus='systemctl is-active nginx php8.5-fpm mariadb'
```

Load them:

```bash
$ source ~/.bashrc
```

## 17.3 A typical day

```bash
$ devon                       # start the stack
$ code ~/www/myapp            # open the project
# ... work, refresh http://myapp.test ...
$ git add . && git commit -m "Work" && git push
$ devoff                      # free the RAM
```

## 17.4 Weekly maintenance

```bash
$ sudo apt update && sudo apt upgrade -y
$ sudo apt autoremove --purge -y
$ sudo apt clean
```

(Review what `autoremove` plans to do if you are unsure: `sudo apt-get -s autoremove --purge`.)
## 17.5 Deploy day

When a change is ready for the live site:

```bash
$ cd ~/www/onebuild
$ git status                      # .env must not be listed
$ git add . && git commit -m "Describe the change"
$ git push
```

Then update the server as in Section 16.14, run the checklist in Section 16.11, and the security tests in Section 16.12.

<div style="page-break-after: always;"></div>

# Chapter 18: Troubleshooting and Recovery

## 18.1 Black screen or no desktop after reboot

1. Press **Ctrl+Alt+F3** for a text console and log in.
2. Reinstall the desktop pieces:

```bash
$ sudo apt update
$ sudo apt install --reinstall cinnamon cinnamon-session nemo \
    lightdm slick-greeter network-manager network-manager-gnome
$ sudo systemctl restart lightdm
```

3. If still broken, restore the snapshot from a console:

```bash
$ sudo timeshift --list
$ sudo timeshift --restore
```

## 18.2 No internet after debloat

```bash
$ sudo apt install --reinstall network-manager network-manager-gnome
$ sudo systemctl enable --now NetworkManager
```

## 18.3 `apt update` complains about the Sury repository

* "NO_PUBKEY" or "not signed": repeat the two key commands in Section 9.2.
* Make sure `/etc/apt/sources.list.d/php-sury.list` says `trixie` and not `gigi`.

## 18.4 `php -v` shows the wrong version

```bash
$ sudo update-alternatives --set php /usr/bin/php8.5
$ ls /run/php/
```

You should see `php8.5-fpm.sock`. If the socket name is different, edit `/etc/nginx/sites-available/<name>.test` and fix the `fastcgi_pass` line, then `sudo systemctl reload nginx`.

## 18.5 ZRAM not active

```bash
$ systemctl status systemd-zram-setup@zram0.service
$ sudo cat /etc/systemd/zram-generator.conf
```

Check the file is spelled exactly as in Section 7.3, then reboot.

## 18.6 Running out of memory anyway

* Use XFCE instead of Cinnamon.
* Run `devoff` when not developing.
* Keep one browser with few tabs; close VS Code windows.
* Raise ZRAM to `ram * 0.75`.
* Consider more RAM: DDR3 modules are cheap, and 8 GB changes this machine completely.
## 18.7 Upload fails with "413 Request Entity Too Large"

Nginx is rejecting the file before PHP sees it. Re-run `newsite` (the template sets `client_max_body_size 8M`) and check that `/etc/php/8.5/fpm/conf.d/99-dev.ini` exists (Section 9.9).

## 18.8 It works locally but not on Dewaweb

Go through Section 8.3 (the five things that must match), then Section 16.16.

## 18.9 Quick health check of the whole stack

```bash
$ systemctl is-active nginx php8.5-fpm mariadb
$ sudo nginx -t
$ php -v
$ zramctl
$ free -h
```

<div style="page-break-after: always;"></div>

# Appendix A: Command Cheat Sheet

## System

| Task | Command |
|---|---|
| Update all | `sudo apt update && sudo apt upgrade -y` |
| Install | `sudo apt install <name>` |
| Remove completely | `sudo apt purge <name>` |
| Clean orphans | `sudo apt autoremove --purge` |
| Memory | `free -h` |
| ZRAM status | `zramctl` and `swapon --show` |
| Snapshot | `sudo timeshift --create --comments "text"` |

## Web stack

| Task | Command |
|---|---|
| Start stack | `devon` |
| Stop stack | `devoff` |
| New local site | `sudo newsite <name> <path>/public` |
| Test Nginx config | `sudo nginx -t` |
| Reload Nginx | `sudo systemctl reload nginx` |
| Nginx errors | `sudo tail -n 30 /var/log/nginx/error.log` |
| MariaDB shell | `sudo mariadb` |
| PHP version | `php -v` |

## Git

| Task | Command |
|---|---|
| Clone | `git clone <url>` |
| Status | `git status` |
| Stage all | `git add .` |
| Commit | `git commit -m "message"` |
| Push | `git push` |
| Pull | `git pull` |
| First push of new repo | `git remote add origin <ssh-url>` then `git push -u origin main` |

## Deployment

| Task | Command or place |
|---|---|
| Pack the committed code | `git archive --format=zip --output=$HOME/deploy/onebuild.zip HEAD` |
| Database structure only | `mariadb-dump -u dev -p --no-data onebuild > schema.sql` |
| Users table rows only | `mariadb-dump -u dev -p --no-create-info onebuild users > users.sql` |
| Test a protected path | `curl -s -o /dev/null -w "%{http_code}\n" <url>/.env` |
| Switch a site's PHP version | `sudo newsite <name> <path> 8.4` |
| Dewaweb PHP version | cPanel -> MultiPHP Manager |
| Dewaweb database | cPanel -> MySQL Database Wizard, then phpMyAdmin |
| Dewaweb HTTPS | cPanel -> SSL/TLS Status -> Run AutoSSL |
| Update the live site | `git push`, then cPanel -> Git Version Control -> Update from Remote |
## Key paths

| Item | Path |
|---|---|
| Your projects | `~/www` |
| Nginx sites | `/etc/nginx/sites-available/` |
| PHP-FPM pool | `/etc/php/8.5/fpm/pool.d/www.conf` |
| php.ini (web) | `/etc/php/8.5/fpm/php.ini` |
| MariaDB tuning | `/etc/mysql/mariadb.conf.d/99-lowmem.cnf` |
| ZRAM config | `/etc/systemd/zram-generator.conf` |
| Hosts file | `/etc/hosts` |

<div style="page-break-after: always;"></div>

# Appendix B: How to Print This Book

**Method 1: HTML through Chromium (lightest, recommended)**

```bash
$ sudo apt install -y pandoc
$ pandoc LMDE7_Onebuild_Ultimate_Guide.md -s --toc -o handbook.html
```

Open `handbook.html` in Chromium and press **Ctrl+P**. The page-break markers between chapters are respected. Choose "Save as PDF" or your printer. Turn off "Headers and footers" for a clean result. For a bound book, print double-sided.

**Method 2: VS Code**

Install the extension *Markdown PDF* (yzane), open the `.md` file, press **Ctrl+Shift+P**, and run **Markdown PDF: Export (pdf)**.

**Tip:** print the Cheat Sheet (Appendix A) on its own page and keep it next to your keyboard.

---

*End of handbook.*
