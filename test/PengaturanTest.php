<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email\Test;

use AdeMS\Dapur\Email\Pengaturan;
use AdeMS\Dapur\Email\PabrikLayanan;
use AdeMS\Dapur\Email\PengirimFile;
use AdeMS\Dapur\Email\PengirimMati;
use AdeMS\Dapur\Email\PengirimSmtp;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PengaturanTest extends TestCase
{
    public function testBawaanMati(): void
    {
        $atur = new Pengaturan([]);

        self::assertSame('mati', $atur->cara);
        self::assertNull($atur->kekurangan());
        self::assertInstanceOf(PengirimMati::class, PabrikLayanan::pengirim($atur));
        self::assertFalse(PabrikLayanan::pengirim($atur)->aktif());
    }

    public function testSmtpLengkapDenganPortBawaan(): void
    {
        $atur = new Pengaturan([
            'cara'     => 'smtp',
            'smtp'     => ['host' => 'mail.contoh.com', 'user' => 'noreply@contoh.com', 'sandi' => 'rahasia'],
            'pengirim' => ['alamat' => 'noreply@contoh.com'],
        ]);

        self::assertSame(587, $atur->port);
        self::assertSame('tls', $atur->enkripsi);
        self::assertSame('Dapur AdeMS', $atur->namaPengirim);
        self::assertNull($atur->kekurangan());
        self::assertInstanceOf(PengirimSmtp::class, PabrikLayanan::pengirim($atur));
        self::assertTrue(PabrikLayanan::pengirim($atur)->aktif());
        self::assertSame(465, (new Pengaturan(['smtp' => ['enkripsi' => 'ssl']]))->port);
    }

    public function testRingkasanTidakMenampilkanKataSandi(): void
    {
        $atur = new Pengaturan([
            'cara'     => 'smtp',
            'smtp'     => ['host' => 'mail.contoh.com', 'sandi' => 'rahasia-sekali'],
            'pengirim' => ['alamat' => 'noreply@contoh.com'],
        ]);

        $teks = implode("\n", $atur->ringkasan());
        self::assertStringNotContainsString('rahasia-sekali', $teks);
        self::assertStringContainsString('kata sandi terisi', $teks);
    }

    public function testKekurangan(): void
    {
        self::assertStringContainsString('Alamat pengirim', (string) (new Pengaturan(['cara' => 'smtp', 'smtp' => ['host' => 'x']]))->kekurangan());
        self::assertStringContainsString('Server SMTP', (string) (new Pengaturan(['cara' => 'smtp', 'pengirim' => ['alamat' => 'a@contoh.com']]))->kekurangan());
        self::assertFalse(PabrikLayanan::pengirim(new Pengaturan(['cara' => 'smtp']))->aktif());
        self::assertInstanceOf(PengirimFile::class, PabrikLayanan::pengirim(new Pengaturan(['cara' => 'file'])));
    }

    public function testCaraTidakDikenalDitolak(): void
    {
        $this->expectException(RuntimeException::class);

        new Pengaturan(['cara' => 'pos']);
    }

    public function testEnkripsiTidakDikenalDitolak(): void
    {
        $this->expectException(RuntimeException::class);

        new Pengaturan(['smtp' => ['enkripsi' => 'starttls']]);
    }
}
