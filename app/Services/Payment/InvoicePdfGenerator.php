<?php

namespace App\Services\Payment;

use App\Services\Payment\DTOs\InvoiceData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TCPDF;

/**
 * Generates PDF invoices from InvoiceData DTOs.
 *
 * Uses TCPDF for Arabic/RTL support. Generated PDFs are stored
 * in the configured disk under invoices/{payment_id}.pdf.
 */
class InvoicePdfGenerator
{
    /**
     * Generate a PDF invoice and store it.
     *
     * @return string|null The storage path of the generated PDF, or null on failure
     */
    public function generate(InvoiceData $invoice): ?string
    {
        try {
            $pdf = $this->createPdf($invoice);
            $path = "invoices/{$invoice->paymentId}.pdf";

            $content = $pdf->Output('', 'S');
            Storage::disk('local')->makeDirectory('invoices');
            Storage::disk('local')->put($path, $content);

            Log::info('Invoice PDF generated', [
                'invoice_number' => $invoice->invoiceNumber,
                'payment_id' => $invoice->paymentId,
                'path' => $path,
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Failed to generate invoice PDF', [
                'invoice_number' => $invoice->invoiceNumber,
                'payment_id' => $invoice->paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate PDF content without storing (for streaming).
     */
    public function generateContent(InvoiceData $invoice): string
    {
        $pdf = $this->createPdf($invoice);

        return $pdf->Output('', 'S');
    }

    /**
     * Build the TCPDF document.
     */
    protected function createPdf(InvoiceData $invoice): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('Itqan Platform');
        $pdf->SetAuthor($invoice->academyName);
        $pdf->SetTitle(__('payments.invoice.title').' - '.$invoice->invoiceNumber);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);

        $pdf->AddPage();
        $pdf->setRTL(true);

        $this->addHeader($pdf, $invoice);
        $pdf->Ln(8);
        $this->addParties($pdf, $invoice);
        $pdf->Ln(8);
        $this->addLineItems($pdf, $invoice);
        $pdf->Ln(6);
        $this->addTotals($pdf, $invoice);
        $pdf->Ln(10);
        $this->addFooter($pdf, $invoice);

        return $pdf;
    }

    protected function addHeader(TCPDF $pdf, InvoiceData $invoice): void
    {
        $pdf->SetFont('dejavusans', 'B', 18);
        $pdf->Cell(0, 12, __('payments.invoice.title'), 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 7, __('payments.invoice.number').': '.$invoice->invoiceNumber, 0, 1, 'C');
        $pdf->Cell(0, 7, __('payments.invoice.date').': '.$invoice->issuedAt->format('Y-m-d'), 0, 1, 'C');
    }

    protected function addParties(TCPDF $pdf, InvoiceData $invoice): void
    {
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(90, 7, __('payments.invoice.from'), 0, 0, 'R');
        $pdf->Cell(90, 7, __('payments.invoice.to'), 0, 1, 'R');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 6, $invoice->academyName, 0, 0, 'R');
        $pdf->Cell(90, 6, $invoice->customerName, 0, 1, 'R');

        if ($invoice->customerEmail) {
            $pdf->Cell(90, 6, '', 0, 0);
            $pdf->Cell(90, 6, $invoice->customerEmail, 0, 1, 'R');
        }

        if ($invoice->customerPhone) {
            $pdf->Cell(90, 6, '', 0, 0);
            $pdf->Cell(90, 6, $invoice->customerPhone, 0, 1, 'R');
        }
    }

    protected function addLineItems(TCPDF $pdf, InvoiceData $invoice): void
    {
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);

        // Table header
        $pdf->Cell(25, 8, __('payments.invoice.total'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, __('payments.invoice.unit_price'), 1, 0, 'C', true);
        $pdf->Cell(20, 8, __('payments.invoice.qty'), 1, 0, 'C', true);
        $pdf->Cell(110, 8, __('payments.invoice.description'), 1, 1, 'C', true);

        $pdf->SetFont('dejavusans', '', 10);

        if (empty($invoice->lineItems)) {
            // Fallback: single line item from subscription name or description
            $description = $invoice->subscriptionName ?? $invoice->description ?? __('payments.invoice.payment');
            $amount = $invoice->getSubtotal();

            $pdf->Cell(25, 7, number_format($amount, 2), 1, 0, 'C');
            $pdf->Cell(25, 7, number_format($amount, 2), 1, 0, 'C');
            $pdf->Cell(20, 7, '1', 1, 0, 'C');
            $pdf->Cell(110, 7, $description, 1, 1, 'R');
        } else {
            foreach ($invoice->lineItems as $item) {
                $pdf->Cell(25, 7, number_format($item['total'], 2), 1, 0, 'C');
                $pdf->Cell(25, 7, number_format($item['unit_price'], 2), 1, 0, 'C');
                $pdf->Cell(20, 7, $item['quantity'], 1, 0, 'C');
                $pdf->Cell(110, 7, $item['description'], 1, 1, 'R');
            }
        }
    }

    protected function addTotals(TCPDF $pdf, InvoiceData $invoice): void
    {
        $labelWidth = 140;
        $valueWidth = 40;

        $pdf->SetFont('dejavusans', '', 10);

        // Subtotal
        $pdf->Cell($valueWidth, 7, number_format($invoice->getSubtotal(), 2).' '.$invoice->currency, 0, 0, 'C');
        $pdf->Cell($labelWidth, 7, __('payments.invoice.subtotal'), 0, 1, 'R');

        // Discount (if any)
        if ($invoice->discountAmount > 0) {
            $pdf->Cell($valueWidth, 7, '-'.number_format($invoice->discountAmount, 2).' '.$invoice->currency, 0, 0, 'C');
            $pdf->Cell($labelWidth, 7, __('payments.invoice.discount'), 0, 1, 'R');
        }

        // Tax
        $pdf->Cell($valueWidth, 7, number_format($invoice->taxAmount, 2).' '.$invoice->currency, 0, 0, 'C');
        $pdf->Cell($labelWidth, 7, __('payments.invoice.tax', ['percent' => $invoice->taxPercentage]), 0, 1, 'R');

        // Total
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell($valueWidth, 8, number_format($invoice->amount, 2).' '.$invoice->currency, 'T', 0, 'C');
        $pdf->Cell($labelWidth, 8, __('payments.invoice.total_amount'), 'T', 1, 'R');
    }

    protected function addFooter(TCPDF $pdf, InvoiceData $invoice): void
    {
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(128, 128, 128);

        $pdf->Cell(0, 5, __('payments.invoice.payment_method').': '.$invoice->paymentMethod, 0, 1, 'C');

        if ($invoice->paidAt) {
            $pdf->Cell(0, 5, __('payments.invoice.paid_at').': '.$invoice->paidAt->format('Y-m-d H:i'), 0, 1, 'C');
        }

        if (! empty($invoice->metadata['transaction_id'])) {
            $pdf->Cell(0, 5, __('payments.invoice.transaction_id').': '.$invoice->metadata['transaction_id'], 0, 1, 'C');
        }

        $pdf->Ln(5);
        $pdf->Cell(0, 5, __('payments.invoice.generated_by_itqan'), 0, 1, 'C');
    }
}
