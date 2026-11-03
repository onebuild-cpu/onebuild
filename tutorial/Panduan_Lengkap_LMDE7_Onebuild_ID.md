---
title: "Panduan Lengkap LMDE 7 + Onebuild"
subtitle: "Dari PC kosong sampai situs web live di Dewaweb"
---

# Panduan Lengkap LMDE 7 + Onebuild

**Instal, debloat, dan siapkan LMDE 7 "Gigi" - bangun server PHP lokal - pelajari MVC Onebuild dengan membuat blog - simpan kode Anda di GitHub - deploy ke shared hosting Dewaweb**

Mesin target: Core i3 (generasi ke-3) / 4 GB DDR3 / GeForce 8600 GT
Sistem dasar: Linux Mint Debian Edition 7 "Gigi" (berbasis Debian 13 "trixie")
Hosting produksi: Dewaweb (cPanel + LiteSpeed Enterprise)

---

## Cara Memakai Buku Ini

* Kerjakan bab **secara berurutan**. Setiap bab diakhiri dengan titik periksa. Jangan lewati titik periksa: bab berikutnya menganggap titik periksa itu sudah terpenuhi.
* Baris yang diawali `$` diketik di terminal. Jangan ketik tanda `$`-nya.
* Teks dalam `<tanda kurung siku>` adalah bagian yang harus Anda ganti dengan nilai Anda sendiri.
* Buka terminal dengan **Ctrl+Alt+T**.
* Pengguna biasa Anda harus punya hak `sudo` (bawaan di LMDE).
* Bab 12 adalah tutorial cetak: setiap file kode diberi label `FILE: ...` dan tertulis FILE BARU atau UBAH FILE.
* Perintah, nama file, dan kode **sengaja tidak diterjemahkan** agar bisa disalin dan dijalankan apa adanya.

## Perjalanan dalam Empat Bagian

| Bagian | Bab | Hasil |
|---|---|---|
| I. Instal dan siapkan LMDE | 1 - 7 | Desktop ramping dan cepat dengan aplikasi pilihan Anda dan ZRAM |
| II. Server web lokal | 8 - 10 | Nginx, PHP 8.5, MariaDB, dan domain `nama.test` |
| III. Belajar Onebuild | 11 - 13 | Onebuild berjalan lokal, blog lengkap (CRUD) buatan Anda, dan proyek baru Anda sendiri |
| IV. Git, produksi, deployment | 14 - 18 | Kode Anda di GitHub dan situs Anda live di Dewaweb |

## Hasil Akhir yang Akan Anda Miliki

| Kategori | Perangkat lunak |
|---|---|
| Desktop | Cinnamon (bawaan) dan XFCE |
| Editor / IDE | Mousepad, Visual Studio Code |
| Peramban | Chromium (Google Chrome opsional) |
| Media | VLC, Cheese |
| Perkantoran | Hanya LibreOffice Writer, Calc, Impress |
| Stack web | Nginx, PHP 8.5 (FPM), MariaDB, Adminer |
| Memori | Swap terkompresi ZRAM |
| Alur kerja dev | Git, kunci SSH, domain lokal `nama.test` |
| Framework | Onebuild (MVC PHP sederhana dengan Bootstrap dan jQuery) |
| Hosting | Shared hosting Dewaweb (cPanel, LiteSpeed) |

## Daftar Isi

**Bagian I: Instal dan Siapkan LMDE**

0. Baca Ini Dulu
1. Instal LMDE 7 di Komputer
2. Siapkan Sistem dan Buat Snapshot
3. Instal Aplikasi yang Anda Inginkan
4. Instal Desktop XFCE
5. Debloat: Hapus Semua yang Lain
6. Layanan dan Catatan Perangkat Keras
7. ZRAM: Swap Terkompresi di RAM

**Bagian II: Server Web Lokal**

8. Nginx, LiteSpeed, dan Dewaweb: Apa Bedanya Sebenarnya
9. Stack Web: Nginx, PHP 8.5, MariaDB
10. Domain Lokal: `apa-saja.test`

**Bagian III: Belajar Onebuild**

11. Tarik Repositori Onebuild dan Jalankan
12. Belajar MVC Onebuild: Membuat Blog (Tutorial CRUD)
13. Membuat Proyek PHP Baru Anda Sendiri

**Bagian IV: Git, Produksi, dan Deployment**

14. Git dan GitHub: Membuat Repo dan Push
15. Menyiapkan Onebuild untuk Produksi
16. Deploy ke Shared Hosting Dewaweb
17. Rutinitas Harian
18. Pemecahan Masalah dan Pemulihan

**Lampiran**

- A. Contekan Perintah
- B. Cara Mencetak Buku Ini

<div style="page-break-after: always;"></div>

# Bab 0: Baca Ini Dulu

## 0.1 Mengapa kita TIDAK menghapus benar-benar semuanya

Desktop itu sendiri (Cinnamon), manajer file (Nemo), terminal, manajer jaringan, layar login, dan alat pembaruan semuanya juga "aplikasi". Jika Anda menghapusnya (purge), boot berikutnya berakhir di layar hitam atau prompt teks.

Jadi aturan buku ini adalah:

> **Hapus setiap aplikasi pengguna. Pertahankan sistem minimum yang membuat komputer tetap bisa dipakai.**

**Yang sengaja kita pertahankan:**

| Dipertahankan | Alasan |
|---|---|
| Cinnamon, Nemo, GNOME Terminal | Desktop, manajer file, terminal Anda |
| LightDM + Slick Greeter | Layar login |
| NetworkManager | Koneksi internet |
| Update Manager, Timeshift | Pembaruan aman dan snapshot |
| System Monitor, Settings | Anda perlu memantau RAM |
| CUPS (pencetakan) | Anda ingin **mencetak buku ini** |

## 0.2 Fakta penting tentang LMDE

1. LMDE 7 dibangun di atas **Debian 13 "trixie"**.
2. LMDE melaporkan nama kode miliknya sendiri, `gigi`, di beberapa perintah (misalnya `lsb_release -sc`). Repositori pihak ketiga **tidak** mengenal `gigi`. Setiap kali sebuah repositori meminta nama kode, **ketik `trixie`**. Buku ini sudah melakukannya.
3. Debian 13 membawa **PHP 8.4**. PHP 8.5 berasal dari repositori pihak ketiga tepercaya `packages.sury.org` (dikelola oleh orang yang mem-paket PHP untuk Debian). Bab 9 menambahkannya.

## 0.3 Tentang perangkat keras Anda

* **RAM 4 GB** terbilang pas-pasan untuk peramban plus IDE plus database. Itulah sebabnya buku ini memakai ZRAM, pengaturan database yang ringan, dan baru menyalakan stack web saat Anda membutuhkannya.
* **GeForce 8600 GT**: pakai driver open-source bawaan **nouveau**. Satu-satunya driver proprietary untuk kartu ini (340.xx) tidak bisa dipakai di kernel modern. Jangan coba memasangnya.
* Jika Chromium atau VS Code berkedip atau menampilkan jendela kosong, lihat Bagian 6.3 (menonaktifkan akselerasi GPU).

## 0.4 Tentang tutorial di Bab 12

Bab 12 adalah tutorial CRUD "Build a Blog with Onebuild". **Seluruh kode PHP, SQL, dan HTML persis sama seperti di file tutorial Anda.** Hanya beberapa baris tentang lingkungan kerja (di mana menemukan terminal, alat database mana yang dipakai, di mana log error berada, cara mengaktifkan ekstensi `gd`) yang disesuaikan agar cocok dengan setup LMDE ini.

## 0.5 Batasan jujur buku ini

* Perintah untuk LMDE, Nginx, PHP, MariaDB, ZRAM, dan Git bersifat standar dan dijelaskan langkah demi langkah, tetapi Anda tetap harus membaca setiap hasil simulasi atau pengujian sebelum melanjutkan.
* **Dewaweb:** secara publik didokumentasikan sebagai hosting cPanel dengan LiteSpeed Enterprise dan versi PHP yang bisa dipilih. Versi PHP persisnya yang ditawarkan paket Anda (misalnya apakah 8.5 sudah tersedia), dan apakah paket Anda menyertakan SSH atau fitur Git cPanel, **bergantung pada paket Anda**. Bab 16 memberi tahu di mana harus mencarinya dan memberikan metode yang tetap berfungsi meskipun Anda tidak memiliki satu pun dari itu.
* File `.htaccess` milik repositori Onebuild tidak terlihat saat buku ini ditulis. Karena itu Bab 8 dan 16 memuat pengujian yang membuktikan situs Anda aman, alih-alih sekadar berasumsi.

<div style="page-break-after: always;"></div>

# BAGIAN I: INSTAL DAN SIAPKAN LMDE

# Bab 1: Instal LMDE 7 di Komputer

> **Peringatan.** "Instalasi otomatis" **menghapus seluruh isi disk**. Salin dulu semua yang ingin Anda simpan ke disk lain atau flashdisk.

## 1.1 Periksa persyaratan

| Item | Dibutuhkan | Mesin Anda |
|---|---|---|
| CPU | Hanya 64-bit | Core i3 generasi ke-3 adalah 64-bit: OK |
| RAM | Minimal 2 GB, disarankan 4 GB | 4 GB: OK |
| Disk | Minimal 20 GB, disarankan 100 GB | Periksa ukuran disk Anda |
| Layar | 1024 x 768 | OK |

LMDE 7 tidak tersedia dalam versi 32-bit.

## 1.2 Unduh ISO

1. Di komputer lain (atau sistem Anda saat ini) buka **https://linuxmint.com/download_lmde.php**.
2. Pilih **LMDE 7 "Gigi" Cinnamon 64-bit** dan unduh file `.iso` (sekitar 2,8 GB). Pilih mirror yang dekat dengan Anda (Asia).
3. Di halaman yang sama, temukan **checksum SHA256** resmi untuk ISO tersebut.

## 1.3 Verifikasi unduhan

Unduhan yang rusak menyebabkan galat installer yang aneh. Di terminal, masuk ke folder tempat ISO berada lalu jalankan:

```bash
$ sha256sum lmde-7-cinnamon-64bit.iso
```

(Gunakan nama file yang sebenarnya.) Bandingkan hasilnya yang panjang dengan checksum di halaman resmi. Keduanya harus **identik**. Jika berbeda, unduh ulang. Di Windows, gunakan PowerShell: `Get-FileHash <file> -Algorithm SHA256`.

## 1.4 Membuat flashdisk instalasi

Anda memerlukan flashdisk **minimal 4 GB**. Semua isinya akan terhapus.

| Alat | Berjalan di | Catatan |
|---|---|---|
| balenaEtcher | Windows, macOS, Linux | Paling mudah: pilih ISO, pilih USB, Flash |
| Rufus | Windows | Jika ditanya, pilih "DD Image mode" |
| USB Image Writer | Linux Mint | Klik kanan ISO, "Make Bootable USB Stick" |

Berhati-hatilah memilih **flashdisk** dan bukan hard disk.

## 1.5 Boot komputer dari USB

1. Colokkan flashdisk ke desktop lalu restart.
2. Begitu layar menyala, tekan berulang **tombol menu boot**. Tombolnya tergantung motherboard: umumnya **F12**, **F11**, **F8**, atau **Esc**. Tombol **setup** biasanya **Del** atau **F2**.
3. Pilih flashdisk (jika muncul dua entri, coba dulu yang diawali "UEFI", jika tidak, pilih yang namanya biasa).
4. Jika komputer memakai UEFI dan menolak boot dari flashdisk, masuk ke setup dan **nonaktifkan Secure Boot**, lalu coba lagi.

## 1.6 Sesi live

USB akan boot ke desktop LMDE yang berfungsi tanpa mengubah disk. Periksa bahwa keyboard, mouse, dan (jika Anda memakainya) jaringan berfungsi.

**Layar hitam atau grafis berantakan?** Ini bisa terjadi pada GeForce 8600 GT yang sudah tua.

* Jika menu boot punya entri **compatibility mode**, gunakan itu.
* Jika tidak, di menu boot tekan tombol edit (**Tab** pada mode BIOS, **e** pada mode UEFI), tambahkan kata `nomodeset` di akhir baris yang diawali `linux`, lalu boot (Enter atau F10).
* Setelah instalasi, driver **nouveau** bawaan akan dipakai dan `nomodeset` tidak diperlukan lagi.

## 1.7 Jalankan installer

Klik dua kali **Install Linux Mint** di desktop live lalu ikuti halamannya:

1. **Bahasa.**
2. **Tata letak keyboard.** Gunakan kotak uji untuk mengetik beberapa simbol.
3. **Zona waktu.** Untuk Jakarta pilih entri Asia / Jakarta.
4. **Jenis instalasi:**
   * **Instalasi otomatis:** menghapus disk dan memasang semuanya. Terbaik untuk komputer khusus pengembangan.
   * **Partisi manual:** hanya jika Anda ingin dual-boot atau tata letak khusus.
   * Biarkan **enkripsi disk** mati kecuali Anda membutuhkannya; enkripsi memperlambat CPU lama.
5. **Swap.** Jika installer membuat partisi swap atau file swap, biarkan saja. ZRAM (Bab 7) akan diprioritaskan di atasnya.
6. **Akun pengguna.** Isi nama Anda, nama komputer, nama pengguna, dan kata sandi. Biarkan **login otomatis mati**.
7. **Bootloader (GRUB).** Pasang di **seluruh disk** (misalnya `/dev/sda`), bukan di nomor partisi seperti `/dev/sda1`.
8. Tunggu. Instalasi memakan waktu sekitar 10 sampai 20 menit.
9. Saat diminta, pilih **Restart now**, lalu **cabut flashdisk** saat diperintahkan.

## 1.8 Boot pertama

1. Login dan tutup jendela Welcome.
2. Hubungkan ke internet (kabel paling baik).
3. Opsional tetapi berguna di Indonesia: **Menu -> Software Sources** lalu pilih mirror yang cepat untuk sistem dasar.
4. Lanjut ke Bab 2 (perbarui semuanya).

**Titik periksa:** LMDE boot ke layar login, Anda bisa login, dan jaringan berfungsi.

<div style="page-break-after: always;"></div>

# Bab 2: Siapkan Sistem dan Buat Snapshot

## 2.1 Perbarui semuanya

```bash
$ sudo apt update && sudo apt upgrade -y
```

Jika proses upgrade menyebut beberapa paket "kept back" (ditahan), jalankan:

```bash
$ sudo apt full-upgrade -y
```

## 2.2 Instal alat dasar yang dipakai nanti

```bash
$ sudo apt install -y curl wget git gpg ca-certificates \
    lsb-release apt-transport-https unzip acl
```

## 2.3 Buat snapshot Timeshift (tombol undo Anda)

Timeshift harus dikonfigurasi sekali lewat jendelanya sebelum baris perintahnya bisa dipakai:

1. Buka **Menu -> Timeshift**. Jalankan wizard setup. Pilih **RSYNC**, pilih disk untuk snapshot, selesai.
2. Lalu buat snapshot:

```bash
$ sudo timeshift --create --comments "before debloat" --tags D
```

Jika terjadi masalah di bab-bab berikutnya, Anda bisa memulihkan snapshot ini (Bab 18).

## 2.4 Reboot

```bash
$ sudo reboot
```

**Titik periksa:** sistem sudah diperbarui, snapshot sudah ada.

<div style="page-break-after: always;"></div>

# Bab 3: Instal Aplikasi yang Anda Inginkan

> **Urutan itu penting.** Instal dulu apa yang Anda inginkan, baru hapus sisanya di Bab 5. Dengan begitu apt tidak akan menghapus sesuatu yang masih Anda butuhkan.

## 3.1 Mousepad, VLC, Cheese

```bash
$ sudo apt install -y mousepad vlc cheese
```

## 3.2 LibreOffice: hanya Writer, Calc, Impress

```bash
$ sudo apt install -y libreoffice-writer libreoffice-calc \
    libreoffice-impress libreoffice-gtk3
```

Opsional (antarmuka dan bantuan bahasa Indonesia):

```bash
$ sudo apt install -y libreoffice-l10n-id libreoffice-help-id
```

Jika LibreOffice sudah terpasang (di LMDE baru memang sudah), bagian lainnya (Base, Draw, Math) dihapus di Bab 5.

## 3.3 Peramban: Chromium (disarankan)

```bash
$ sudo apt install -y chromium
```

**Opsional: Google Chrome sebagai pengganti (atau pendamping) Chromium.** Chrome menambahkan repositori pembaruannya sendiri.

```bash
$ wget -O chrome.deb \
  https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
$ sudo apt install -y ./chrome.deb
$ rm chrome.deb
```

Pada RAM 4 GB, memakai **hanya satu** peramban itu bijak.

## 3.4 Visual Studio Code

```bash
$ wget -O vscode.deb \
  "https://code.visualstudio.com/sha/download?build=stable&os=linux-deb-x64"
$ sudo apt install -y ./vscode.deb
$ rm vscode.deb
```

Paket ini menambahkan repositori Microsoft, sehingga VS Code diperbarui lewat `apt upgrade` yang biasa.

**Tips hemat RAM untuk VS Code:**

* Pasang hanya ekstensi yang Anda butuhkan. Paket PHP yang bagus: *PHP Intelephense*, *GitLens* (opsional).
* Jangan membuka lebih dari satu jendela VS Code.
* Tutup panel terminal terintegrasi saat tidak dipakai.

**IDE alternatif yang lebih ringan:** `sudo apt install -y geany` (terbuka dalam waktu kurang dari satu detik).

**Titik periksa:** Anda bisa membuka Mousepad, VLC, Cheese, LibreOffice Writer, Chromium, dan VS Code dari menu.

<div style="page-break-after: always;"></div>

# Bab 4: Instal Desktop XFCE

Anda akan memiliki **kedua** desktop, Cinnamon dan XFCE, dan bisa memilih saat login.

```bash
$ sudo apt install -y xfce4 xfce4-terminal xfce4-whiskermenu-plugin
```

* `xfce4` sudah termasuk manajer file Thunar dan panelnya.
* Jika installer menanyakan display manager mana yang dipakai, pilih **lightdm**.

## 4.1 Berpindah desktop

1. Logout.
2. Di layar login, klik **ikon sesi** (ikon kecil di dekat nama pengguna atau di bilah bawah/atas).
3. Pilih **Xfce Session** atau **Cinnamon**, lalu login.

## 4.2 Pakai yang mana?

Cinnamon saat diam memakai sekitar 600 sampai 900 MB RAM; XFCE sekitar 400 sampai 500 MB. Pada perangkat keras Anda, **pakai XFCE di hari-hari pengembangan** dan simpan Cinnamon sebagai cadangan.

Periksa memori kapan saja:

```bash
$ free -h
```

**Titik periksa:** Anda bisa login ke kedua desktop.

<div style="page-break-after: always;"></div>

# Bab 5: Debloat: Hapus Semua yang Lain

> **Baca sebelum mulai.** Bab ini memakai skrip yang (1) hanya menghapus paket yang benar-benar terpasang, (2) melakukan simulasi dulu, dan (3) **membatalkan diri secara otomatis** jika apt akan menghapus sesuatu yang dilindungi (desktop, jaringan, login, atau aplikasi yang Anda pilih di Bab 3).

## 5.1 Lihat apa saja yang terpasang (opsional)

```bash
$ apt-mark showmanual | less
```

Tekan `q` untuk keluar.

## 5.2 Buat skrip debloat

Buat file `~/debloat.sh`:

```bash
$ mousepad ~/debloat.sh
```

Tempel persis seperti ini, lalu simpan:

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

Buat agar bisa dieksekusi:

```bash
$ chmod +x ~/debloat.sh
```

## 5.3 Jalankan simulasi, lalu yang sebenarnya

```bash
$ ~/debloat.sh
$ less /tmp/will_remove.txt
```

Baca daftarnya. Jika Anda sudah setuju:

```bash
$ ~/debloat.sh --run
```

**Jika skrip membatalkan diri** dan baris terlindungi yang ditampilkan adalah `libreoffice-impress`, berarti Impress bergantung pada Draw di sistem Anda. Hapus `libreoffice-draw` dari daftar `CANDIDATES` lalu jalankan lagi. Untuk pembatalan karena sebab lain, hapus kandidat yang menyebabkannya.

## 5.4 Tandai paket yang dipertahankan, lalu autoremove dengan hati-hati

`autoremove` menghapus paket yang dianggap apt sebagai yatim (orphan). Bagian desktop yang dipasang lewat "meta package" bisa tampak seperti yatim, jadi tandai dulu yang penting sebagai terpasang manual:

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

Sekarang **simulasikan** autoremove dan bacalah:

```bash
$ sudo apt-get -s autoremove --purge | grep '^Remv'
```

Jika Anda melihat `cinnamon`, `nemo`, `lightdm`, `network-manager`, `xfce4`, atau `mintupdate` dalam daftar itu, **berhenti** dan jalankan `sudo apt-mark manual <nama>` untuk paket tersebut. Jika daftarnya hanya berisi hal-hal yang tidak Anda butuhkan:

```bash
$ sudo apt autoremove --purge -y
$ sudo apt clean
```

## 5.5 Opsional: hapus Flatpak

Lewati langkah ini jika Anda suatu saat ingin memakai aplikasi Flatpak.

```bash
$ flatpak uninstall --all -y
$ sudo apt-get -s purge flatpak | grep '^Remv'
```

Jika simulasi menampilkan `mintinstall` (Software Manager), pertahankan Flatpak atau terima kehilangan Software Manager. Jika Anda menerimanya:

```bash
$ sudo apt purge -y flatpak
$ sudo apt autoremove --purge -y
```

## 5.6 Reboot dan verifikasi

```bash
$ sudo reboot
```

Setelah login:

```bash
$ free -h
$ systemctl --failed
```

**Titik periksa:** desktop boot, jaringan berfungsi, aplikasi pilihan Anda berjalan, dan `systemctl --failed` tidak menampilkan sesuatu yang penting.

<div style="page-break-after: always;"></div>

# Bab 6: Layanan dan Catatan Perangkat Keras

## 6.1 Nonaktifkan layanan latar belakang yang tidak Anda pakai

```bash
$ sudo systemctl disable --now avahi-daemon cups-browsed ModemManager
```

* **JANGAN nonaktifkan `cups`** jika Anda ingin mencetak buku ini dari komputer ini.
* Biarkan `bluetooth` aktif jika Anda memakai perangkat Bluetooth. Jika tidak:
  `sudo systemctl disable --now bluetooth`

Jika nama layanan "not found", abaikan. Artinya layanan itu memang tidak terpasang.

## 6.2 Lihat apa yang memakai RAM Anda

```bash
$ ps aux --sort=-%mem | head -n 10
```

## 6.3 Jika Chromium atau VS Code tampak bermasalah (GPU lama)

Jalankan keduanya tanpa akselerasi GPU:

```bash
$ chromium --disable-gpu
$ code --disable-gpu
```

Untuk menjadikannya permanen di VS Code: tekan **Ctrl+Shift+P**, jalankan **Preferences: Configure Runtime Arguments**, lalu tambahkan:

```json
"disable-hardware-acceleration": true
```

<div style="page-break-after: always;"></div>

# Bab 7: ZRAM: Swap Terkompresi di RAM

## 7.1 Apa yang dilakukan ZRAM

ZRAM membuat perangkat swap **di dalam RAM** yang menyimpan data dalam bentuk terkompresi (biasanya 2:1 sampai 3:1). Saat memori penuh, sistem mengompresi halaman yang menganggur alih-alih membeku karena menulis ke disk yang lambat. Pada mesin 4 GB, ini adalah penyetelan paling berguna.

Kita memakai `systemd-zram-generator`, metode modern di Debian.

## 7.2 Instal

```bash
$ sudo apt install -y systemd-zram-generator
```

## 7.3 Konfigurasi

```bash
$ sudo mousepad /etc/systemd/zram-generator.conf
```

Isinya:

```ini
[zram0]
zram-size = ram / 2
compression-algorithm = zstd
swap-priority = 100
fs-type = swap
```

* `zram-size = ram / 2` membuat perangkat ZRAM 2 GB (sekitar 4 GB swap efektif setelah kompresi). RAM hanya terpakai seiring perangkat itu terisi.
* Jika Anda masih kehabisan memori, Anda bisa menaikkannya menjadi `ram * 0.75`.
* `swap-priority = 100` membuat sistem lebih memilih ZRAM dibanding file swap atau partisi swap di disk.

## 7.4 Setel kernel untuk ZRAM

Dengan ZRAM, nilai swappiness yang **tinggi** itu benar, karena melakukan swap ke RAM terkompresi murah biayanya.

```bash
$ sudo mousepad /etc/sysctl.d/99-zram.conf
```

Isinya:

```ini
vm.swappiness = 100
vm.page-cluster = 0
vm.vfs_cache_pressure = 50
```

## 7.5 Terapkan dan verifikasi

```bash
$ sudo sysctl --system
$ sudo systemctl daemon-reload
$ sudo systemctl start systemd-zram-setup@zram0.service
$ zramctl
$ swapon --show
```

Yang diharapkan: `zramctl` menampilkan perangkat `/dev/zram0` dengan algoritma `zstd`, dan `swapon --show` menampilkan `/dev/zram0` dengan prioritas `100`.

Jika tidak ada yang muncul, reboot sekali lalu jalankan kedua perintah pemeriksaan itu lagi.

**Titik periksa:** ZRAM aktif di urutan teratas daftar `swapon --show`.

<div style="page-break-after: always;"></div>

# BAGIAN II: SERVER WEB LOKAL

# Bab 8: Nginx, LiteSpeed, dan Dewaweb: Apa Bedanya Sebenarnya

Anda bertanya: *Dewaweb memakai LiteSpeed. Apakah itu sama dengan Nginx? Apakah saya sebaiknya memasang LiteSpeed di LMDE saya sebagai ganti Nginx?*

## 8.1 Jawaban singkat

* **Keduanya bukan program yang sama.** Keduanya web server yang cepat dan "event-driven", tetapi cara kerjanya berbeda (lihat 8.2).
* **Jangan pasang LiteSpeed di LMDE 4 GB Anda.** Tetap pakai Nginx. Yang menentukan lancarnya deployment bukan merek web server, melainkan lima hal yang bisa Anda kendalikan (lihat 8.3).
* Satu-satunya perbedaan nyata yang bisa menjebak Anda adalah **`.htaccess`**: LiteSpeed membacanya, Nginx tidak pernah. Bagian 8.4 menunjukkan cara menanganinya.

## 8.2 Ketiga server berdampingan

| | Nginx (LMDE Anda) | LiteSpeed Enterprise (Dewaweb) | OpenLiteSpeed (edisi gratis) |
|---|---|---|---|
| Membaca `.htaccess` | **Tidak** | **Ya** (pengganti langsung Apache dan memuat konfigurasi Apache) | Sebagian (aturan rewrite dan sebagian kecil direktif, harus diaktifkan) |
| Cara PHP berjalan | PHP-FPM | LSAPI (LSPHP) | LSAPI |
| Gaya konfigurasi | File teks di `/etc/nginx` | Dikelola oleh cPanel/WHM | Layar admin web sendiri |
| Lisensi | Gratis, open source | Lisensi komersial berbayar (dipegang oleh penyedia hosting) | Gratis, open source |
| Siapa yang mengubah pengaturan | Anda | Dewaweb dan cPanel | Anda |
| Cocok untuk PC 4 GB Anda | Bagus, ringan | Tidak ditawarkan sebagai instalasi rumahan gratis | Mungkin, tetapi lebih banyak yang perlu dipelajari dan dirawat |

## 8.3 Apa yang sebenarnya menentukan apakah situs Anda berperilaku sama

Lupakan merek server dan samakan lima hal ini:

1. **Versi PHP dan ekstensi.** PHP lokal Anda harus memiliki versi mayor yang sama dengan yang Anda pilih di Dewaweb, dan memiliki ekstensi yang sama (`pdo_mysql`, `mbstring`, `gd` dengan WebP, `curl`, `zip`, `intl`). Bab 10 memungkinkan Anda menjalankan versi PHP yang berbeda untuk setiap situs, sehingga Anda bisa menguji versi persis yang akan dipakai online.
2. **Aturan `.htaccess`.** Setiap aturan yang penting (routing, memblokir file, header) harus memiliki padanan di Nginx secara lokal. Lihat 8.4.
3. **Izin file.** Secara lokal PHP berjalan sebagai `www-data`. Di cPanel/LiteSpeed PHP berjalan sebagai **pengguna akun hosting Anda**. Jadi secara lokal `storage` butuh izin tulis grup, sedangkan online folder `755` yang sederhana sudah cukup. Jangan pernah memakai `777` online.
4. **Batasan.** Ukuran unggahan, memori, dan batas waktu. Samakan secara lokal (Bab 9, Bagian 9.9) dan periksa di cPanel.
5. **HTTP vs HTTPS.** Secara lokal Anda memakai `http://onebuild.test` biasa. Online Anda memakai `https://`. Pengaturan yang bergantung pada HTTPS (cookie aman, pengalihan) bisa berperilaku berbeda, jadi selalu uji di subdomain staging sebelum go live (Bagian 8.5).

## 8.4 Menerjemahkan `.htaccess` ke Nginx

Buka file `.htaccess` di root proyek Onebuild Anda dan bacalah. Lalu gunakan tabel ini. Pembantu `newsite` dari Bab 10 sudah memuat baris pertama, kedua, dan terakhir.

| Gagasan `.htaccess` | Padanan Nginx | Sudah ada di `newsite`? |
|---|---|---|
| `RewriteRule` yang mengarahkan semuanya ke `index.php` | `try_files $uri $uri/ /index.php?$query_string;` | Ya |
| Menyembunyikan dotfile seperti `.env`, `.git` | `location ~ /\.(?!well-known) { deny all; }` | Ya |
| Tanpa eksekusi PHP di `uploads` | `location ^~ /uploads/ { location ~ \.php$ { return 403; } }` | Ya |
| `Options -Indexes` (tanpa daftar isi folder) | `autoindex off;` (ini sudah bawaan) | Ya (bawaan) |
| `Header set ...` (header keamanan) | `add_header Name "value" always;` | Tidak |
| `ExpiresByType` (cache peramban) | `expires 30d;` di dalam `location` untuk file statis | Tidak |
| `php_value upload_max_filesize` | Ubah `php.ini` atau pool FPM (Bab 9) | Dilakukan di 9.9 |
| Memblokir `app/`, `config/`, `storage/` | Tidak perlu secara lokal, karena root situs adalah folder `public` | tidak berlaku |

Catatan: ketika Onebuild menambahkan header keamanan (misalnya aturan nonce di tutorial) dari **kode PHP**, header itu bekerja di server mana pun dan tidak membutuhkan apa pun di sini. Hanya header yang ditulis di `.htaccess` yang perlu salinan Nginx.

## 8.5 Jaring pengaman: subdomain staging di Dewaweb

Buat subdomain gratis seperti `staging.domainanda.com` di Dewaweb dan lakukan deploy ke sana **terlebih dahulu** (Bab 16). Subdomain itu menjalankan server LiteSpeed asli dengan PHP asli, penanganan `.htaccess` asli, dan HTTPS asli. Apa pun yang tidak bisa ditunjukkan Nginx lokal Anda akan muncul di sana, sebelum pengunjung melihatnya.

## 8.6 Kapan OpenLiteSpeed masuk akal?

Hanya jika proyek Anda sangat bergantung pada aturan `.htaccess` yang sulit diterjemahkan, atau Anda ingin salinan persis produksi di rumah. Harganya di mesin 4 GB adalah pemakaian RAM lebih banyak, satu alat admin lagi untuk dipelajari, dan perawatan manual. Untuk Onebuild, yang routing-nya hanya satu front controller di folder `public`, Nginx dengan baris `try_files` sudah cukup. (Jika suatu saat Anda membutuhkan OpenLiteSpeed, itu bisa ditambahkan pada edisi panduan ini yang akan datang.)

**Titik periksa:** Anda tahu bahwa LiteSpeed bukan Nginx, bahwa Anda tetap memakai Nginx, dan bahwa Anda akan menguji di subdomain staging.

<div style="page-break-after: always;"></div>

# Bab 9: Stack Web: Nginx, PHP 8.5, MariaDB

## 9.1 Instal Nginx dan MariaDB

```bash
$ sudo apt install -y nginx mariadb-server
```

## 9.2 Tambahkan repositori PHP 8.5 (Sury)

Debian 13 hanya memiliki PHP 8.4, jadi tambahkan repositori Sury. **Kita menulis `trixie` secara manual karena nama kode LMDE sendiri adalah `gigi`.**

```bash
$ sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb \
    https://packages.sury.org/debsuryorg-archive-keyring.deb
$ sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
$ echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" \
    | sudo tee /etc/apt/sources.list.d/php-sury.list
$ sudo apt update
```

Periksa bahwa PHP 8.5 tersedia:

```bash
$ apt-cache policy php8.5-fpm
```

Anda akan melihat "Candidate" yang diawali `8.5`.

## 9.3 Instal PHP 8.5 dengan FPM dan ekstensi umum

```bash
$ sudo apt install -y php8.5-fpm php8.5-cli php8.5-mysql \
    php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip \
    php8.5-intl php8.5-gd php8.5-bcmath
```

Jadikan 8.5 perintah `php` bawaan lalu verifikasi:

```bash
$ sudo update-alternatives --set php /usr/bin/php8.5
$ php -v
```

Opsional: Composer (hanya jika proyek memiliki `composer.json`):

```bash
$ sudo apt install -y composer
$ php -v
```

Jika `php -v` sekarang menampilkan 8.4, jalankan lagi baris `update-alternatives`.

## 9.4 Membuat PHP-FPM hemat RAM

Ubah file pool:

```bash
$ sudo mousepad /etc/php/8.5/fpm/pool.d/www.conf
```

Cari dan atur nilai-nilai berikut (sudah ada di dalam file; ubah saja):

```ini
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 10s
```

Restart:

```bash
$ sudo systemctl restart php8.5-fpm
```

## 9.5 Membuat MariaDB hemat RAM

```bash
$ sudo mousepad /etc/mysql/mariadb.conf.d/99-lowmem.cnf
```

Isinya:

```ini
[mysqld]
innodb_buffer_pool_size = 128M
key_buffer_size         = 16M
max_connections         = 30
performance_schema      = OFF
```

Restart dan amankan:

```bash
$ sudo systemctl restart mariadb
$ sudo mariadb-secure-installation
```

Jawaban untuk mesin pengembangan: kata sandi root saat ini kosong (tekan Enter); autentikasi `unix_socket`: **Y**; hapus pengguna anonim: **Y**; larang login root dari jarak jauh: **Y**; hapus database test: **Y**; muat ulang privilege: **Y**.

## 9.6 Buat pengguna database untuk pengembangan

Di Debian, akun root MariaDB dipakai lewat `sudo`, bukan dengan kata sandi.

```bash
$ sudo mariadb
```

Di dalam prompt MariaDB (ganti kata sandinya):

```sql
CREATE USER 'dev'@'localhost' IDENTIFIED BY 'ChangeThisPassword';
GRANT ALL PRIVILEGES ON *.* TO 'dev'@'localhost';
EXIT;
```

Pengguna ini hanya bisa terhubung dari komputer Anda sendiri (`localhost`), yang cukup untuk pengembangan lokal. Jangan pernah memakai pengaturan ini di server publik.

## 9.7 Hapus situs bawaan Nginx dan izinkan akses folder home

```bash
$ sudo rm -f /etc/nginx/sites-enabled/default
$ chmod 711 ~
```

`chmod 711 ~` memungkinkan web server melintas *melalui* folder home Anda untuk mencapai `~/www` tanpa bisa melihat daftar file Anda.

## 9.8 Uji

```bash
$ sudo systemctl enable --now nginx php8.5-fpm mariadb
$ sudo nginx -t
$ systemctl status nginx php8.5-fpm mariadb --no-pager | grep Active
```

Ketiganya harus menampilkan `active (running)`.

## 9.9 Naikkan batas unggahan (diperlukan untuk gambar blog)

Batas unggahan bawaan PHP kecil. Buat file pengaturan tambahan yang kecil:

```bash
$ sudo mousepad /etc/php/8.5/fpm/conf.d/99-dev.ini
```

Isinya:

```ini
upload_max_filesize = 8M
post_max_size = 8M
```

Restart PHP-FPM:

```bash
$ sudo systemctl restart php8.5-fpm
```

(Nginx punya batasnya sendiri, `client_max_body_size`, yang sudah diatur 8 MB oleh pembantu `newsite` di Bab 10. Tanpa itu, Nginx menjawab "413 Request Entity Too Large" untuk setiap unggahan di atas 1 MB.)

**Titik periksa:** `php -v` menampilkan 8.5, uji konfigurasi Nginx lulus, MariaDB berjalan.

<div style="page-break-after: always;"></div>

# Bab 10: Domain Lokal: `apa-saja.test`

## 10.1 Gagasannya

Untuk setiap proyek yang Anda inginkan:

1. Sebuah folder proyek, dengan folder `public` di dalamnya.
2. Sebuah file "site" Nginx kecil yang berbunyi: *ketika seseorang meminta `nama.test`, sajikan folder ini*.
3. Satu baris di `/etc/hosts` yang berbunyi: *`nama.test` adalah komputer ini*.

`.test` adalah domain yang dicadangkan untuk pengujian, sehingga tidak akan pernah bertabrakan dengan situs web sungguhan.

Kita mengotomatiskan ketiganya dengan satu perintah pembantu bernama **`newsite`**.

## 10.2 Buat perintah pembantu

```bash
$ sudo mousepad /usr/local/bin/newsite
```

Tempel:

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

Buat agar bisa dieksekusi:

```bash
$ sudo chmod +x /usr/local/bin/newsite
```

Mulai sekarang, selalu jalankan dengan `sudo`:

```bash
$ sudo newsite myproject ~/www/myproject/public
```

Lalu buka **http://myproject.test** di peramban.

**Catatan tentang aturan rewrite:** baris `try_files $uri $uri/ /index.php?$query_string;` mengirim setiap URL yang bukan file sungguhan ke `index.php`. Begitulah cara front controller MVC PHP bekerja. Baris ini menggantikan file `.htaccess`, yang hanya dibaca oleh Apache. Nginx mengabaikan `.htaccess`.

## 10.3 Hapus sebuah situs

```bash
$ sudo rm /etc/nginx/sites-enabled/myproject.test
$ sudo rm /etc/nginx/sites-available/myproject.test
$ sudo mousepad /etc/hosts      # delete the "127.0.0.1 myproject.test" line
$ sudo systemctl reload nginx
```

## 10.4 Buat folder proyek Anda

```bash
$ mkdir -p ~/www
```

## 10.5 Pilih versi PHP untuk setiap situs

Argumen ketiga (opsional) dari `newsite` menentukan versi PHP untuk satu situs itu:

```bash
$ sudo newsite myapp ~/www/myapp/public          # PHP 8.5 (default)
$ sudo newsite myapp ~/www/myapp/public 8.4      # PHP 8.4 for this site
```

Versi PHP yang lain harus sudah terpasang dan berjalan (`php8.4-fpm`, lihat Bagian 15.2). Dengan cara inilah Anda menguji dengan versi PHP yang sama dengan yang ditawarkan hosting Anda.

## 10.6 Instal Adminer (layar database Anda)

Adminer adalah pengelola database satu file, alternatif yang sangat ringan untuk phpMyAdmin. Pasang sebagai situs lokal bernama `db.test`:

```bash
$ mkdir -p ~/www/tools/adminer
$ wget -O ~/www/tools/adminer/index.php https://www.adminer.org/latest.php
$ sudo newsite db ~/www/tools/adminer
```

Buka **http://db.test**. Login dengan:

| Kolom | Nilai |
|---|---|
| System | MySQL / MariaDB |
| Server | `localhost` |
| Username | `dev` |
| Password | yang Anda atur di Bagian 9.6 |

Pakailah hanya di komputer Anda sendiri. **Jangan pernah memasang Adminer di situs web yang live.**

**Titik periksa:** `newsite` sudah ada, `~/www` sudah ada, dan `http://db.test` membuka Adminer.

<div style="page-break-after: always;"></div>

# BAGIAN III: BELAJAR ONEBUILD

# Bab 11: Tarik Repositori Onebuild dan Jalankan

Repositori: `https://github.com/onebuild-cpu/onebuild`
Dideskripsikan oleh penulisnya sebagai: *a very simple basic PHP MVC with Bootstrap and jQuery* (MVC PHP dasar yang sangat sederhana dengan Bootstrap dan jQuery).

Dari daftar file repositori, tingkat teratasnya berisi: `app`, `bin`, `config`, `database`, `public`, `storage`, `tutorial`, `views`, ditambah `.env.example`, `.gitignore`, `.htaccess`, `dump_file.py` dan `project_dump.md`. Konsekuensi pentingnya:

* Folder yang disajikan ke web harus **`public`**, bukan root proyek.
* `.env.example` berarti Anda menyalinnya menjadi `.env` dan mengisi detail database Anda.
* `database` kemungkinan berisi skema SQL Anda; `storage` harus bisa ditulisi.
* `tutorial` layak dibaca.
* `bin` berisi pembantu baris perintah (misalnya yang membuat akun admin pertama).

## 11.1 Clone

```bash
$ cd ~/www
$ git clone https://github.com/onebuild-cpu/onebuild.git
$ cd onebuild
$ ls -la
```

Meng-clone repositori publik lewat HTTPS tidak memerlukan login.

## 11.2 Baca file milik proyek itu sendiri

```bash
$ ls tutorial database config bin public
```

Buka file di `tutorial` dengan Mousepad atau VS Code. Periksa bahwa `public/index.php` ada. File itu adalah front controller: setiap alamat melewatinya, dan di dalamnya ada daftar route yang akan Anda ubah di Bab 12.

## 11.3 Buat file `.env`

```bash
$ cp .env.example .env
$ mousepad .env
```

Isi nama database, pengguna (`dev`), dan kata sandi dari Bagian 9.6. Jika ada pengaturan untuk URL situs atau base URL, isi dengan `http://onebuild.test`. Gunakan nama kunci persis seperti yang ada di file.

Selagi belajar di komputer ini, atur juga:

```
APP_ENV=development
```

agar pesan error tampil di layar. **Jangan pernah membiarkan ini aktif di situs web yang live** (Bab 15).

## 11.4 Buat database dan impor tabelnya

```bash
$ sudo mariadb -e "CREATE DATABASE onebuild CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$ ls database
```

Jika `database` berisi file `.sql` (ganti nama filenya):

```bash
$ mariadb -u dev -p onebuild < database/<file>.sql
```

Gunakan nama database yang sama dengan yang Anda ketik di `.env` (baris `DB_NAME`).

## 11.5 Composer (hanya jika proyek memiliki `composer.json`)

```bash
$ ls composer.json && composer install
```

## 11.6 Folder yang harus bisa ditulisi web server

Git tidak menyimpan folder kosong, jadi pastikan folder unggahan ada, lalu beri web server (`www-data`) akses tulis:

```bash
$ mkdir -p public/uploads/images
$ sudo chgrp -R www-data storage public/uploads
$ chmod -R 775 storage public/uploads
```

## 11.7 Periksa bahwa PHP bisa membuat gambar WebP

Blog di Bab 12 mengonversi setiap gambar yang diunggah menjadi WebP. Verifikasi:

```bash
$ php -r 'var_dump(function_exists("imagewebp"));'
```

Harus menampilkan `bool(true)`. Jika tidak: `sudo apt install php8.5-gd` dan `sudo systemctl restart php8.5-fpm`.

## 11.8 Terbitkan sebagai `onebuild.test`

```bash
$ sudo systemctl start nginx php8.5-fpm mariadb
$ sudo newsite onebuild ~/www/onebuild/public
```


(`newsite` dibuat di Bab 10.)

Buka **http://onebuild.test** di Chromium. Halaman beranda seharusnya muncul.

## 11.9 Buat administrator pertama

Halaman admin yang Anda buat di Bab 12 menolak semua orang kecuali peran **root** dan **admin**. Buat satu akun root, sekali saja, di folder proyek:

```bash
$ cd ~/www/onebuild
$ php bin/add_root.php "+628123456789" "YourStrongPassword"
```

Ganti nomor dan kata sandinya dengan milik Anda. Pertahankan tanda kutipnya. Lalu buka **http://onebuild.test/login**, login, dan Anda seharusnya sampai ke `/dashboard`.

## 11.10 Jika tidak berhasil

| Gejala | Perbaikan |
|---|---|
| "Server not found" | `grep test /etc/hosts`; jalankan lagi perintah `newsite` |
| 502 Bad Gateway | `sudo systemctl status php8.5-fpm`; nyalakan |
| 403 Forbidden | Izin folder home: `chmod 711 ~` |
| 404 di setiap halaman | Folder salah: situs harus mengarah ke `.../public` |
| Halaman putih / 500 | `sudo tail -n 30 /var/log/nginx/onebuild.test.error.log` |
| Galat database | Periksa lagi nilai `.env` dan pastikan database sudah ada |
| `add_root.php` tidak bisa terhubung | Pengaturan database sama seperti di atas; pastikan MariaDB berjalan |

**Titik periksa:** `http://onebuild.test` menampilkan aplikasi dan Anda bisa login di `/login`.

<div style="page-break-after: always;"></div>

# Bab 12: Belajar MVC Onebuild: Membuat Blog (Tutorial CRUD)

> **Catatan lingkungan untuk LMDE (baca dulu).**
> * Di bagian tutorial yang menyebut "terminal", tekan **Ctrl+Alt+T** dan jalankan `cd ~/www/onebuild`.
> * Di bagian yang menyebut alat database, pakai **Adminer** di `http://db.test` (dipasang di Bab 10). Login dengan pengguna `dev` dan kata sandi dari Bagian 9.6.
> * Edit file dengan **VS Code** (`code ~/www/onebuild`) atau Mousepad.
> * Setelah mengubah PHP, cukup simpan lalu muat ulang halaman. Setelah mengubah `php.ini`, jalankan `sudo systemctl restart php8.5-fpm`.
> * Log error Anda ada di `/var/log/nginx/onebuild.test.error.log`.
> * Teks tutorial dimulai di bawah ini. Kodenya tidak diubah.

## Baca ini dulu

**Apa yang akan Anda buat.** Sebuah blog kecil di dalam situs Onebuild Anda:

- pengelola **Categories** (tambah, daftar, ubah, hapus)
- pengelola **Articles** (empat aksi yang sama, ditambah unggah gambar)
- dua **halaman publik** tempat pengunjung membaca artikel

Para programmer menyebut keempat aksi itu **CRUD**: **C**reate (buat), **R**ead (baca), **U**pdate (ubah), **D**elete (hapus). Hampir setiap situs web adalah CRUD atas hal yang berbeda-beda (produk, pelanggan, faktur). Pelajari sekali dan Anda bisa memakainya selamanya.

**Berapa lama.** Sekitar 3 sampai 4 jam, dalam beberapa sesi. Situs berfungsi di setiap TITIK PERIKSA, jadi Anda bisa berhenti setelah Bagian mana pun dan melanjutkan di hari lain.

**Cara mengikuti kertas ini**

1. Setiap file diawali label seperti `FILE: app/Models/CategoryModel.php` dan bertuliskan **FILE BARU** atau **UBAH FILE**.
2. Ketik kode persis seperti yang tercetak. PHP ketat soal karakter ini: `;` `{ }` `( )` `[ ]` `' '` `$` `->` `=>`. Satu karakter yang hilang menghasilkan halaman error.
3. Indentasi (spasi di awal baris) hanya untuk memudahkan membaca. Tetap pertahankan, karena membantu Anda menemukan kesalahan.
4. Baris yang diawali `//` atau `*`, atau yang berada di antara `/**` dan `*/`, adalah komentar untuk manusia. Anda boleh tidak mengetiknya.
5. Nama bersifat peka huruf besar-kecil. File `AdminCategoryController.php` harus berisi `class AdminCategoryController`. Huruf sama, kapital sama.
6. Gunakan editor kode seperti VS Code (gratis). Jangan pakai Word.
7. **Pendeteksi salah ketik.** Setelah menyimpan file PHP, buka terminal di folder proyek Anda (tekan Ctrl+Alt+T, lalu ketik `cd ~/www/onebuild`) dan jalankan `php -l app/Helpers/BlogHelper.php`, dengan path file yang baru Anda buat. Hasilnya `No syntax errors detected`, atau memberi tahu baris mana yang bermasalah. Berlaku juga untuk file view.

## Sebelum mulai (daftar periksa)

Centang setiap kotak.

- [ ] Halaman beranda Onebuild Anda terbuka di peramban.
- [ ] Anda bisa login di `/login` dan sampai ke `/dashboard`.
- [ ] Akun Anda memiliki peran **root** atau **admin**. Halaman admin yang akan Anda buat menolak semua orang lainnya. (Akun root dibuat dengan menjalankan `php bin/add_root.php "+628123456789" "YourPassword"` sekali di folder proyek.)
- [ ] Selagi belajar di komputer sendiri, buka file `.env` dan atur `APP_ENV=development`. Pesan error lalu tampil di layar, bukan halaman kosong. Jangan pernah membiarkan ini aktif di situs sungguhan: kembalikan ke `production` saat Anda mempublikasikannya.
- [ ] Anda punya alat database (Adminer di `http://db.test`, dipasang di Bab 10) dan Anda tahu database mana yang dipakai Onebuild. Itu adalah baris `DB_NAME` di `.env`.

## Gambaran besar (baca sekali, 5 menit)

Bayangkan sebuah restoran.

- **Route** = papan menu di pintu. Isinya "alamat ini ditangani oleh pelayan itu". Onebuild menyimpan route-nya di `public/index.php`.
- **Controller** = pelayan. Ia menerima permintaan, memeriksa apakah Anda diizinkan, mengambil apa yang diperlukan, dan membawanya ke meja. Controller berada di `app/Controllers/`.
- **Model** = penjaga gudang. Satu-satunya yang menyentuh database. Model berada di `app/Models/`.
- **View** = piringnya. Halaman HTML yang dilihat pengunjung. View berada di `views/`.

Apa yang terjadi ketika Anda membuka halaman `/dashboard/categories`:

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

**Satu aturan yang akan Anda lihat di mana-mana.** Apa pun yang memiliki formulir membutuhkan **dua** method controller. Satu **menampilkan** formulir (peramban *meminta* sebuah halaman: ini disebut GET). Satunya **menerima** formulir yang sudah diisi (peramban *mengirim* data: ini disebut POST).

- Create = `create()` menampilkan formulir kosong, `store()` menyimpannya
- Read = `index()` menampilkan daftar
- Update = `edit()` menampilkan formulir terisi, `update()` menyimpannya
- Delete = `delete()` menghapus item (sebuah formulir kecil dengan tombol Delete memicunya)

**Istilah yang akan Anda temui**

- **Slug**: versi nama yang ramah web. "My First Post!" menjadi `my-first-post` dan dipakai di alamat `/blog/my-first-post`.
- **Session**: ingatan server tentang siapa yang sedang login.
- **Flash message**: pesan hijau atau merah yang tampil sekali (misalnya "Category created.") lalu dilupakan. Menu atas Onebuild sudah menampilkannya. Anda hanya menulis `$_SESSION['success'] = '...'` atau `$_SESSION['error'] = '...'`.
- **Token CSRF**: kode rahasia yang tersembunyi di dalam setiap formulir. Kode ini membuktikan bahwa formulir berasal dari situs Anda sendiri dan bukan dari halaman jebakan. Setiap formulir yang Anda buat memuatnya, dan setiap aksi yang menerima formulir memeriksanya.
- **Layout**: file yang menampung bagian yang dipakai bersama oleh setiap halaman (menu atas, sidebar, footer).

**Apa yang sudah bisa dilakukan setiap model Onebuild** (gratis, Anda mewarisinya):

- `findAll('name ASC')`: semua baris, terurut
- `findById(5)`: satu baris, atau `null` jika tidak ada
- `insert([...])`: tambah baris (tanggal diisi otomatis)
- `update(5, [...])`: ubah baris
- `delete(5)`: hapus baris
- `query('SELECT ...', [values])`: SQL Anda sendiri, mengembalikan banyak baris
- `queryOne('SELECT ...', [values])`: SQL Anda sendiri, mengembalikan satu baris atau `null`

**File yang akan Anda buat (BARU) atau ubah (UBAH)**

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

## Bagian 1: Tabel database

Tabel itu seperti lembar Excel: kolom di bagian atas, satu baris untuk setiap item.

**Langkah 1.1** Buka Adminer (`http://db.test`), login dengan pengguna `dev`, lalu klik database Onebuild Anda.

**Langkah 1.2** Klik **SQL command** di menu kiri. Tempel kode ini, lalu tekan **Execute**.

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

Baris yang penting:

- `id ... AUTO_INCREMENT`: database memberi nomor setiap baris 1, 2, 3... untuk Anda.
- `slug ... UNIQUE`: dua baris tidak boleh memiliki slug yang sama.
- `category_id`: tautan yang menyatakan sebuah artikel termasuk kategori mana.
- `ON DELETE RESTRICT`: database menolak menghapus kategori yang masih memiliki artikel. Ini melindungi Anda dari terhapusnya artikel secara tidak sengaja.
- `created_at`, `updated_at`: tanggal yang terisi sendiri.

**TITIK PERIKSA 1.** Pada daftar tabel Anda sekarang melihat `categories` dan `articles`, keduanya kosong.

## Bagian 2: File helper

**Helper** adalah kotak perkakas berisi fungsi-fungsi kecil yang tidak dimiliki satu halaman mana pun. Kotak kita punya lima perkakas:

- `makeSlug()` mengubah "My First Post!" menjadi `my-first-post`.
- `imageFolder()` menyatakan di mana gambar disimpan.
- `saveImageAsWebp()` memeriksa gambar yang diunggah, mengonversinya ke WebP (format kecil dan cepat) lalu menyimpannya dengan nama acak.
- `deleteImage()` menghapus file gambar.
- `excerpt()` memotong teks panjang menjadi pendek untuk halaman daftar.

**Langkah 2.1** Buat folder baru bernama `Helpers` di dalam folder `app`.

**Langkah 2.2** Buat file di bawah ini.

**FILE: `app/Helpers/BlogHelper.php`  (FILE BARU)**

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

Yang perlu diperhatikan:

- `namespace App\Helpers;` memberi tahu Onebuild di mana file itu berada. Namespace `App\Helpers` berarti folder `app/Helpers`. Karena itu, Anda tidak pernah perlu menulis `require` untuk class buatan sendiri: Onebuild menemukannya berdasarkan nama.
- `static` berarti Anda bisa memakai perkakas itu langsung, tanpa membuat objek lebih dulu: `BlogHelper::makeSlug('Hello')`.
- `throw new Exception('...')` membunyikan alarm yang membawa pesan. Controller akan menangkapnya dan menampilkan pesan itu kepada pengguna.
- Gambar disimpan di `public/uploads/images`. Folder itu sudah ada di Onebuild dan sudah memblokir dijalankannya program apa pun yang diletakkan di dalamnya, sehingga unggahan tetap aman.

Jalankan pendeteksi salah ketik: `php -l app/Helpers/BlogHelper.php`

## Bagian 3: Model

Model adalah satu-satunya tempat yang berbicara dengan database. Kedua model kita mewarisi semua method gratis (`findAll`, `findById`, `insert`, `update`, `delete`) dan menambahkan beberapa milik sendiri.

**FILE: `app/Models/CategoryModel.php`  (FILE BARU)**

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

**FILE: `app/Models/ArticleModel.php`  (FILE BARU)**

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

Yang perlu diperhatikan:

- `extends Model` berarti "mulai dengan semua yang bisa dilakukan model Onebuild biasa".
- `protected string $table = 'articles';` memberi tahu model tabel mana yang dikelolanya.
- Setiap `?` dalam SQL adalah ruang kosong yang diisi dengan aman oleh nilai yang sesuai dari daftar `[...]`. **Jangan pernah menempelkan teks pengguna langsung ke SQL.** Ruang kosong `?` itulah yang melindungi situs Anda dari SQL injection (trik peretasan klasik).
- `LEFT JOIN` meminjam nama kategori dari tabel lain, sehingga halaman daftar bisa menampilkan nama alih-alih angka.
- `?array` berarti "mengembalikan array, atau `null` jika tidak ada yang ditemukan".

Jalankan pendeteksi salah ketik pada kedua file.

## Bagian 4: Potongan layout bersama

Setiap halaman admin membutuhkan bingkai yang sama: header, menu atas, sidebar, dan footer. Alih-alih mengulang 15 baris di delapan file, kita membuat dua file kecil: satu **membuka** bingkai, satu **menutupnya**. Setiap halaman meletakkan isinya sendiri di antaranya, seperti gambar di dalam bingkai foto.

**FILE: `views/layouts/admin_top.php`  (FILE BARU)**

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

**FILE: `views/layouts/admin_bottom.php`  (FILE BARU)**

```php
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
```

Yang perlu diperhatikan:

- `require_once` berarti "tempel file itu di sini".
- `<?= $base ?>` mencetak alamat dasar situs web Anda. Letakkan di depan setiap tautan dan setiap path file, agar situs berfungsi di folder atau domain mana pun tempatnya berjalan.
- `<?= htmlspecialchars(...) ?>` mencetak teks dengan aman. **Selalu** bungkus teks yang berasal dari database atau dari pengunjung dengan `htmlspecialchars()`. Tanpa itu, pengunjung dapat menyuntikkan kode berbahaya ke halaman Anda.
- `$nonce` adalah tiket sekali pakai yang diwajibkan oleh aturan keamanan Onebuild pada setiap `<script>` dan `<link>` inline. Ingat ini: **`<script>` inline tanpa `nonce="<?= $nonce ?>"` akan diblokir oleh peramban.**

**Langkah 4.3** Tambahkan dua tautan ke sidebar.

**FILE: `views/dashboard/_sidebar.php`  (UBAH FILE)**

Buka file itu dan temukan blok yang sudah ada ini (item menu "Overview"):

```php
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= $base ?>/dashboard">
                    <i class="bi bi-speedometer2"></i> Overview
                </a>
            </li>
```

Tepat **di bawah** blok itu (setelah `</li>` penutupnya) tambahkan:

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

(Bagian `? 'active' : ''` menyalin gaya yang sudah dipakai di file itu: ia menambahkan kata `active` untuk menyorot item menu halaman yang sedang Anda buka.)

**TITIK PERIKSA 4.** Login dan buka `/dashboard`. Sidebar sekarang menampilkan **Categories** dan **Articles**. Mengkliknya menghasilkan halaman "404" untuk saat ini. Itu benar: Anda belum membuat halaman-halaman itu.

## Bagian 5: CRUD Kategori

Kita membangun siklus lengkap untuk kategori lebih dulu karena itu yang paling kecil. Artikel (Bagian 6) memakai pola yang persis sama, dengan beberapa tambahan.

**Langkah 5.1** Controller. Satu class dengan enam aksi yang bisa Anda lihat di peramban (`index`, `create`, `store`, `edit`, `update`, `delete`) ditambah dua method pembantu yang merapikan dan memeriksa data formulir.

**FILE: `app/Controllers/AdminCategoryController.php`  (FILE BARU)**

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

Cara membacanya:

1. `__construct` berjalan ketika Onebuild membuat controller. `parent::__construct($pdo)` menyerahkan koneksi database ke controller dasar Onebuild. Lalu kita membuat model kita.
2. Baris pertama setiap aksi adalah `$this->requireAdmin();`. Siapa pun yang tidak login sebagai admin atau root dikirim ke halaman login. **Lupakan baris ini dan halaman itu terbuka untuk seluruh dunia.**
3. `store`, `update` dan `delete` menerima formulir, jadi mereka memeriksa dua hal lebih dulu: permintaan itu benar-benar POST, dan kode CSRF rahasianya benar (`CsrfProtection::requireValidToken()`). Kode yang salah atau hilang menghentikan semuanya dengan error 419.
4. `$_SESSION['success'] = '...'` menyimpan flash message. `$this->redirect(...)` lalu mengirim peramban kembali ke daftar, tempat pesan muncul satu kali. Mengarahkan ulang setelah menyimpan juga berarti menekan tombol Refresh tidak akan menyimpan dua kali.
5. Ketika datanya salah kita **tidak** mengarahkan ulang. Kita menampilkan formulir lagi dengan apa yang sudah diketik pengguna (`'category' => $category`), jadi tidak ada yang perlu diketik ulang.
6. `readForm()` mengumpulkan dan membersihkan nilai yang diketik. `Validator::sanitize()` adalah perkakas Onebuild yang memangkas spasi, membuang tag HTML, dan memotong teks sampai panjang maksimum. `$_POST['name'] ?? ''` berarti "kolom `name`, atau teks kosong jika tidak ada". Jika kolom slug kosong, nama dipakai untuk membuatnya.
7. `delete()` menolak menghapus kategori yang masih berisi artikel.
8. Angka di `edit(int $id)` berasal dari alamat (`/dashboard/categories/edit/3`). Langkah 5.3 menunjukkan bagaimana route mengoperkannya.

Jalankan pendeteksi salah ketik.

**Langkah 5.2** Keempat view. Buat dulu folder `views/admin` dan `views/admin/categories`.

Formulirnya dibutuhkan dua kali (create dan edit), jadi kita menulisnya sekali di `_form.php`. Nama file yang diawali garis bawah adalah *potongan* yang ditempelkan halaman lain. Ia bukan halaman tersendiri.

**FILE: `views/admin/categories/_form.php`  (FILE BARU)**

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

**FILE: `views/admin/categories/create.php`  (FILE BARU)**

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

**FILE: `views/admin/categories/edit.php`  (FILE BARU)**

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

**FILE: `views/admin/categories/index.php`  (FILE BARU)**

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

Yang perlu diperhatikan pada view:

- `<?php if (...): ?> ... <?php else: ?> ... <?php endif; ?>` dan `<?php foreach (...): ?> ... <?php endforeach; ?>` adalah cara PHP menulis "jika" dan "ulangi untuk setiap baris" di tengah HTML. Setiap `if` dan `foreach` harus ditutup oleh `endif` dan `endforeach`-nya.
- `<?= \App\Core\CsrfProtection::getHiddenInput() ?>` mencetak kode rahasia yang tersembunyi. **Setiap formulir membutuhkannya**, atau controller menjawab dengan error 419.
- **Delete adalah formulir mungil dengan tombol, bukan tautan biasa.** Tautan biasa bisa dipicu oleh halaman jebakan di situs web lain. Formulir dengan kode rahasia tidak bisa.
- `<script>` kecil di bagian bawah menampilkan popup "Are you sure?". Itu JavaScript biasa (tanpa jQuery) dan membawa `nonce="<?= $nonce ?>"`, yang dituntut oleh aturan keamanan Onebuild.
- Tidak ada `style="..."` di mana pun. Aturan keamanan Onebuild memblokir style inline, jadi kita memakai class siap pakai dari Bootstrap (`mb-3`, `col-lg-6`, `d-inline`, dan seterusnya).

**Langkah 5.3** Beri tahu Onebuild alamat mana yang milik method mana. Inilah daftar route.

**FILE: `public/index.php`  (UBAH FILE)**

Gulir ke akhir file dan temukan baris-baris ini:

```php
// Protected route example
$router->addRoute('dashboard', 'DashboardController', 'index');
$router->addRoute('dashboard/profile', 'DashboardController', 'profile');

// Dispatch request - FIXED FOR SUBDIRECTORY
```

Tambahkan blok berikut **di antara** baris `dashboard/profile` dan komentar `// Dispatch request`:

```php
// Admin: categories
$router->addRoute('dashboard/categories', 'AdminCategoryController', 'index');
$router->addRoute('dashboard/categories/create', 'AdminCategoryController', 'create');
$router->addRoute('dashboard/categories/store', 'AdminCategoryController', 'store');
$router->addRoute('dashboard/categories/edit/([0-9]+)', 'AdminCategoryController', 'edit');
$router->addRoute('dashboard/categories/update/([0-9]+)', 'AdminCategoryController', 'update');
$router->addRoute('dashboard/categories/delete/([0-9]+)', 'AdminCategoryController', 'delete');

```

Cara membaca sebuah route: `addRoute('alamat', 'NamaController', 'namaMethod')`. Alamat tidak diawali garis miring. `([0-9]+)` berarti "satu digit atau lebih", dan digit apa pun yang ditemukan diserahkan ke method: alamat `dashboard/categories/edit/3` memanggil `edit(3)`.

**TITIK PERIKSA 5. Coba semuanya.**

1. Buka jendela peramban private/incognito dan kunjungi `/dashboard/categories`. Anda harus dikirim ke halaman login. (Ini membuktikan kunci admin bekerja.)
2. Di jendela biasa Anda, klik **Categories** di sidebar. Anda melihat tabel dengan tulisan "No categories yet."
3. Klik **Add New Category**. Ketik `Technology News`, biarkan Slug kosong, klik **Save Category**. Anda kembali ke daftar dengan pesan hijau, dan slug menampilkan `technology-news`.
4. Tambahkan lagi `Technology News`. Kali ini Anda tetap di formulir, melihat pesan merah bahwa slug sudah dipakai, dan teks yang Anda ketik masih ada.
5. Klik **Edit** pada barisnya. Ubah nama menjadi `Tech` dan slug menjadi `tech`. Klik **Update Category**.
6. Tambahkan kategori bernama `Temporary`. Klik tombol **Delete**-nya. Sebuah popup bertanya "Delete this category?". Klik Cancel: tidak terjadi apa-apa. Klik Delete lagi lalu OK: kategori itu menghilang.

Simpan kategori `Tech` untuk Bagian berikutnya. Jika popup tidak muncul saat Anda mengklik Delete, kemungkinan besar bagian `nonce="<?= $nonce ?>"` pada baris script salah ketik.

## Bagian 6: CRUD Artikel

Artikel mengikuti pola yang persis sama dengan kategori. Ada tiga hal baru:

- sebuah **daftar drop-down** untuk memilih kategori,
- sebuah **unggah gambar** (dikonversi ke WebP dan disimpan di `public/uploads/images`),
- saat edit, gambar lama **dipertahankan** kecuali Anda memilih yang baru (dan file lama dihapus ketika diganti atau ketika artikel dihapus).

**Langkah 6.1** Controller.

**FILE: `app/Controllers/AdminArticleController.php`  (FILE BARU)**

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

Apa yang baru dibandingkan controller kategori:

- `try { ... } catch (Exception $e) { ... }` berarti "coba ini; jika helper membunyikan alarmnya (`throw`), jangan crash, jalankan bagian catch". Di sini bagian catch menyimpan pesan alarm (`$e->getMessage()`) agar bisa ditampilkan dengan warna merah.
- `handleUpload()` melihat `$_FILES['image']`, kotak PHP yang menampung gambar yang diunggah. Ia mengembalikan nama file baru, atau teks kosong jika tidak ada gambar yang dipilih.
- Di `update()`: tanpa gambar baru berarti gambar lama tetap. Gambar baru menggantikan yang lama, dan file lama dihapus agar folder Anda tidak penuh dengan gambar yang tidak terpakai.
- `create()` mengirim Anda ke halaman Categories bila belum ada kategori, karena sebuah artikel membutuhkannya.
- `(int) $_POST['category_id']` memaksa nilainya menjadi bilangan bulat. Jangan pernah mempercayai apa yang datang dari peramban.

Jalankan pendeteksi salah ketik.

**Langkah 6.2** Keempat view. Buat dulu folder `views/admin/articles`.

**FILE: `views/admin/articles/_form.php`  (FILE BARU)**

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

**FILE: `views/admin/articles/create.php`  (FILE BARU)**

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

**FILE: `views/admin/articles/edit.php`  (FILE BARU)**

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

**FILE: `views/admin/articles/index.php`  (FILE BARU)**

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

Apa yang baru di view-view ini:

- Formulir yang mengunggah file **harus** memiliki `enctype="multipart/form-data"` pada tag `<form>`-nya. Tanpa itu gambar tidak pernah sampai.
- Drop-down: untuk setiap kategori kita mencetak sebuah `<option>`. Yang nomornya cocok dengan kategori artikel mendapat kata `selected`, sehingga pilihan yang benar sudah terpilih saat Anda mengedit.
- Tombol "View" pada daftar membuka halaman publik artikel (Anda membuatnya di Bagian 7).

**Langkah 6.3** Tambahkan route artikel.

**FILE: `public/index.php`  (UBAH FILE)**

Tepat di bawah blok kategori yang Anda tambahkan di Langkah 5.3 (dan tetap di atas komentar `// Dispatch request`), tambahkan:

```php
// Admin: articles
$router->addRoute('dashboard/articles', 'AdminArticleController', 'index');
$router->addRoute('dashboard/articles/create', 'AdminArticleController', 'create');
$router->addRoute('dashboard/articles/store', 'AdminArticleController', 'store');
$router->addRoute('dashboard/articles/edit/([0-9]+)', 'AdminArticleController', 'edit');
$router->addRoute('dashboard/articles/update/([0-9]+)', 'AdminArticleController', 'update');
$router->addRoute('dashboard/articles/delete/([0-9]+)', 'AdminArticleController', 'delete');

```

**TITIK PERIKSA 6. Coba semuanya.**

1. Klik **Articles** di sidebar. Anda melihat "No articles yet."
2. Klik **Write New Article**. Title: `My First Post!`. Category: `Tech`. Pilih gambar JPG atau PNG yang kecil. Content: ketik dua baris pendek. Klik **Publish Article**. Anda kembali ke daftar dengan thumbnail dan pesan hijau.
3. Lihat isi folder `public/uploads/images`. Anda menemukan satu file baru yang namanya berakhiran `.webp`. Itulah gambar Anda yang sudah dikonversi.
4. Tulis artikel lain dengan judul yang sama. Anda mendapat pesan merah "slug already used" dan teks Anda tetap ada.
5. Coba unggah file teks yang Anda ganti namanya menjadi `picture.jpg`. Anda mendapat "not a valid image".
6. Klik **Edit** pada artikel pertama. Ubah judul, pilih **tanpa** gambar baru, klik **Update Article**. Thumbnail masih ada.
7. Edit lagi, kali ini pilih gambar yang berbeda. Periksa foldernya: tetap hanya ada satu file `.webp` (yang lama sudah dihapus).
8. Buka **Categories** dan coba hapus `Tech`. Anda mendapat pesan merah: kategori itu masih memiliki artikel.
9. Kembali di **Articles**, pastikan Anda berakhir dengan **dua artikel** di kategori `Tech` (satu dengan gambar, satu tanpa). Anda membutuhkannya untuk Bagian 7. Anda bisa menguji **Delete** pada artikel ketiga yang sementara.

## Bagian 7: Halaman blog publik

Ini adalah halaman yang dilihat pengunjung biasa. Di sini **sengaja tidak ada** `requireAdmin()`: semua orang boleh membaca. Hanya dua aksi yang dibutuhkan: daftar (`index`) dan satu artikel penuh (`show`).

**Langkah 7.1** Controller.

**FILE: `app/Controllers/BlogController.php`  (FILE BARU)**

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

Jika tidak ada artikel yang cocok dengan alamat, kita menjawab dengan halaman 404 Onebuild yang sudah ada dan kode "404" yang tepat. Teks `pageTitle` menjadi judul di tab peramban.

**Langkah 7.2** Kedua view. Buat dulu folder `views/blog`. Halaman-halaman ini memakai bingkai publik normal Onebuild (header, navbar, footer), persis seperti `views/home/index.php`.

**FILE: `views/blog/index.php`  (FILE BARU)**

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

**FILE: `views/blog/show.php`  (FILE BARU)**

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

Yang perlu diperhatikan: `nl2br(htmlspecialchars(...))` pertama-tama membuat teks aman, lalu mengubah pergantian baris Anda menjadi `<br>` agar paragraf tampak benar. Tanda `??` pada `$art['category_name'] ?? 'General'` berarti "pakai nilai ini, atau `General` jika tidak ada".

**Langkah 7.3** Tambahkan tautan **Blog** ke menu atas.

**FILE: `views/layouts/navbar.php`  (UBAH FILE)**

Temukan item menu Home:

```php
                <li class="nav-item">
                    <a class="nav-link <?= ($requestUri === '' || $requestUri === '/') ? 'active' : '' ?>" href="<?= $base ?>">Home</a>
                </li>
```

Tepat **di bawahnya** tambahkan:

```php
                <li class="nav-item">
                    <a class="nav-link <?= stripos($requestUri, 'blog') === 0 ? 'active' : '' ?>" href="<?= $base ?>/blog">Blog</a>
                </li>
```

**Langkah 7.4** Tambahkan kedua route publik.

**FILE: `public/index.php`  (UBAH FILE)**

Di bawah blok artikel, tetap di atas `// Dispatch request`, tambahkan:

```php
// Blog (public pages)
$router->addRoute('blog', 'BlogController', 'index');
$router->addRoute('blog/([a-z0-9-]+)', 'BlogController', 'show');

```

Pola `([a-z0-9-]+)` berarti "huruf, digit, dan tanda hubung", yang persis menjadi bahan penyusun sebuah slug. Alamat `blog/my-first-post` memanggil `show('my-first-post')`.

**TITIK PERIKSA 7. Ujian terakhir.**

1. Buka jendela private/incognito (Anda tidak login) dan pergi ke `/blog`. Anda melihat kartu untuk setiap artikel, dengan gambar, lencana kategori, judul, dan awal teksnya.
2. Klik **Read more**. Anda melihat artikel lengkap. Alamat di peramban berakhiran slug, misalnya `/blog/my-first-post`.
3. Ketik `<b>hello</b>` di dalam isi sebuah artikel dan simpan. Pada halaman publik, teks yang tampil secara harfiah adalah `<b>hello</b>` dan tidak ada yang menjadi tebal. Itulah perlindungan `htmlspecialchars` yang bekerja.
4. Kunjungi `/blog/does-not-exist`. Anda mendapat halaman 404.
5. Di jendela private kunjungi `/dashboard/articles`. Anda dikirim ke halaman login.
6. Menu atas sekarang memiliki tautan **Blog**. Beranda, login, dan halaman profil asli Anda tetap berfungsi.

**Selamat. Anda telah membangun aplikasi CRUD yang lengkap.**

## Bagian 8: Apa yang terjadi ketika Anda mengklik "Publish Article"

Bacalah pelan-pelan sekali. Bagian ini mengikat semuanya.

1. Peramban mengirim formulir (POST) ke alamat `/dashboard/articles/store`, dengan teks Anda, gambar, dan kode CSRF rahasia.
2. `public/index.php` menelusuri daftar route dan menemukan `dashboard/articles/store`. Ia membuat `AdminArticleController` dan memanggil `store()`.
3. `store()` pertama-tama menjalankan `requireAdmin()` (apakah Anda admin?) dan `requireValidToken()` (apakah kode rahasianya benar?).
4. `readForm()` mengumpulkan dan membersihkan teks. `validateArticle()` memeriksanya (judul ada? kategori nyata? slug masih bebas?).
5. `handleUpload()` menyerahkan gambar ke `BlogHelper::saveImageAsWebp()`, yang memeriksa bahwa itu benar-benar gambar, mengonversinya, dan menyimpannya.
6. `ArticleModel::insert()` (diwarisi dari Model Onebuild) menulis baris baru ke tabel `articles`.
7. Controller menyimpan "Article published." di session dan mengarahkan ulang ke `/dashboard/articles`.
8. Halaman daftar menjalankan `index()`, meminta baris-baris dari `ArticleModel`, menyerahkannya ke `views/admin/articles/index.php`, dan menu atas menampilkan pesan hijau satu kali.

Route, controller, model, view. Setiap halaman yang akan Anda buat di Onebuild adalah variasi dari rantai ini.

## Ketika ada yang salah

Pertama: pastikan `APP_ENV=development` sudah diatur di `.env` (hanya di komputer lokal). Lalu cari gejala Anda.

**Halaman putih, atau "Something went wrong" / Error 500**

PHP menemukan masalah yang tidak bisa dipulihkan. Jalankan pendeteksi salah ketik (`php -l path/ke/file.php`) pada file yang terakhir Anda ubah. Jika hasilnya "No syntax errors", pesannya tersembunyi di log error PHP server Anda (di LMDE: `sudo tail -n 30 /var/log/nginx/onebuild.test.error.log`). Baca baris terakhirnya.

**"Parse error: syntax error, unexpected ..." dengan nomor baris**

Salah ketik. Lihat baris itu **dan baris di atasnya**. Tersangka yang biasa: `;` yang hilang di akhir baris, `)` atau `}` atau `]` yang hilang, tanda kutip penutup yang hilang, `->` yang diketik `-` atau `=>` yang diketik `=`.

**Halaman yang Anda buat menampilkan halaman 404**

Onebuild menampilkan 404 setiap kali ia tidak bisa mencocokkan route **atau** tidak bisa menemukan class atau method controller. Periksa, dengan urutan ini: (1) baris route di `public/index.php` dieja persis seperti alamatnya dan **tidak** diawali garis miring; (2) nama file controller sama dengan nama class, termasuk huruf kapital; (3) nama method di route sama dengan nama method yang sebenarnya.

**"Class ... not found" atau "View not found"**

File berada di folder yang salah atau namanya salah. `AdminCategoryController` harus ada di `app/Controllers/`, model di `app/Models/`, helper di `app/Helpers/`, view di bawah `views/`. Periksa juga baris `namespace` di bagian atas file.

**"Table ... doesn't exist" (pesan SQLSTATE)**

Anda menjalankan SQL Bagian 1 di database yang salah. Yang benar tertulis di baris `DB_NAME` pada `.env`.

**"CSRF token validation failed" (error 419)**

Formulir tidak memiliki baris `<?= \App\Core\CsrfProtection::getHiddenInput() ?>`, atau Anda membiarkan formulir terbuka lebih dari satu jam (muat ulang halaman dan coba lagi), atau gambarnya lebih besar dari yang diizinkan server sehingga seluruh formulir peramban terbuang. Coba gambar yang lebih kecil.

**"The image could not be uploaded (error code 1)" atau "(error code 2)"**

Gambar lebih besar dari batas server. Pilih gambar yang lebih kecil, atau naikkan `upload_max_filesize` dan `post_max_size` di `/etc/php/8.5/fpm/conf.d/99-dev.ini` (Bagian 9.9) lalu jalankan `sudo systemctl restart php8.5-fpm`.

**"Call to undefined function imagecreatefromwebp()" atau "imagewebp()"**

Ekstensi gambar PHP mati. Pasang dengan `sudo apt install php8.5-gd` lalu jalankan `sudo systemctl restart php8.5-fpm`.

**"Access denied. Admin privileges required."**

Anda sudah login, tetapi peran Anda bukan `root` atau `admin`. Buka tabel `users` di alat database Anda dan lihat kolom `role` untuk pengguna Anda.

**Popup "Are you sure?" tidak muncul ketika saya mengklik Delete**

Baris `<script>` di bagian bawah view daftar tidak memiliki `nonce="<?= $nonce ?>"` atau ada salah ketik di dalamnya. Peramban diam-diam menolak script tanpa nonce.

**Gambar tidak tampil**

Buka folder `public/uploads/images`: apakah file `.webp` ada di sana? Jika folder kosong di server live, folder itu kemungkinan tidak bisa ditulisi (beri izin 755).

**Saya sudah memperbaiki file tetapi tidak ada yang berubah**

Simpan file (Ctrl+S), lalu muat ulang halaman peramban dengan Ctrl+F5.

## Buat CRUD Anda sendiri: resep 10 langkah

Untuk mengelola hal lain (produk, klien, acara...), salin polanya. Contoh: **Products**.

1. Buat tabel di database (`products` dengan kolom yang Anda butuhkan).
2. Salin `CategoryModel.php` menjadi `ProductModel.php`. Ubah nama class, `$table`, dan SQL di dalamnya.
3. Salin `AdminCategoryController.php` menjadi `AdminProductController.php`. Ubah nama class, model, nama folder view, teks alamat (`/dashboard/products`) dan pesan-pesannya.
4. Sesuaikan `readForm()` dan `validateProduct()` dengan kolom Anda.
5. Salin keempat view dari `views/admin/categories/` ke `views/admin/products/` dan ubah kolom formulir serta kolom tabelnya.
6. Tambahkan enam route ke `public/index.php`.
7. Tambahkan tautan di `views/dashboard/_sidebar.php` dengan kata `$currentPage` tersendiri.
8. Uji: buat, ubah, hapus, formulir kosong, nilai duplikat, tanpa login.
9. Baca controller Anda sekali lagi dan tanyakan: apakah `requireAdmin()` adalah baris pertama setiap aksi? Apakah setiap formulir membawa kode CSRF? Apakah setiap nilai yang dicetak dibungkus `htmlspecialchars()`?
10. Sebelum go live, atur `APP_ENV=production` di `.env` yang live.

## Ide untuk berlatih

- **Draft atau terbit.** Tambahkan kolom `status` ke `articles` (`'draft'` atau `'published'`), tambahkan drop-down di formulir, dan tampilkan hanya artikel yang terbit di `/blog`. Petunjuk: Model dasar sudah memiliki `findAllPublished()`, yang mencari `status = 'published'`.
- **Angka dashboard.** Tampilkan "Total articles" dan "Total categories" di halaman ringkasan dashboard. Petunjuk: tambahkan method `countAll()` pada setiap model memakai `queryOne('SELECT COUNT(*) AS total FROM ...')`.
- **Artikel per kategori.** Tambahkan route `blog/category/([a-z0-9-]+)` yang menampilkan hanya artikel dari satu kategori.

Jika Anda buntu pada langkah mana pun, catat nama filenya dan salin pesan error persisnya. Itu semua yang dibutuhkan siapa pun untuk membantu Anda.

## Simpan blog Anda di Git

Setelah Git disiapkan (Bagian 14.1), simpan pekerjaan ini:

```bash
$ cd ~/www/onebuild
$ git status
$ git add .
$ git commit -m "Add blog: categories, articles, public pages"
```

Mendorongnya (push) ke repositori GitHub Anda sendiri dibahas di Bagian 14.4.

<div style="page-break-after: always;"></div>

# Bab 13: Membuat Proyek PHP Baru Anda Sendiri

## 13.1 Buat struktur folder

```bash
$ mkdir -p ~/www/myapp/public
$ cd ~/www/myapp
```

Hanya folder `public` yang disajikan ke peramban. Letakkan kode yang tidak ingin Anda ekspos (config, class, `.env`) **di samping** `public`, bukan di dalamnya.

Tata letak sederhana:

```
myapp/
  app/          <- your PHP classes
  config/       <- settings
  views/        <- templates
  public/       <- web root  (index.php lives here)
  .env          <- secrets (never commit)
  .gitignore
```

## 13.2 Halaman pertama

```bash
$ mousepad public/index.php
```

```php
<?php
echo "<h1>Hello from myapp.test</h1>";
echo "<p>PHP version: " . PHP_VERSION . "</p>";
```

## 13.3 Terbitkan

```bash
$ sudo newsite myapp ~/www/myapp/public
```

Buka **http://myapp.test**. Anda seharusnya melihat judul dan "PHP version: 8.5.x".

## 13.4 Uji PHP dengan database

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

Buka **http://myapp.test/db.php**. Hapus file ini setelah selesai menguji, karena berisi kata sandi.

## 13.5 Buka proyek di VS Code

```bash
$ code ~/www/myapp
```

**Titik periksa:** `myapp.test` berfungsi dan terhubung ke database.

<div style="page-break-after: always;"></div>

# BAGIAN IV: GIT, PRODUKSI, DAN DEPLOYMENT

# Bab 14: Git dan GitHub: Membuat Repo dan Push

> **Untuk perjalanan buku ini, Skenario B (Bagian 14.4) adalah yang terpenting:** Anda meng-clone Onebuild di Bab 11, Anda membangun blog di Bab 12, dan sekarang Anda mendorong (push) pekerjaan itu ke repositori GitHub **milik Anda sendiri**. Repositori itulah yang Anda deploy ke Dewaweb di Bab 16.

## 14.1 Beri tahu Git siapa Anda (sekali saja)

```bash
$ git config --global user.name "Your Name"
$ git config --global user.email "you@example.com"
$ git config --global init.defaultBranch main
```

Gunakan email yang sama dengan akun GitHub Anda (atau alamat `noreply` privat dari GitHub yang ada di pengaturan email GitHub Anda).

## 14.2 Buat kunci SSH (sekali saja)

GitHub tidak lagi menerima kata sandi akun untuk melakukan push. Kunci SSH adalah metode permanen yang paling sederhana.

```bash
$ ssh-keygen -t ed25519 -C "you@example.com"
```

Tekan Enter untuk menerima lokasi bawaan. Atur passphrase atau biarkan kosong. Lalu tampilkan kunci **publik**:

```bash
$ cat ~/.ssh/id_ed25519.pub
```

Salin seluruh barisnya (diawali `ssh-ed25519`).

Di github.com: klik foto profil Anda -> **Settings** -> **SSH and GPG keys** -> **New SSH key** -> beri judul (misalnya "LMDE desktop"), tempel kuncinya, **Add SSH key**.

Uji:

```bash
$ ssh -T git@github.com
```

Pada kali pertama, ketik `yes` untuk memercayai fingerprint GitHub. Anda akan melihat "Hi <username>! You've successfully authenticated".

## 14.3 Skenario A: push proyek baru Anda (`myapp`) ke repositori baru

**Langkah 1: buat repo kosong di GitHub.**
Di github.com klik **+** -> **New repository**. Beri nama `myapp`. Pilih Public atau Private. **JANGAN** centang "Add a README", ".gitignore" atau "license" (repo harus kosong). Klik **Create repository**.

**Langkah 2: siapkan proyek lokal.**

```bash
$ cd ~/www/myapp
$ mousepad .gitignore
```

Isi `.gitignore` setidaknya dengan ini:

```
.env
vendor/
node_modules/
storage/*.log
.vscode/
```

**Langkah 3: commit pertama dan push.**

```bash
$ git init
$ git add .
$ git status
$ git commit -m "Initial commit"
$ git branch -M main
$ git remote add origin git@github.com:<your-username>/myapp.git
$ git push -u origin main
```

Selalu baca `git status` sebelum commit dan pastikan `.env` **tidak** tercantum.

## 14.4 Skenario B: ambil `onebuild` hasil clone dan push ke repo baru MILIK ANDA SENDIRI

Anda tidak bisa melakukan push ke `onebuild-cpu/onebuild` (itu milik orang lain). Ada dua pilihan:

**Pilihan 1: salinan mandiri milik Anda.** Buat repo kosong baru di GitHub (misalnya `onebuild-mine`), lalu:

```bash
$ cd ~/www/onebuild
$ git remote rename origin upstream
$ git remote add origin git@github.com:<your-username>/onebuild-mine.git
$ git push -u origin main
```

`upstream` tetap menunjuk ke repo asli, sehingga Anda bisa menarik pembaruannya nanti dengan `git pull upstream main`.

**Pilihan 2: GitHub Fork.** Di halaman repo asli klik **Fork**, lalu clone fork Anda sebagai gantinya.

## 14.5 Alur kerja Git harian

```bash
$ git status                 # what changed?
$ git add .                  # stage everything
$ git commit -m "Describe the change"
$ git push                   # upload
$ git pull                   # download updates
$ git log --oneline -n 10    # recent history
```

Kebiasaan baik:

* Commit sedikit-sedikit dan sering, dengan pesan yang jelas.
* Jangan pernah meng-commit `.env`, kata sandi, atau kunci API. Jika sudah terlanjur, ganti rahasia itu segera.

**Titik periksa:** proyek Anda terlihat di profil GitHub Anda.

<div style="page-break-after: always;"></div>

# Bab 15: Menyiapkan Onebuild untuk Produksi

Kerjakan bab ini di mesin LMDE Anda **sebelum** Anda menyentuh Dewaweb.

## 15.1 Cari tahu apa yang ditawarkan Dewaweb

Login ke **cPanel** Dewaweb Anda (tautan dan kata sandinya ada di email selamat datang) dan cari di kotak pencarian di bagian atas:

| Cari | Mengapa |
|---|---|
| **MultiPHP Manager** | Versi PHP mana yang bisa Anda pilih? Catat yang tertinggi. |
| **Select PHP Version** atau **PHP Extensions** | Apakah `gd`, `pdo_mysql`, `mbstring`, `curl`, `zip`, `intl` aktif? |
| **Git Version Control** | Jika ada, Anda bisa deploy langsung dari GitHub. |
| **Terminal** atau **SSH Access** | Jika ada, Anda bisa menjalankan `git pull` dan `php bin/add_root.php` di server. |
| **MySQL Database Wizard** dan **phpMyAdmin** | Anda membutuhkannya di Bab 16. |

Catat temuan Anda. Temuan itu menentukan metode deployment mana yang Anda pakai di Bab 16.

## 15.2 Uji secara lokal dengan versi PHP yang sama dengan Dewaweb

Jika versi tertinggi di Dewaweb lebih rendah dari 8.5 (misalnya 8.4), uji secara lokal dengan versi itu.

```bash
$ sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-gd php8.4-bcmath
$ sudo systemctl start php8.4-fpm
$ sudo newsite onebuild ~/www/onebuild/public 8.4
```

Buka http://onebuild.test dan klik semua halaman, termasuk semua layar admin Bab 12, login, dan unggah gambar. Perbaiki setiap peringatan. (Argumen ketiga, `8.4`, adalah versi PHP untuk situs itu.) Kembali kapan saja:

```bash
$ sudo newsite onebuild ~/www/onebuild/public 8.5
```

## 15.3 `.env` produksi (siapkan sekarang)

Di server Anda akan membuat `.env` yang **berbeda**. Siapkan isinya dalam file teks di desktop Anda (bukan di dalam proyek):

```
APP_ENV=production
DB_NAME=<cpanel-user>_onebuild
DB_USER=<cpanel-user>_onebuild
DB_PASS=<a long random password>
```

ditambah setiap kunci lain yang muncul di `.env.example`, dengan nama kunci yang sama. Catatan:

* `APP_ENV` **harus** `production` secara online. Ini menyembunyikan detail error dari pengunjung.
* Di cPanel, nama database dan pengguna diberi awalan nama akun Anda. Anda memilih nama persisnya di Bab 16.
* Jika ada pengaturan URL situs, nilainya akan `https://domainanda.com` (atau subdomain staging).
* **Jangan pernah meng-commit `.env` ke Git.** Pastikan dengan `git status` bahwa `.env` tidak tercantum.

## 15.4 Dependensi

Jika proyek memiliki `composer.json`:

```bash
$ cd ~/www/onebuild
$ ls composer.json && composer install --no-dev --optimize-autoloader
```

Jika server tidak memiliki Composer, folder `vendor/` harus diunggah bersama kode (lihat 16.6).

## 15.5 Tentukan apa yang tidak perlu diunggah

Repositori berisi file khusus pengembang: `dump_file.py`, `project_dump.md`, dan folder `tutorial`. Semuanya tidak berbahaya di luar web root, tetapi Anda boleh tidak menyertakannya dalam ZIP deployment. Buat file bernama `.gitattributes` di root proyek:

```
dump_file.py   export-ignore
project_dump.md export-ignore
tutorial        export-ignore
.gitattributes  export-ignore
```

`git archive` (dipakai di 16.6, Metode 1) lalu melewatinya.

## 15.6 Ekspor database Anda untuk server

Struktur setiap tabel, ditambah data tabel `users` (agar akun admin Anda ikut terbawa):

```bash
$ mkdir -p ~/deploy
$ mariadb-dump -u dev -p --no-data onebuild > ~/deploy/schema.sql
$ mariadb-dump -u dev -p --no-create-info onebuild users > ~/deploy/users.sql
```

(`--no-data` hanya menulis struktur tabel; `--no-create-info` hanya menulis baris data.) Nanti, di Bab 16 Anda mengimpor `schema.sql` lebih dulu, lalu `users.sql`. Jika Anda lebih suka memulai situs live dengan artikel uji Anda, ekspor seluruh database dengan `mariadb-dump -u dev -p onebuild > ~/deploy/full.sql`.

> Kata sandi Anda hanya tersimpan sebagai hash di `users.sql`, tetapi tetap perlakukan file itu sebagai privat dan hapus dari server setelah diimpor.

## 15.7 Pengujian keamanan, secara lokal

Dengan stack berjalan, jalankan ini. Setiap hasil harus sesuai.

```bash
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/.env
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/.git/config
```

Keduanya harus menampilkan **404** atau **403**, jangan pernah `200`. Lalu buktikan bahwa file PHP di folder uploads tidak bisa dijalankan:

```bash
$ echo '<?php echo "DANGER";' > ~/www/onebuild/public/uploads/images/t.php
$ curl -s -o /dev/null -w "%{http_code}\n" http://onebuild.test/uploads/images/t.php
$ rm ~/www/onebuild/public/uploads/images/t.php
```

Harus menampilkan **403** (atau 404). Jika menampilkan `200` dan Anda melihat `DANGER`, berhenti: perlindungan uploads tidak ada (periksa lagi templat `newsite` di Bab 10).

## 15.8 Commit dan push

Commit pekerjaan Anda yang sudah selesai dan push ke repositori GitHub Anda sendiri (Bab 14, Skenario B). Selalu jalankan `git status` lebih dulu dan pastikan `.env` tidak ada dalam daftar.

## 15.9 Buat titik cadangan

```bash
$ sudo timeshift --create --comments "ready to deploy" --tags D
```

**Titik periksa:** situs berfungsi secara lokal dengan versi PHP yang sama dengan Dewaweb, pengujian keamanan lulus, isi `.env` produksi dan file SQL sudah siap, dan kode Anda ada di GitHub.

<div style="page-break-after: always;"></div>

# Bab 16: Deploy ke Shared Hosting Dewaweb

Cloud hosting Dewaweb menjalankan **cPanel** di atas **LiteSpeed Enterprise** dengan versi PHP yang bisa dipilih. LiteSpeed membaca `.htaccess`, sehingga aturan gaya Apache langsung bekerja di sana.

> Nama menu di bawah adalah nama standar cPanel (dalam bahasa Inggris). Jika layar Anda tampak berbeda atau berbahasa Indonesia, ketik nama itu di **kotak pencarian di bagian atas cPanel**. Apa pun yang ditandai **(jika tersedia)** bergantung pada paket Anda; tanyakan ke dukungan Dewaweb jika Anda tidak menemukannya.

## 16.1 Rencana dalam satu gambar

```
Your LMDE PC            GitHub                Dewaweb (cPanel + LiteSpeed)
------------            ------                ----------------------------
code + test   --push--> your repo --deploy--> ~/onebuild/            (project)
http://x.test                                 ~/onebuild/public       (web root)
                                              https://yourdomain.com
```

**Aturan emas:** hanya folder `public` yang boleh bisa dijangkau dari internet. Semua yang lain (`app`, `config`, `database`, `.env`, `.git`) tetap berada satu tingkat di atasnya.

## 16.2 Domain, DNS, dan subdomain staging

1. Arahkan domain Anda ke Dewaweb seperti yang tertulis di email selamat datang (biasanya dengan mengubah **nameserver** domain). Perubahan DNS bisa memakan waktu dari beberapa menit sampai berjam-jam.
2. Selagi menunggu, Anda bisa menguji lewat **subdomain** seperti `staging.domainanda.com`. Buat di 16.3.
3. Lakukan deployment penuh di subdomain staging lebih dulu. Ketika semuanya lulus, ulangi langkahnya untuk domain sungguhan (atau pindahkan).

## 16.3 Buat web root yang mengarah ke `public`

**Tata letak A (disarankan):** proyek berada di luar web root, dan **document root** (sub)domain adalah folder `public` milik proyek.

Di cPanel buka **Domains** (versi lama: **Subdomains**), lalu:

1. Klik **Create A New Domain** (atau Create Subdomain).
2. Domain: `staging.domainanda.com`.
3. **Hilangkan centang** "Share document root" (jika tampil).
4. Document Root: `onebuild_staging/public`. (Ini relatif terhadap folder home Anda.)
5. Kirim. cPanel membuat folder `onebuild_staging/public`.

Untuk **domain utama** Anda punya dua pilihan:

* Di **Domains -> Manage**, ubah document root domain menjadi `onebuild/public` (jika cPanel Anda mengizinkan pengeditannya), atau
* dengan SSH atau Terminal **(jika tersedia)**: letakkan proyek di `~/onebuild` dan ganti folder bawaan dengan tautan:
  ```bash
  $ mv ~/public_html ~/public_html.bak
  $ ln -s ~/onebuild/public ~/public_html
  ```

**Tata letak B (hanya jika keduanya tidak bisa):** unggah seluruh proyek ke `public_html` dan andalkan `.htaccess` di root proyek. Pakai Tata letak B hanya setelah semua pengujian keamanan di 16.12 lulus, karena di sini `.env`, `app/`, dan `config/` berada di dalam web root.

## 16.4 Pilih versi PHP

1. cPanel -> **MultiPHP Manager**.
2. Centang domain atau subdomain Anda, pilih versi PHP yang Anda uji secara lokal di 15.2, klik **Apply**.
3. cPanel -> **MultiPHP INI Editor** -> pilih domainnya, lalu atur:
   * `upload_max_filesize` = `8M`
   * `post_max_size` = `8M`
   * `memory_limit` = `256M` (atau sesuai yang diizinkan paket)
4. Pastikan ekstensi aktif (cPanel -> **Select PHP Version** atau **PHP Extensions**, jika tersedia): `gd`, `pdo_mysql` (atau `mysqli`), `mbstring`, `curl`, `zip`, `intl`, `xml`.

## 16.5 Buat database

1. cPanel -> **MySQL Database Wizard**.
2. Langkah 1: nama database `onebuild`. cPanel menambahkan awalan akun Anda, sehingga nama sebenarnya seperti `youruser_onebuild`.
3. Langkah 2: buat pengguna, misalnya `onebuild`, dengan **kata sandi panjang hasil generate**. Simpan.
4. Langkah 3: centang **ALL PRIVILEGES**.
5. Catat ketiga nilai sebenarnya (database, pengguna, kata sandi). Nilai-nilai itu masuk ke `.env` di server.

**Impor tabel:** cPanel -> **phpMyAdmin** -> klik database Anda di daftar kiri -> **Import** -> pilih `~/deploy/schema.sql` -> **Go**. Ulangi dengan `~/deploy/users.sql`. (Jika file besar, kompres dulu sebagai `.zip` atau `.gz`; phpMyAdmin menerimanya.)

## 16.6 Unggah kode

Pilih **satu** metode. Semuanya berakhir dengan folder proyek di direktori home Anda, misalnya `/home/youruser/onebuild_staging`.

### Metode 1: unggah ZIP (selalu berhasil)

Di PC LMDE Anda, di folder proyek:

```bash
$ cd ~/www/onebuild
$ git status                     # must be clean, .env not listed
$ git archive --format=zip --output=$HOME/deploy/onebuild.zip HEAD
```

`git archive` hanya mengemas apa yang sudah di-commit (tanpa `.git`, tanpa `.env`) dan menghormati `.gitattributes` dari 15.5.

Jika proyek memakai Composer dan server tidak memiliki Composer, tambahkan folder `vendor`: `zip -r ~/deploy/onebuild.zip vendor`.

Di cPanel:

1. **File Manager** -> buka folder home Anda (bukan `public_html`).
2. Buat (atau buka) folder `onebuild_staging`. Unggah `onebuild.zip` ke dalamnya.
3. Klik kanan zip -> **Extract**. Lalu hapus zip-nya.
4. Pastikan `onebuild_staging/public/index.php` ada.

### Metode 2: cPanel "Git Version Control" **(jika tersedia)**

1. cPanel -> **Git Version Control** -> **Create**.
2. Aktifkan **Clone a Repository**.
3. Clone URL: `https://github.com/<username-anda>/<repo-anda>.git`. (Repositori privat memerlukan deploy key SSH. Paling mudah adalah membiarkan repo publik selagi Anda belajar, selama tidak berisi **rahasia apa pun**.)
4. Repository Path: `/home/youruser/onebuild_staging` (folder itu tidak boleh sudah berisi file).
5. Klik **Create**. Untuk memperbarui nanti, buka **Manage** dan gunakan **Update from Remote**.

Metode 2 memberi Anda seluruh repositori termasuk `.git` di folder di luar web root. Itu aman pada Tata letak A.

### Metode 3: SSH **(jika tersedia)**

```bash
$ ssh <user>@<server> -p <port>        # values from Dewaweb
$ git clone https://github.com/<your-username>/<your-repo>.git onebuild_staging
```

## 16.7 Buat `.env` di server

1. Di **File Manager**, buka **Settings** (kanan atas) dan centang **Show Hidden Files (dotfiles)**.
2. Masuk ke `onebuild_staging`, klik **+ File**, beri nama `.env`.
3. Klik kanan -> **Edit**, tempel isi yang Anda siapkan di 15.3 (dengan nama database, pengguna, dan kata sandi sebenarnya dari 16.5), dan pastikan tertulis `APP_ENV=production`.
4. Simpan. Atur izinnya menjadi **600** (klik kanan -> Change Permissions).

## 16.8 Izin folder

Di cPanel dengan LiteSpeed, PHP berjalan sebagai **akun Anda**, jadi izin normal sudah cukup. **Jangan pernah memakai 777.**

| Apa | Izin |
|---|---|
| Folder | `755` |
| File | `644` |
| `.env` | `600` |
| `storage/` dan `public/uploads/` | `755` (bisa ditulisi oleh akun Anda) |

Pastikan `public/uploads/images` ada. Jika belum, buat di File Manager.

## 16.9 Aktifkan HTTPS

1. cPanel -> **SSL/TLS Status** -> pilih domainnya -> **Run AutoSSL**. Tunggu beberapa menit.
2. Di **Domains**, aktifkan **Force HTTPS Redirect** untuk domain itu.
3. Jika Onebuild memiliki pengaturan URL situs di `.env`, ubah menjadi `https://...`.

## 16.10 Buat akun administrator

* Jika Anda mengimpor `users.sql` di 16.5, admin lokal Anda sudah ada. Login dengan login dan kata sandi yang sama seperti yang Anda pakai secara lokal.
* Jika tidak, dengan SSH atau Terminal **(jika tersedia)**:
  ```bash
  $ cd ~/onebuild_staging
  $ php bin/add_root.php "+628123456789" "YourStrongPassword"
  ```

Setelah login pertama, **ganti kata sandi** dengan yang baru.

## 16.11 Apakah situsnya berfungsi?

Buka `https://staging.domainanda.com` dan jalankan daftar periksa ini:

- [ ] Halaman beranda terbuka, dengan gaya Bootstrap.
- [ ] `/login` berfungsi dan Anda sampai ke `/dashboard`.
- [ ] `/blog` menampilkan artikel Anda (atau "No articles yet").
- [ ] Buat kategori dan artikel dengan gambar. Gambarnya muncul.
- [ ] Ubah dan hapus berfungsi.
- [ ] Alamat yang sengaja salah menampilkan halaman 404, bukan error PHP.

## 16.12 Pengujian keamanan (wajib)

Jalankan ini dari terminal LMDE Anda terhadap alamat **live**:

```bash
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/.env
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/.git/config
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/config/
$ curl -s -o /dev/null -w "%{http_code}\n" https://staging.yourdomain.com/app/
```

Setiap baris harus menampilkan **403** atau **404**. Jika ada yang menampilkan `200`, matikan situs (ganti nama document root) dan perbaiki sebelum hal lain. Pada Tata letak A jalur-jalur ini bahkan tidak berada di dalam web root, sehingga tidak mungkin disajikan.

Periksa juga:

- [ ] Buka halaman yang menyebabkan error (misalnya tanpa mengubah apa pun, cukup kunjungi URL yang salah). Anda **tidak boleh** melihat path file, SQL, atau stack trace. Jika terlihat, `APP_ENV` bukan `production`.
- [ ] Lihat bilah alamat: gembok menunjukkan HTTPS.

## 16.13 Go live

Ketika staging sudah sempurna:

1. Arahkan domain sungguhan ke proyek (16.3, opsi domain utama).
2. Buat `.env` produksi untuknya (pengaturan URL menjadi domain sungguhan Anda).
3. Ulangi 16.4, 16.9, dan 16.12 untuk domain sungguhan.

## 16.14 Cara memperbarui situs live nanti

1. Ubah dan uji di LMDE.
2. `git add . && git commit -m "pesan" && git push`.
3. Di server:
   * Metode 2: cPanel -> Git Version Control -> Manage -> **Update from Remote**.
   * Metode 3: `ssh` masuk, `cd onebuild_staging && git pull`.
   * Metode 1: buat ZIP baru dan unggah menimpa folder (**jangan** hapus `.env` atau `public/uploads`).
4. Jika Anda mengubah database, terapkan perubahan SQL di **phpMyAdmin**. Simpan daftar setiap `ALTER TABLE` atau `CREATE TABLE` yang Anda jalankan secara lokal.
5. Uji lagi daftar periksa di 16.11.

**Jangan pernah** mengedit file langsung di server kecuali dalam keadaan darurat. Perbaiki secara lokal, push, deploy.

## 16.15 Cadangan dan rollback

* Sebelum setiap pembaruan, ekspor database: phpMyAdmin -> database Anda -> **Export**.
* Dewaweb menyediakan cadangannya sendiri, tetapi simpan juga salinan Anda sendiri: unduh ekspor database setiap minggu.
* Rollback kode: dengan Git, `git checkout <commit-lama>` di server (atau deploy ZIP yang lebih lama).
* Folder `public/uploads/images` berisi gambar yang **tidak** ada di Git. Unduh salinannya sebelum perubahan yang berisiko.

## 16.16 Masalah di server

| Gejala | Kemungkinan penyebab dan perbaikan |
|---|---|
| 500 Internal Server Error | Buka file `error_log` di folder situs (File Manager) atau cPanel -> **Errors**. Sering karena `.env` salah atau ekstensi PHP kurang |
| Halaman putih | `APP_ENV=production` menyembunyikan error. Periksa `error_log` |
| Setiap halaman selain beranda memberi 404 | Document root bukan folder `public`, atau `.htaccess` tidak ada di `public` |
| "Database connection failed" | Nama/pengguna/kata sandi salah. Ingat awalan `youruser_`. Periksa lagi bahwa pengguna sudah ditambahkan ke database dengan ALL PRIVILEGES |
| Gambar tidak terunggah | Folder tidak ada atau tidak bisa ditulisi (`public/uploads/images`, 755). Periksa juga `upload_max_filesize` (16.4) |
| "Class not found" | File tidak terunggah atau nama file memiliki huruf kapital yang berbeda. Linux peka huruf besar-kecil |
| Gaya tidak tampil | Pengaturan base URL salah, atau peramban menyimpan cache situs lama (Ctrl+F5) |
| Berfungsi lokal, tidak online | Bandingkan versi PHP (16.4 vs 15.2), ekstensi, dan aturan `.htaccess` (Bab 8) |

**Titik periksa:** situs Onebuild Anda berjalan lewat HTTPS di Dewaweb dan lulus setiap pengujian keamanan.

<div style="page-break-after: always;"></div>

# Bab 17: Rutinitas Harian

## 17.1 Jangan biarkan stack web menyala sendiri

Nginx, PHP-FPM, dan MariaDB memakai RAM. Nyalakan hanya saat mengembangkan:

```bash
$ sudo systemctl disable nginx php8.5-fpm mariadb
```

## 17.2 Pintasan

```bash
$ mousepad ~/.bashrc
```

Tambahkan di bagian bawah:

```bash
alias devon='sudo systemctl start nginx php8.5-fpm mariadb'
alias devoff='sudo systemctl stop nginx php8.5-fpm mariadb'
alias devstatus='systemctl is-active nginx php8.5-fpm mariadb'
```

Muat:

```bash
$ source ~/.bashrc
```

## 17.3 Satu hari yang khas

```bash
$ devon                       # start the stack
$ code ~/www/myapp            # open the project
# ... work, refresh http://myapp.test ...
$ git add . && git commit -m "Work" && git push
$ devoff                      # free the RAM
```

## 17.4 Pemeliharaan mingguan

```bash
$ sudo apt update && sudo apt upgrade -y
$ sudo apt autoremove --purge -y
$ sudo apt clean
```

(Tinjau apa yang direncanakan `autoremove` jika Anda ragu: `sudo apt-get -s autoremove --purge`.)

## 17.5 Hari deploy

Ketika sebuah perubahan siap untuk situs live:

```bash
$ cd ~/www/onebuild
$ git status                      # .env must not be listed
$ git add . && git commit -m "Describe the change"
$ git push
```

Lalu perbarui server seperti di Bagian 16.14, jalankan daftar periksa di Bagian 16.11, dan pengujian keamanan di Bagian 16.12.

<div style="page-break-after: always;"></div>

# Bab 18: Pemecahan Masalah dan Pemulihan

## 18.1 Layar hitam atau tidak ada desktop setelah reboot

1. Tekan **Ctrl+Alt+F3** untuk konsol teks lalu login.
2. Pasang ulang bagian-bagian desktop:

```bash
$ sudo apt update
$ sudo apt install --reinstall cinnamon cinnamon-session nemo \
    lightdm slick-greeter network-manager network-manager-gnome
$ sudo systemctl restart lightdm
```

3. Jika masih rusak, pulihkan snapshot dari konsol:

```bash
$ sudo timeshift --list
$ sudo timeshift --restore
```

## 18.2 Tidak ada internet setelah debloat

```bash
$ sudo apt install --reinstall network-manager network-manager-gnome
$ sudo systemctl enable --now NetworkManager
```

## 18.3 `apt update` mengeluh soal repositori Sury

* "NO_PUBKEY" atau "not signed": ulangi kedua perintah kunci di Bagian 9.2.
* Pastikan `/etc/apt/sources.list.d/php-sury.list` berisi `trixie` dan bukan `gigi`.

## 18.4 `php -v` menampilkan versi yang salah

```bash
$ sudo update-alternatives --set php /usr/bin/php8.5
$ ls /run/php/
```

Anda seharusnya melihat `php8.5-fpm.sock`. Jika nama socket-nya berbeda, edit `/etc/nginx/sites-available/<nama>.test` dan perbaiki baris `fastcgi_pass`, lalu `sudo systemctl reload nginx`.

## 18.5 ZRAM tidak aktif

```bash
$ systemctl status systemd-zram-setup@zram0.service
$ sudo cat /etc/systemd/zram-generator.conf
```

Periksa bahwa file ditulis persis seperti di Bagian 7.3, lalu reboot.

## 18.6 Tetap kehabisan memori

* Pakai XFCE sebagai ganti Cinnamon.
* Jalankan `devoff` saat tidak mengembangkan.
* Pakai satu peramban dengan sedikit tab; tutup jendela VS Code.
* Naikkan ZRAM menjadi `ram * 0.75`.
* Pertimbangkan menambah RAM: modul DDR3 murah, dan 8 GB mengubah mesin ini sepenuhnya.

## 18.7 Unggahan gagal dengan "413 Request Entity Too Large"

Nginx menolak file itu sebelum PHP melihatnya. Jalankan ulang `newsite` (templatnya mengatur `client_max_body_size 8M`) dan periksa bahwa `/etc/php/8.5/fpm/conf.d/99-dev.ini` ada (Bagian 9.9).

## 18.8 Berfungsi secara lokal tetapi tidak di Dewaweb

Telusuri Bagian 8.3 (lima hal yang harus sama), lalu Bagian 16.16.

## 18.9 Pemeriksaan kesehatan cepat seluruh stack

```bash
$ systemctl is-active nginx php8.5-fpm mariadb
$ sudo nginx -t
$ php -v
$ zramctl
$ free -h
```

<div style="page-break-after: always;"></div>

# Lampiran A: Contekan Perintah

## Sistem

| Tugas | Perintah |
|---|---|
| Perbarui semuanya | `sudo apt update && sudo apt upgrade -y` |
| Instal | `sudo apt install <nama>` |
| Hapus sepenuhnya | `sudo apt purge <nama>` |
| Bersihkan paket yatim | `sudo apt autoremove --purge` |
| Memori | `free -h` |
| Status ZRAM | `zramctl` dan `swapon --show` |
| Snapshot | `sudo timeshift --create --comments "teks"` |

## Stack web

| Tugas | Perintah |
|---|---|
| Nyalakan stack | `devon` |
| Matikan stack | `devoff` |
| Situs lokal baru | `sudo newsite <nama> <path>/public` |
| Uji konfigurasi Nginx | `sudo nginx -t` |
| Muat ulang Nginx | `sudo systemctl reload nginx` |
| Error Nginx | `sudo tail -n 30 /var/log/nginx/error.log` |
| Shell MariaDB | `sudo mariadb` |
| Versi PHP | `php -v` |

## Git

| Tugas | Perintah |
|---|---|
| Clone | `git clone <url>` |
| Status | `git status` |
| Stage semuanya | `git add .` |
| Commit | `git commit -m "pesan"` |
| Push | `git push` |
| Pull | `git pull` |
| Push pertama repo baru | `git remote add origin <url-ssh>` lalu `git push -u origin main` |

## Deployment

| Tugas | Perintah atau tempat |
|---|---|
| Kemas kode yang sudah di-commit | `git archive --format=zip --output=$HOME/deploy/onebuild.zip HEAD` |
| Hanya struktur database | `mariadb-dump -u dev -p --no-data onebuild > schema.sql` |
| Hanya baris tabel users | `mariadb-dump -u dev -p --no-create-info onebuild users > users.sql` |
| Uji jalur yang dilindungi | `curl -s -o /dev/null -w "%{http_code}\n" <url>/.env` |
| Ganti versi PHP sebuah situs | `sudo newsite <nama> <path> 8.4` |
| Versi PHP Dewaweb | cPanel -> MultiPHP Manager |
| Database Dewaweb | cPanel -> MySQL Database Wizard, lalu phpMyAdmin |
| HTTPS Dewaweb | cPanel -> SSL/TLS Status -> Run AutoSSL |
| Perbarui situs live | `git push`, lalu cPanel -> Git Version Control -> Update from Remote |

## Jalur penting

| Item | Path |
|---|---|
| Proyek Anda | `~/www` |
| Situs Nginx | `/etc/nginx/sites-available/` |
| Pool PHP-FPM | `/etc/php/8.5/fpm/pool.d/www.conf` |
| php.ini (web) | `/etc/php/8.5/fpm/php.ini` |
| Penyetelan MariaDB | `/etc/mysql/mariadb.conf.d/99-lowmem.cnf` |
| Konfigurasi ZRAM | `/etc/systemd/zram-generator.conf` |
| File hosts | `/etc/hosts` |

<div style="page-break-after: always;"></div>

# Lampiran B: Cara Mencetak Buku Ini

**Metode 1: HTML lewat Chromium (paling ringan, disarankan)**

```bash
$ sudo apt install -y pandoc
$ pandoc Panduan_Lengkap_LMDE7_Onebuild_ID.md -s --toc -o handbook.html
```

Buka `handbook.html` di Chromium lalu tekan **Ctrl+P**. Penanda pemisah halaman di antara bab dihormati. Pilih "Save as PDF" atau printer Anda. Matikan "Headers and footers" untuk hasil yang bersih. Untuk buku berjilid, cetak bolak-balik.

**Metode 2: VS Code**

Pasang ekstensi *Markdown PDF* (yzane), buka file `.md`, tekan **Ctrl+Shift+P**, lalu jalankan **Markdown PDF: Export (pdf)**.

**Tips:** cetak Contekan Perintah (Lampiran A) di halaman tersendiri dan simpan di samping keyboard Anda.

---

*Akhir buku.*
