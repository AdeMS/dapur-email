# Dapur AdeMS Email

Modul email untuk [Dapur AdeMS](https://github.com/AdeMS/dapur): mengirim email lewat server SMTP, dengan isi
email yang disusun dari template seperti halaman biasa.

Paket ini tidak membuat halaman sendiri. Modul lain, misalnya [adems/dapur-user](https://github.com/AdeMS/dapur-user)
untuk undangan dan lupa kata sandi, memakai layanan dari paket ini untuk mengirim email.

Pengiriman memakai [symfony/mailer](https://symfony.com/doc/current/mailer.html), pustaka pengirim email yang
banyak dipakai di dunia PHP. Laminas juga punya laminas-mail, tetapi paket itu kini hanya menerima perbaikan
keamanan, jadi untuk proyek baru dipilih symfony/mailer. Komponen Laminas dan Symfony bisa dipakai bersama tanpa
masalah, karena keduanya mengikuti standar PHP yang sama.

## Kebutuhan

- Dapur AdeMS 2.4 atau lebih baru
- Akun email untuk mengirim, misalnya `noreply@contoh.com` di hosting Anda, beserta alamat server SMTP-nya

## Pemasangan

Paket ini biasanya ikut terpasang sebagai kebutuhan modul lain. Untuk memasangnya sendiri, tulis di
`composer.local.json`, lalu jalankan `composer update`:

```json
{
    "require": {
        "adems/dapur-email": "^1.0"
    }
}
```

Bila tidak ada modul lain yang memuatnya, cantumkan `AdeMS\Dapur\Email\ConfigProvider` di `config/modul.local.php`.

## Pengaturan

Buat `config/autoload/email.local.php` di folder inti. Akhiran `.local.php` membuat file ini tidak masuk Git,
dan memang harus begitu, karena isinya memuat kata sandi email:

```php
<?php

return [
    'email' => [
        // smtp: kirim sungguhan | file: tulis ke folder (untuk uji) | mati: tidak mengirim apa pun
        'cara'     => 'smtp',
        'smtp'     => [
            'host'     => 'mail.contoh.com',
            'port'     => 587,
            'user'     => 'noreply@contoh.com',
            'sandi'    => 'kata-sandi-akun-email',
            'enkripsi' => 'tls',   // tls (port 587) | ssl (port 465) | tidak
        ],
        'pengirim' => [
            'alamat' => 'noreply@contoh.com',
            'nama'   => 'Dapur AdeMS',
        ],
    ],
];
```

Batasi izin file ini seperti `database.local.php`: hanya pemilik dan grup web server yang boleh membacanya,
misalnya `chmod 640` dengan grup web server. Ingat juga bahwa cache konfigurasi (`data/cache/`) ikut memuat kata
sandi ini, jadi izin folder cache harus tetap tertutup.

Tanpa pengaturan, `cara` bernilai `mati`: tidak ada email yang dikirim, dan modul yang memakai email memakai cara
lain bila ada (misalnya kata sandi sementara untuk user baru).

### Memilih server SMTP

Kirim lewat server email yang memang berhak mengirim untuk domain Anda, biasanya server email hosting tempat kotak
surat itu dibuat. Banyak domain memasang aturan SPF, DKIM, dan DMARC: email yang dikirim dari server lain (misalnya
langsung dari server web atau NAS di rumah) akan ditolak atau masuk spam. Alamat `host` dan `port` biasanya tertera
di panel hosting, pada bagian pengaturan aplikasi email.

### Cara `file` untuk uji

Dengan `'cara' => 'file'`, email tidak dikirim, tetapi ditulis sebagai berkas `.eml` ke `data/email/` (bisa diubah
dengan `'folder' => '...'`). Berkas `.eml` bisa dibuka di Thunderbird atau Outlook untuk melihat tampilannya. Cara
ini cocok di komputer sendiri; di server, folder itu harus bisa ditulis web server.

### Per website

Dengan [adems/dapur-multiwebsite](https://github.com/AdeMS/dapur-multiwebsite), setiap website bisa memakai
pengirim atau server SMTP sendiri lewat `config/autoload/email.local.php` miliknya.

## Perintah

```bash
vendor/bin/dapur-email info            # menampilkan pengaturan email, tanpa kata sandi
vendor/bin/dapur-email uji <alamat>    # mengirim email uji ke alamat itu
```

Setelah mengubah pengaturan, hapus cache konfigurasi lebih dulu (`php dapur clear-config-cache`), lalu jalankan
`uji` untuk memastikan email benar-benar sampai. Dengan paket multi-website, tambahkan `--website=<domain>`.

## Memakai email di modul lain

Ambil dua layanan dari container:

- `AdeMS\Dapur\Email\SuratTemplat` menyusun email dari template.
- `AdeMS\Dapur\Email\PengirimEmail` mengirimnya sesuai pengaturan.

```php
$pesan = $container->get(SuratTemplat::class)->buat(
    'toko::pesanan-baru',                      // template isi HTML
    ['nomor' => 'P-0012'],                     // data untuk template
    'budi@contoh.com',                         // penerima
    'Pesanan baru P-0012',                     // subjek
    'Budi',                                    // nama penerima (opsional)
);

$pengirim = $container->get(PengirimEmail::class);
if ($pengirim->aktif()) {
    $pengirim->kirim($pesan);                  // melempar GagalKirim bila gagal
}
```

Setiap email butuh dua template:

- `toko::pesanan-baru` untuk isi HTML. Template ini otomatis dibungkus layout `email::layout`, berisi kepala
  "Dapur AdeMS" dan catatan kaki.
- `toko::pesanan-baru-teks` untuk isi teks biasa, tanpa layout.

Email dengan dua versi isi lebih nyaman dibaca di semua aplikasi email dan lebih jarang dianggap spam. Di template
HTML, tulis gaya langsung di elemen (`style="..."`), karena banyak aplikasi email mengabaikan CSS terpisah.

Layout dan template paket ini bisa ditimpa seperti template lain:

```php
'templates' => ['paths' => ['email' => [__DIR__ . '/../../templates/email']]],
```

## Kenapa modul lain tidak memakai symfony/mailer langsung

Modul lain hanya mengenal kelas milik paket ini: `PengirimEmail`, `Pesan`, `SuratTemplat`, dan `GagalKirim`, ditambah
pengaturan `'email'`. Hanya tiga berkas di paket ini yang menyentuh symfony/mailer: `PengirimSmtp`,
`PengirimFile`, dan `SuratSymfony`.

Pola ini disebut *adapter* atau *dependency inversion*: kode bergantung pada antarmuka buatan sendiri, bukan pada
pustaka pihak ketiga. Container Laminas membantu di sini, karena modul cukup meminta `PengirimEmail::class`, lalu
`PabrikLayanan` yang menentukan isinya sesuai pengaturan. Akibatnya:

- bila suatu saat ada pustaka yang lebih baik (atau layanan seperti Amazon SES), cukup tiga berkas itu yang ditulis
  ulang; modul lain, template, dan pengaturan di server tidak berubah;
- cara kirim baru, misalnya `'cara' => 'ses'`, bisa ditambahkan tanpa menyentuh modul lain;
- di uji, `PengirimEmail` bisa diganti tiruan yang hanya menyimpan email.

Karena itu, modul yang memakai paket ini sebaiknya **tidak** membuat objek symfony/mailer (misalnya
`Symfony\Component\Mime\Email`) sendiri.

## Uji

```bash
composer install
composer test
```

## Lisensi

BSD-3-Clause. Lihat [LICENSE.md](LICENSE.md).
