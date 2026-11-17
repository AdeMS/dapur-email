<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email\Test;

use AdeMS\Dapur\Email\GagalKirim;
use AdeMS\Dapur\Email\Pengaturan;
use AdeMS\Dapur\Email\PengirimFile;
use AdeMS\Dapur\Email\PengirimMati;
use AdeMS\Dapur\Email\PengirimSmtp;
use AdeMS\Dapur\Email\Pesan;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

final class PengirimTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        $this->folder = sys_get_temp_dir() . '/dapur-email-uji-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->folder . '/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->folder)) {
            rmdir($this->folder);
        }
    }

    private function pesan(): Pesan
    {
        return new Pesan('budi@contoh.com', 'Undangan Dapur', '<p>Halo <b>Budi</b></p>', 'Halo Budi', 'Budi Santoso');
    }

    /** @param array<string, mixed> $tambahan */
    private function pengaturan(string $cara, array $tambahan = []): Pengaturan
    {
        return new Pengaturan($tambahan + [
            'cara'     => $cara,
            'smtp'     => ['host' => 'mail.contoh.com'],
            'pengirim' => ['alamat' => 'noreply@contoh.com', 'nama' => 'Dapur Uji'],
            'folder'   => $this->folder,
        ]);
    }

    public function testFileMenulisEml(): void
    {
        (new PengirimFile($this->pengaturan('file')))->kirim($this->pesan());

        $file = glob($this->folder . '/*.eml') ?: [];
        self::assertCount(1, $file);
        self::assertSame(0660, fileperms($file[0]) & 0777);

        $isi = (string) file_get_contents($file[0]);
        self::assertStringContainsString('Subject: Undangan Dapur', $isi);
        self::assertStringContainsString('From: Dapur Uji <noreply@contoh.com>', $isi);
        self::assertStringContainsString('To: Budi Santoso <budi@contoh.com>', $isi);
        self::assertStringContainsString('text/plain', $isi);
        self::assertStringContainsString('text/html', $isi);
    }

    public function testSmtpMengirimLewatTransport(): void
    {
        $transport = new class extends AbstractTransport {
            /** @var list<Email> */
            public array $terkirim = [];

            protected function doSend(SentMessage $message): void
            {
                $asli = $message->getOriginalMessage();
                assert($asli instanceof Email);
                $this->terkirim[] = $asli;
            }

            public function __toString(): string
            {
                return 'uji://';
            }
        };

        (new PengirimSmtp($this->pengaturan('smtp'), $transport))->kirim($this->pesan());

        self::assertCount(1, $transport->terkirim);
        self::assertSame('Undangan Dapur', $transport->terkirim[0]->getSubject());
        self::assertSame('Halo Budi', $transport->terkirim[0]->getTextBody());
        self::assertSame('noreply@contoh.com', $transport->terkirim[0]->getFrom()[0]->getAddress());
    }

    public function testSmtpGagalMenjadiGagalKirim(): void
    {
        $transport = new class extends AbstractTransport {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('Connection refused');
            }

            public function __toString(): string
            {
                return 'uji://';
            }
        };

        $this->expectException(GagalKirim::class);
        $this->expectExceptionMessage('Connection refused');

        (new PengirimSmtp($this->pengaturan('smtp'), $transport))->kirim($this->pesan());
    }

    public function testSmtpBelumLengkapTidakMencobaMengirim(): void
    {
        $this->expectException(GagalKirim::class);
        $this->expectExceptionMessage('Alamat pengirim');

        (new PengirimSmtp(new Pengaturan(['cara' => 'smtp', 'smtp' => ['host' => 'x']])))->kirim($this->pesan());
    }

    public function testMati(): void
    {
        $this->expectException(GagalKirim::class);

        (new PengirimMati())->kirim($this->pesan());
    }

    public function testSmtpMembuatTransportDariPengaturan(): void
    {
        // Tanpa transport uji, PengirimSmtp membuat transport SMTP sungguhan; port 9 (discard) di localhost tidak
        // menerima koneksi SMTP, jadi pengiriman gagal dengan GagalKirim tanpa menunggu lama.
        $atur = $this->pengaturan('smtp', ['smtp' => ['host' => '127.0.0.1', 'port' => 9, 'enkripsi' => 'tidak']]);

        $this->expectException(GagalKirim::class);

        (new PengirimSmtp($atur))->kirim($this->pesan());
    }
}
