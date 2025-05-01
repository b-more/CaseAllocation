<?php

namespace App\Http\Controllers;

use App\Models\InquiryFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InquiryFileController extends Controller
{
    /**
     * Export inquiry file to PDF
     */
    public function exportPdf(Request $request, $id)
{
    $inquiryFile = InquiryFile::with([
        'status',
        'courtType',
        'courtStage',
        'officer',
        'offence',
        'pinkFile',

    ])->findOrFail($id);

    // Get accused persons
    $accusedPersons = $inquiryFile->accused()->get();

    // Get status history
    $statusHistory = $inquiryFile->statusChanges()
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->get();

    $pdf = PDF::loadView('pdf.inquiry-file', [
        'inquiryFile' => $inquiryFile,
        'accusedPersons' => $accusedPersons,
        'statusHistory' => $statusHistory,
        'generatedDate' => now()->format('d M Y H:i'),
    ]);

    // Sanitize the filename - replace slashes with hyphens or underscores
    $sanitizedNumber = str_replace('/', '-', $inquiryFile->if_number);

    return $pdf->download("inquiry-file-{$sanitizedNumber}.pdf");
}
}
