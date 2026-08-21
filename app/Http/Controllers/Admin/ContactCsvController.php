<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Contact\BuildContactFilter;
use App\Http\Controllers\Controller;
use App\Services\Admin\Csv\ContactCsvExporter;
use App\Services\Admin\Csv\CsvWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactCsvController extends Controller
{
    public function __construct(private readonly CsvWriter $writer) {}

    public function export(
        Request $request,
        BuildContactFilter $buildFilter,
        ContactCsvExporter $exporter,
    ): StreamedResponse {
        $filters = $buildFilter->filtersOf($request);

        return $this->writer->stream(
            'contacts_'.now()->format('Ymd').'.csv',
            $exporter->header(),
            $exporter->rows($filters),
        );
    }
}
