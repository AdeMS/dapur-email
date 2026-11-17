<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email\Test;

use AdeMS\Dapur\Email\Pesan;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PesanTest extends TestCase
{
    public function testPesanSah(): void
    {
        $pesan = new Pesan('budi@contoh.com', 'Halo', '<p>Halo</p>', 'Halo', 'Budi', 'admin@contoh.com');

        self::assertSame('budi@contoh.com', $pesan->kepada);
        self::assertSame('admin@contoh.com', $pesan->balasKe);
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function pesanTidakSah(): array
    {
        return [
            'alamat salah'           => ['bukan-email', 'Halo', ''],
            'subjek kosong'          => ['budi@contoh.com', '  ', ''],
            'baris baru di subjek'   => ['budi@contoh.com', "Halo\r\nBcc: x@contoh.com", ''],
            'baris baru di nama'     => ['budi@contoh.com', 'Halo', "Budi\nBcc: x@contoh.com"],
        ];
    }

    #[DataProvider('pesanTidakSah')]
    public function testPesanTidakSahDitolak(string $kepada, string $subjek, string $nama): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Pesan($kepada, $subjek, '<p>x</p>', 'x', $nama);
    }
}
