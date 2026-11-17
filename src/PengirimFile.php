<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

/**
 * Tidak mengirim apa pun: setiap email ditulis sebagai berkas .eml ke folder pengaturan (bawaan data/email).
 * Berkas .eml bisa dibuka di aplikasi email seperti Thunderbird atau Outlook untuk melihat tampilannya. Cocok untuk
 * mencoba di komputer sendiri.
 *
 * Folder dibuat dengan izin 2770 dan berkas 660, karena isinya bisa berisi tautan rahasia (misalnya undangan).
 */
final class PengirimFile implements PengirimEmail
{
    public function __construct(private readonly Pengaturan $pengaturan)
    {
    }

    public function aktif(): bool
    {
        return $this->pengaturan->kekurangan() === null;
    }

    public function kirim(Pesan $pesan): void
    {
        $kurang = $this->pengaturan->kekurangan();
        if ($kurang !== null) {
            throw new GagalKirim($kurang);
        }

        $folder = $this->pengaturan->folder;
        if (! is_dir($folder)) {
            if (! @mkdir($folder, 0770, true) && ! is_dir($folder)) {
                throw new GagalKirim("Folder email '{$folder}' tidak bisa dibuat.");
            }

            @chmod($folder, 02770);
        }

        $file = sprintf('%s/%s-%s.eml', $folder, gmdate('Ymd-His'), bin2hex(random_bytes(4)));
        if (@file_put_contents($file, SuratSymfony::dari($pesan, $this->pengaturan)->toString()) === false) {
            throw new GagalKirim("Email tidak bisa ditulis ke '{$file}'.");
        }

        @chmod($file, 0660);
    }
}
