<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/** Mengubah Pesan menjadi email symfony/mime, lengkap dengan pengirim dari pengaturan. */
final class SuratSymfony
{
    public static function dari(Pesan $pesan, Pengaturan $pengaturan): Email
    {
        $email = (new Email())
            ->from(new Address($pengaturan->alamatPengirim, $pengaturan->namaPengirim))
            ->to(new Address($pesan->kepada, $pesan->namaKepada))
            ->subject($pesan->subjek)
            ->text($pesan->teks)
            ->html($pesan->html);

        if ($pesan->balasKe !== null) {
            $email->replyTo($pesan->balasKe);
        }

        return $email;
    }
}
