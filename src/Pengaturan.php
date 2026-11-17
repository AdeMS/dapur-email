<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use Composer\InstalledVersions;
use RuntimeException;

/**
 * Isi $config['email'] yang sudah diperiksa.
 *
 * Nilai bawaan sengaja diletakkan di kelas ini, bukan di ConfigProvider, agar pengaturan di config/autoload inti
 * tidak tertimpa oleh modul yang dimuat belakangan (misalnya modul pilihan sebuah website).
 */
final class Pengaturan
{
    public const SMTP = 'smtp';
    public const FILE = 'file';
    public const MATI = 'mati';

    public const ENKRIPSI = ['tls', 'ssl', 'tidak'];

    /** Cara kirim: 'smtp', 'file' (ditulis ke folder sebagai berkas .eml), atau 'mati'. */
    public readonly string $cara;

    public readonly string $host;
    public readonly int $port;
    public readonly string $user;
    public readonly string $sandi;

    /** 'tls' (STARTTLS, biasanya port 587), 'ssl' (biasanya port 465), atau 'tidak'. */
    public readonly string $enkripsi;

    public readonly string $alamatPengirim;
    public readonly string $namaPengirim;

    /** Folder tujuan untuk cara 'file'. */
    public readonly string $folder;

    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $cara = $config['cara'] ?? self::MATI;
        if (! in_array($cara, [self::SMTP, self::FILE, self::MATI], true)) {
            throw new RuntimeException("Pengaturan 'cara' email harus 'smtp', 'file', atau 'mati'.");
        }

        $smtp     = is_array($config['smtp'] ?? null) ? $config['smtp'] : [];
        $pengirim = is_array($config['pengirim'] ?? null) ? $config['pengirim'] : [];
        $enkripsi = $smtp['enkripsi'] ?? 'tls';
        if (! in_array($enkripsi, self::ENKRIPSI, true)) {
            throw new RuntimeException("Pengaturan email 'smtp' => 'enkripsi' harus 'tls', 'ssl', atau 'tidak'.");
        }

        $this->cara           = $cara;
        $this->host           = self::teks($smtp['host'] ?? '');
        $this->port           = (int) ($smtp['port'] ?? ($enkripsi === 'ssl' ? 465 : 587));
        $this->user           = self::teks($smtp['user'] ?? '');
        $this->sandi          = is_string($smtp['sandi'] ?? null) ? $smtp['sandi'] : '';
        $this->enkripsi       = $enkripsi;
        $this->alamatPengirim = self::teks($pengirim['alamat'] ?? '');
        $this->namaPengirim   = self::teks($pengirim['nama'] ?? 'Dapur AdeMS');
        $this->folder         = self::teks($config['folder'] ?? '') ?: self::folderInti() . '/data/email';
    }

    /** Pesan kesalahan bila pengaturan belum lengkap untuk cara kirim ini, atau null bila sudah lengkap. */
    public function kekurangan(): ?string
    {
        if ($this->cara === self::MATI) {
            return null;
        }

        if (filter_var($this->alamatPengirim, FILTER_VALIDATE_EMAIL) === false) {
            return "Alamat pengirim belum diatur: 'email' => ['pengirim' => ['alamat' => ...]].";
        }

        if ($this->cara === self::SMTP && $this->host === '') {
            return "Server SMTP belum diatur: 'email' => ['smtp' => ['host' => ...]].";
        }

        if ($this->port < 1 || $this->port > 65535) {
            return 'Port SMTP tidak sah.';
        }

        return null;
    }

    /** Ringkasan pengaturan tanpa kata sandi, untuk ditampilkan perintah `info`. */
    public function ringkasan(): array
    {
        return [
            'cara'     => $this->cara,
            'pengirim' => trim($this->namaPengirim . ' <' . $this->alamatPengirim . '>'),
            'smtp'     => $this->cara === self::SMTP
                ? sprintf('%s:%d (%s), user %s, kata sandi %s', $this->host, $this->port, $this->enkripsi, $this->user ?: '-', $this->sandi === '' ? 'kosong' : 'terisi')
                : '-',
            'folder'   => $this->cara === self::FILE ? $this->folder : '-',
        ];
    }

    private static function teks(mixed $nilai): string
    {
        return is_string($nilai) ? trim($nilai) : '';
    }

    private static function folderInti(): string
    {
        $folder = InstalledVersions::getRootPackage()['install_path'];

        return rtrim((string) (realpath($folder) ?: $folder), '/');
    }
}
