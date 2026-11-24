<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use RuntimeException;

final class PostingErrorPresenter
{
    public const ZERO_TOTAL_INVOICE_MESSAGE = 'Total faktur Rp 0 karena diskon 100% — faktur tidak dapat diposting. Periksa kembali diskon atau nilai baris.';

    public static function zeroTotalInvoiceMessage(): string
    {
        return self::ZERO_TOTAL_INVOICE_MESSAGE;
    }

    public static function present(\Throwable $e, ?string $context = null): string
    {
        if (stripos($e->getMessage(), 'must have debit or credit') !== false) {
            return self::zeroTotalInvoiceMessage();
        }

        if ($e instanceof RuntimeException) {
            if (stripos($e->getMessage(), 'closed period') !== false) {
                if ($context !== null && $context !== '') {
                    $periodLabel = Carbon::parse($context)->locale('id')->translatedFormat('F Y');

                    return "Tidak bisa memposting: periode {$periodLabel} sudah ditutup. Minta admin membuka periode tersebut lebih dulu, lalu posting ulang.";
                }

                return 'Tidak bisa memposting: periode akuntansi sudah ditutup. Minta admin membuka periode tersebut lebih dulu, lalu posting ulang.';
            }

            return 'Tidak bisa memposting: '.$e->getMessage();
        }

        if ($e instanceof InvalidArgumentException) {
            return 'Data jurnal belum lengkap atau tidak seimbang: '.$e->getMessage();
        }

        report($e);

        return 'Terjadi kesalahan saat memposting dokumen. Silakan hubungi tim support dan sebutkan nomor dokumen ini.';
    }
}
