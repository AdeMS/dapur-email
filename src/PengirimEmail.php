<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

/**
 * Cara mengirim email. Modul lain cukup bergantung pada antarmuka ini, sehingga cara kirimnya (SMTP, file untuk uji,
 * atau dimatikan) diatur dari pengaturan tanpa mengubah kode modul.
 */
interface PengirimEmail
{
    /**
     * Apakah email benar-benar bisa dikirim. Bila tidak, modul bisa memakai cara lain, misalnya menampilkan kata
     * sandi sementara alih-alih mengirim undangan.
     */
    public function aktif(): bool;

    /** @throws GagalKirim */
    public function kirim(Pesan $pesan): void;
}
