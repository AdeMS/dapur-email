<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use Mezzio\Template\TemplateRendererInterface;

/**
 * Menyusun Pesan dari template: '<nama>' untuk isi HTML (dibungkus layout 'email::layout') dan '<nama>-teks' untuk
 * isi teks biasa (tanpa layout). Template ditulis seperti template halaman biasa (laminas-view), jadi bisa ditimpa
 * dari config 'templates' => ['paths' => ...].
 */
final class SuratTemplat
{
    public const LAYOUT = 'email::layout';

    public function __construct(private readonly TemplateRendererInterface $renderer)
    {
    }

    /** @param array<string, mixed> $data */
    public function buat(string $template, array $data, string $kepada, string $subjek, string $namaKepada = ''): Pesan
    {
        $data += ['subjek' => $subjek, 'namaKepada' => $namaKepada];

        // Layout dirender sebagai template biasa, bukan lewat opsi 'layout' renderer: renderer Mezzio tidak
        // meneruskan data template ke layout, sehingga subjek tidak akan sampai ke sana.
        $isi  = $this->renderer->render($template, ['layout' => false] + $data);
        $html = $this->renderer->render(self::LAYOUT, ['layout' => false, 'content' => $isi] + $data);

        return new Pesan(
            $kepada,
            $subjek,
            $html,
            trim($this->renderer->render($template . '-teks', ['layout' => false] + $data)) . "\n",
            $namaKepada,
        );
    }
}
