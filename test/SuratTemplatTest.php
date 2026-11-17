<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email\Test;

use AdeMS\Dapur\Email\SuratTemplat;
use Mezzio\Template\TemplatePath;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;

final class SuratTemplatTest extends TestCase
{
    public function testHtmlDenganLayoutDanTeksTanpaLayout(): void
    {
        $renderer = new class implements TemplateRendererInterface {
            /** @var list<array{0: string, 1: array<string, mixed>}> */
            public array $panggilan = [];

            public function render(string $name, $params = []): string
            {
                $this->panggilan[] = [$name, (array) $params];

                return "[{$name}]";
            }

            public function addPath(string $path, ?string $namespace = null): void
            {
            }

            /** @return TemplatePath[] */
            public function getPaths(): array
            {
                return [];
            }

            public function addDefaultParam(string $templateName, string $param, mixed $value): void
            {
            }
        };

        $pesan = (new SuratTemplat($renderer))->buat('user::undangan', ['tautan' => 'https://contoh.com/x'], 'budi@contoh.com', 'Undangan', 'Budi');

        // Isi dirender tanpa layout, lalu dimasukkan ke layout yang dirender sebagai template biasa.
        self::assertSame('[email::layout]', $pesan->html);
        self::assertSame("[user::undangan-teks]\n", $pesan->teks);
        self::assertSame('Budi', $pesan->namaKepada);
        self::assertSame(['user::undangan', SuratTemplat::LAYOUT, 'user::undangan-teks'], array_column($renderer->panggilan, 0));
        self::assertFalse($renderer->panggilan[0][1]['layout']);
        self::assertFalse($renderer->panggilan[1][1]['layout']);
        self::assertSame('[user::undangan]', $renderer->panggilan[1][1]['content']);
        self::assertSame('Undangan', $renderer->panggilan[1][1]['subjek']);
        self::assertFalse($renderer->panggilan[2][1]['layout']);
        self::assertSame('https://contoh.com/x', $renderer->panggilan[2][1]['tautan']);
    }
}
