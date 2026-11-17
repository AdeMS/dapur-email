<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

/**
 * Layanan email untuk modul lain: PengirimEmail (cara kirim sesuai pengaturan) dan SuratTemplat (menyusun email dari
 * template). Cantumkan kelas ini di config/modul.local.php milik inti, atau biarkan modul yang memakainya
 * menyertakannya.
 *
 * Sengaja TIDAK memberi nilai bawaan untuk kunci 'email': bawaannya ada di kelas Pengaturan, agar pengaturan di
 * config/autoload inti tidak tertimpa modul yang dimuat belakangan.
 */
final class ConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => array_fill_keys([
                    Pengaturan::class,
                    PengirimEmail::class,
                    SuratTemplat::class,
                ], PabrikLayanan::class),
            ],
            'templates'    => [
                'paths' => [
                    'email' => [dirname(__DIR__) . '/templates'],
                ],
            ],
        ];
    }
}
