<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use InvalidArgumentException;

/**
 * Satu email yang akan dikirim: penerima, subjek, isi HTML, dan isi teks biasa.
 *
 * Isi teks biasa selalu ikut dikirim. Selain untuk aplikasi email yang tidak menampilkan HTML, email dengan dua
 * versi isi lebih jarang dianggap spam.
 */
final class Pesan
{
    public function __construct(
        public readonly string $kepada,
        public readonly string $subjek,
        public readonly string $html,
        public readonly string $teks,
        public readonly string $namaKepada = '',
        public readonly ?string $balasKe = null,
    ) {
        foreach (array_filter([$kepada, $balasKe]) as $alamat) {
            if (filter_var($alamat, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException("Alamat email tidak sah: {$alamat}");
            }
        }

        // Baris baru di kepala email bisa dipakai untuk menyisipkan kepala lain (header injection).
        foreach ([$subjek, $namaKepada] as $kepala) {
            if (preg_match('/[\r\n]/', $kepala) === 1) {
                throw new InvalidArgumentException('Subjek dan nama penerima tidak boleh berisi baris baru.');
            }
        }

        if (trim($subjek) === '') {
            throw new InvalidArgumentException('Subjek email wajib diisi.');
        }
    }
}
