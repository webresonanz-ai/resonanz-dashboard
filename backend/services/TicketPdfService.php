<?php

declare(strict_types=1);

namespace Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class TicketPdfService
{
    public function generate(string $templatePath, string $name, string $registrationCode): string
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('PDF ticket dependencies are missing. Run composer install in the backend directory.');
        }
        require_once $autoload;

        $template = $this->resolveTemplatePath($templatePath);
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($template);
        if ($pageCount < 1) {
            throw new RuntimeException('Ticket template PDF has no pages.');
        }

        $page = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($page);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($page);

        $pageWidth = $size['width'];
        $pageHeight = $size['height'];
        $qrSize = min(50, $pageWidth * 0.3, $pageHeight * 0.18);
        $qrX = ($pageWidth - $qrSize) / 2;
        $qrY = ($pageHeight * 0.38) - ($qrSize / 2);

        $temporaryQr = tempnam(sys_get_temp_dir(), 'rz_ticket_qr_');
        if ($temporaryQr === false) {
            throw new RuntimeException('Could not create temporary QR image.');
        }

        try {
            $qr = new QrCode(
                data: $registrationCode,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 600,
                margin: 20,
            );
            $vendorDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'endroid' . DIRECTORY_SEPARATOR . 'qr-code' . DIRECTORY_SEPARATOR;
            // Endroid 5.1 emits PHP 8.5 signature deprecations; keep filtering scoped to its QR renderer.
            set_error_handler(static function (int $severity, string $message, string $file) use ($vendorDirectory): bool {
                return in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)
                    && str_starts_with($file, $vendorDirectory);
            });
            try {
                $png = (new PngWriter())->write($qr)->getString();
            } finally {
                restore_error_handler();
            }
            if (file_put_contents($temporaryQr, $png) === false) {
                throw new RuntimeException('Could not write temporary QR image.');
            }
            $pdf->Image($temporaryQr, $qrX, $qrY, $qrSize, $qrSize, 'PNG');
        } finally {
            if (is_file($temporaryQr) && !unlink($temporaryQr)) {
                throw new RuntimeException('Could not remove temporary QR image.');
            }
        }

        $displayName = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', trim($name));
        if ($displayName === false) {
            throw new RuntimeException('Could not encode audience name for the ticket PDF.');
        }

        $fontSize = 22;
        $maxTextWidth = $pageWidth - 24;
        $pdf->SetFont('Helvetica', 'B', $fontSize);
        while ($fontSize > 10 && $pdf->GetStringWidth($displayName) > $maxTextWidth) {
            $pdf->SetFont('Helvetica', 'B', --$fontSize);
        }

        $nameY = $pageHeight * 0.72;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(10, $nameY - 1, $pageWidth - 20, 12, 'F');
        $pdf->SetTextColor(45, 30, 30);
        $pdf->SetXY(10, $nameY);
        $pdf->Cell($pageWidth - 20, 10, $displayName, 0, 0, 'C');

        return $pdf->Output('S');
    }

    private function resolveTemplatePath(string $templatePath): string
    {
        if (!preg_match('#\A/uploads/ticket-templates/[A-Za-z0-9_.-]+\.pdf\z#i', $templatePath)) {
            throw new RuntimeException('Ticket template path is invalid.');
        }

        $uploadDirectory = realpath(__DIR__ . '/../public/uploads/ticket-templates');
        $template = realpath(__DIR__ . '/../public' . $templatePath);
        if (
            $uploadDirectory === false
            || $template === false
            || !is_file($template)
            || !str_starts_with($template, $uploadDirectory . DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('Ticket template PDF could not be found.');
        }

        return $template;
    }
}
