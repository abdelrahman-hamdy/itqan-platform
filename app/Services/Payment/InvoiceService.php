<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Payment\DTOs\InvoiceData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating invoice data and PDFs from completed payments.
 *
 * Generates structured invoice data with proper invoice numbers
 * following the format: INV-{academy_id}-{YYYYMM}-{sequence}.
 *
 * PDF generation is delegated to InvoicePdfGenerator.
 */
class InvoiceService
{
    public function __construct(
        protected InvoicePdfGenerator $pdfGenerator
    ) {}

    /**
     * Generate invoice data for a completed payment.
     *
     * If the payment already has an invoice_id (invoice number stored),
     * it returns the existing invoice data without generating a new number.
     *
     * @param  Payment  $payment  The completed payment to generate an invoice for
     * @return InvoiceData The structured invoice data
     */
    public function generateInvoice(Payment $payment): InvoiceData
    {
        $payment->loadMissing(['academy', 'user', 'payable']);

        // If payment already has an invoice number in metadata, reuse it
        $existingInvoiceNumber = $this->getExistingInvoiceNumber($payment);

        if ($existingInvoiceNumber) {
            Log::info('Returning existing invoice data', [
                'payment_id' => $payment->id,
                'invoice_number' => $existingInvoiceNumber,
            ]);

            return InvoiceData::fromPayment($payment, $existingInvoiceNumber);
        }

        // Generate a new invoice number with sequence lock
        $invoiceNumber = $this->generateInvoiceNumber($payment);

        // Store the invoice number on the payment record
        $this->storeInvoiceNumber($payment, $invoiceNumber);

        Log::info('Invoice generated successfully', [
            'payment_id' => $payment->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $payment->amount,
            'academy_id' => $payment->academy_id,
        ]);

        return InvoiceData::fromPayment($payment, $invoiceNumber);
    }

    /**
     * Get invoice data for a payment without generating a new invoice.
     *
     * Returns null if no invoice has been generated for this payment.
     *
     * @param  Payment  $payment  The payment to get invoice data for
     * @return InvoiceData|null The invoice data, or null if not yet generated
     */
    public function getInvoice(Payment $payment): ?InvoiceData
    {
        $invoiceNumber = $this->getExistingInvoiceNumber($payment);

        if (! $invoiceNumber) {
            return null;
        }

        return InvoiceData::fromPayment($payment, $invoiceNumber);
    }

    /**
     * Generate invoice data AND a PDF file for a completed payment.
     *
     * @return array{invoice: InvoiceData, pdf_path: string|null}
     */
    public function generateInvoiceWithPdf(Payment $payment): array
    {
        $invoiceData = $this->generateInvoice($payment);
        $pdfPath = $this->pdfGenerator->generate($invoiceData);

        if ($pdfPath) {
            // Store PDF path in payment metadata and receipt_url column
            $currentMetadata = $payment->metadata ?? [];
            $currentMetadata['invoice_pdf_path'] = $pdfPath;
            $payment->update([
                'metadata' => $currentMetadata,
                'receipt_url' => $pdfPath,
            ]);
        }

        return ['invoice' => $invoiceData, 'pdf_path' => $pdfPath];
    }

    /**
     * Get the PDF path for an existing invoice, or generate if missing.
     */
    public function getOrGeneratePdf(Payment $payment): ?string
    {
        $metadata = $payment->metadata ?? [];

        if (! empty($metadata['invoice_pdf_path'])) {
            return $metadata['invoice_pdf_path'];
        }

        $invoiceData = $this->getInvoice($payment);

        if (! $invoiceData) {
            $invoiceData = $this->generateInvoice($payment);
        }

        $pdfPath = $this->pdfGenerator->generate($invoiceData);

        if ($pdfPath) {
            $currentMetadata = $payment->metadata ?? [];
            $currentMetadata['invoice_pdf_path'] = $pdfPath;
            $payment->update([
                'metadata' => $currentMetadata,
                'receipt_url' => $pdfPath,
            ]);
        }

        return $pdfPath;
    }

    /**
     * Generate a unique invoice number in the format: INV-{academy_id}-{YYYYMM}-{sequence}.
     *
     * Uses database-level locking to ensure sequence uniqueness within
     * the same academy and month.
     *
     * @param  Payment  $payment  The payment to generate an invoice number for
     * @return string The generated invoice number
     */
    private function generateInvoiceNumber(Payment $payment): string
    {
        $academyId = $payment->academy_id;
        $yearMonth = now()->format('Ym');
        $prefix = "INV-{$academyId}-{$yearMonth}-";

        // Use a transaction with lock to ensure unique sequence numbers
        return DB::transaction(function () use ($prefix, $academyId) {
            // Find the highest existing sequence for this academy and month
            // We look in payment metadata for existing invoice numbers with this prefix
            $lastInvoice = Payment::withoutGlobalScopes()
                ->where('academy_id', $academyId)
                ->where('metadata->invoice_number', 'LIKE', $prefix.'%')
                ->lockForUpdate()
                ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.invoice_number')) AS CHAR) DESC")
                ->value('metadata');

            $sequence = 1;

            if ($lastInvoice) {
                $lastMetadata = is_string($lastInvoice) ? json_decode($lastInvoice, true) : $lastInvoice;
                $lastNumber = $lastMetadata['invoice_number'] ?? '';

                // Extract the sequence number from the last invoice number
                if (str_starts_with($lastNumber, $prefix)) {
                    $lastSequence = (int) substr($lastNumber, strlen($prefix));
                    $sequence = $lastSequence + 1;
                }
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the existing invoice number from the payment metadata.
     *
     * @param  Payment  $payment  The payment to check
     * @return string|null The existing invoice number, or null
     */
    private function getExistingInvoiceNumber(Payment $payment): ?string
    {
        $metadata = $payment->metadata;

        if (is_array($metadata) && ! empty($metadata['invoice_number'])) {
            return $metadata['invoice_number'];
        }

        return null;
    }

    /**
     * Store the invoice number in the payment's metadata and invoice_id fields.
     *
     * @param  Payment  $payment  The payment to update
     * @param  string  $invoiceNumber  The generated invoice number
     */
    private function storeInvoiceNumber(Payment $payment, string $invoiceNumber): void
    {
        $currentMetadata = $payment->metadata ?? [];
        $currentMetadata['invoice_number'] = $invoiceNumber;
        $currentMetadata['invoice_generated_at'] = now()->toIso8601String();

        $payment->update([
            'metadata' => $currentMetadata,
        ]);
    }
}
