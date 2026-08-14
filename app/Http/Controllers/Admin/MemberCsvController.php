<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Csv\ImportCsvRequest;
use App\Services\Admin\Csv\CsvReader;
use App\Services\Admin\Csv\CsvWriter;
use App\Services\Admin\Csv\MemberCsvExporter;
use App\Services\Admin\Csv\MemberCsvImporter;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberCsvController extends Controller
{
    public function __construct(
        private readonly CsvReader $reader,
        private readonly CsvWriter $writer,
    ) {}

    public function import(ImportCsvRequest $request, MemberCsvImporter $importer): RedirectResponse
    {
        $result = $importer->import($this->reader->read($request->file('file')));

        return back()->with('importResult', $result->toArray());
    }

    public function export(MemberCsvExporter $exporter): StreamedResponse
    {
        return $this->writer->stream('members.csv', $exporter->header(), $exporter->rows());
    }
}
