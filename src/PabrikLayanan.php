<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

/** Satu factory untuk semua layanan paket; berupa kelas agar konfigurasi tetap bisa di-cache. */
final class PabrikLayanan
{
    public function __invoke(ContainerInterface $c, string $nama): object
    {
        return match ($nama) {
            Pengaturan::class => new Pengaturan(self::config($c)),
            PengirimEmail::class => self::pengirim($c->get(Pengaturan::class)),
            SuratTemplat::class => new SuratTemplat($c->get(TemplateRendererInterface::class)),
            default => throw new RuntimeException("Layanan email tidak dikenal: {$nama}"),
        };
    }

    public static function pengirim(Pengaturan $pengaturan): PengirimEmail
    {
        return match ($pengaturan->cara) {
            Pengaturan::SMTP => new PengirimSmtp($pengaturan),
            Pengaturan::FILE => new PengirimFile($pengaturan),
            default => new PengirimMati(),
        };
    }

    /** @return array<string, mixed> */
    private static function config(ContainerInterface $c): array
    {
        $config = $c->has('config') ? $c->get('config') : [];

        return is_array($config['email'] ?? null) ? $config['email'] : [];
    }
}
