<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Mengirim email lewat server SMTP, misalnya server email hosting. Memakai symfony/mailer.
 *
 * Dengan 'enkripsi' => 'tls', koneksi dimulai biasa lalu ditingkatkan dengan STARTTLS (umumnya port 587); dengan
 * 'ssl', koneksi terenkripsi sejak awal (umumnya port 465).
 */
final class PengirimSmtp implements PengirimEmail
{
    private ?TransportInterface $transport;

    public function __construct(
        private readonly Pengaturan $pengaturan,
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport;
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

        try {
            $this->transport()->send(SuratSymfony::dari($pesan, $this->pengaturan));
        } catch (TransportExceptionInterface $e) {
            // Pesan symfony/mailer tidak memuat kata sandi; tetap diringkas agar jelas bagi admin.
            throw new GagalKirim('Server email menolak atau tidak bisa dihubungi: ' . $e->getMessage(), 0, $e);
        }
    }

    private function transport(): TransportInterface
    {
        if ($this->transport === null) {
            $p      = $this->pengaturan;
            $opsi   = $p->enkripsi === 'tidak' ? ['auto_tls' => 'false'] : [];
            $skema  = $p->enkripsi === 'ssl' ? 'smtps' : 'smtp';
            $dsn    = new Dsn($skema, $p->host, $p->user !== '' ? $p->user : null, $p->sandi !== '' ? $p->sandi : null, $p->port, $opsi);

            $this->transport = (new EsmtpTransportFactory())->create($dsn);
        }

        return $this->transport;
    }
}
