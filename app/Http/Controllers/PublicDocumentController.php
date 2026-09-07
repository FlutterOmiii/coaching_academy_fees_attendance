<?php

namespace App\Http\Controllers;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\MatchFee;
use App\Models\Setting;

/**
 * Guardian-facing fee documents behind signed URLs (no login). Each method
 * flattens one record into the shared public document template, so every
 * receipt and invoice a parent opens from WhatsApp looks the same.
 */
class PublicDocumentController extends Controller
{
    /** Monthly fee payment receipt. */
    public function receipt(FeePayment $payment)
    {
        $payment->load('student', 'invoice.batch');
        $currency = Setting::get('currency_symbol', '₹');

        return view('public.document', [
            'docTitle' => 'Payment Receipt',
            'docNo' => $payment->receipt_no,
            'docDate' => $payment->payment_date?->format('d M Y'),
            'student' => $payment->student,
            'badge' => 'Payment Received',
            'rows' => array_filter([
                'For the month of' => $payment->invoice?->period_label,
                'Batch' => $payment->invoice?->batch?->name,
                'Payment Mode' => $payment->mode_label,
                'Reference No' => $payment->reference_no,
            ]),
            'amountLabel' => 'Amount Received',
            'amount' => $currency.number_format((float) $payment->amount, 2),
            'footNote' => ((float) ($payment->invoice?->balance_amount ?? 0)) <= 0
                ? 'The fee for '.$payment->invoice?->period_label.' is fully paid. Thank you!'
                : 'Remaining balance: '.$currency.number_format((float) $payment->invoice->balance_amount, 2),
        ]);
    }

    /** Match fee receipt. */
    public function matchReceipt(MatchFee $matchFee)
    {
        abort_unless($matchFee->status === 'paid', 404);
        $matchFee->load('student', 'match');
        $currency = Setting::get('currency_symbol', '₹');

        return view('public.document', [
            'docTitle' => 'Match Fee Receipt',
            'docNo' => $matchFee->receipt_no,
            'docDate' => $matchFee->payment_date?->format('d M Y'),
            'student' => $matchFee->student,
            'badge' => 'Payment Received',
            'rows' => array_filter([
                'Match' => $matchFee->match?->title,
                'Match Date' => $matchFee->match?->match_date?->format('d M Y'),
                'Venue' => $matchFee->match?->venue,
                'Payment Mode' => $matchFee->mode_label,
                'Reference No' => $matchFee->reference_no,
            ]),
            'amountLabel' => 'Amount Received',
            'amount' => $currency.number_format((float) $matchFee->amount, 2),
            'footNote' => 'The match fee is fully paid. Thank you!',
        ]);
    }

    /** Monthly fee invoice / bill. */
    public function invoice(FeeInvoice $invoice)
    {
        $invoice->load('student', 'batch');
        $currency = Setting::get('currency_symbol', '₹');
        $money = fn ($v) => $currency.number_format((float) $v, 2);
        $balance = (float) $invoice->balance_amount;

        return view('public.document', [
            'docTitle' => 'Fee Invoice',
            'docNo' => $invoice->invoice_no,
            'docDate' => $invoice->issue_date?->format('d M Y'),
            'student' => $invoice->student,
            'badge' => $balance <= 0 ? 'Fully Paid' : 'Payment Due',
            'badgeTone' => $balance <= 0 ? 'paid' : 'due',
            'rows' => array_filter([
                'For the month of' => $invoice->period_label,
                'Batch' => $invoice->batch?->name,
                'Fee Amount' => $money($invoice->amount),
                'Discount' => (float) $invoice->discount > 0 ? '− '.$money($invoice->discount) : null,
                'Late Fee' => (float) $invoice->late_fee > 0 ? '+ '.$money($invoice->late_fee) : null,
                'Total' => $money($invoice->total_amount),
                'Paid' => $money($invoice->paid_amount),
                'Due Date' => $invoice->due_date?->format('d M Y'),
            ]),
            'amountLabel' => $balance <= 0 ? 'Total Paid' : 'Balance Payable',
            'amount' => $balance <= 0 ? $money($invoice->total_amount) : $money($balance),
            'footNote' => $balance <= 0
                ? 'This invoice is fully settled. Thank you!'
                : 'Kindly pay before '.$invoice->due_date?->format('d M Y').'. Please ignore if already paid.',
        ]);
    }
}
