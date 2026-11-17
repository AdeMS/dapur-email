<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

/** Pengiriman email dimatikan ('cara' => 'mati'): tidak ada email yang dikirim. */
final class PengirimMati implements PengirimEmail
{
    public function aktif(): bool
    {
        return false;
    }

    public function kirim(Pesan $pesan): void
    {
        throw new GagalKirim("Pengiriman email belum diaktifkan. Atur 'email' => ['cara' => 'smtp', ...] di config/autoload.");
    }
}
