<?php

namespace App\Http\Controllers;

use App\Models\ProformaInvoice;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function downloadQuotation(Quotation $quotation)
    {
        $quotation->load('products');

        $pdf = Pdf::loadView('pdf.quotation', compact('quotation'));

        return $pdf->download("Quotation-{$quotation->id}.pdf");
    }

    public function streamQuotation(Quotation $quotation)
    {
        $quotation->load('products');

        $pdf = Pdf::loadView('pdf.quotation', compact('quotation'));

        return $pdf->stream("Quotation-{$quotation->id}.pdf");
    }

    public function downloadProforma(ProformaInvoice $proforma)
    {
        $proforma->load('products');

        $pdf = Pdf::loadView('pdf.proforma', compact('proforma'));

        return $pdf->download("Proforma-{$proforma->id}.pdf");
    }

    public function streamProforma(ProformaInvoice $proforma)
    {
        $proforma->load('products');

        $pdf = Pdf::loadView('pdf.proforma', compact('proforma'));

        return $pdf->stream("Proforma-{$proforma->id}.pdf");
    }
}
