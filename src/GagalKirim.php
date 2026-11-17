<?php

declare(strict_types=1);

namespace AdeMS\Dapur\Email;

use RuntimeException;

/** Email tidak bisa dikirim: pengiriman dimatikan, pengaturan belum lengkap, atau server email menolak. */
final class GagalKirim extends RuntimeException
{
}
