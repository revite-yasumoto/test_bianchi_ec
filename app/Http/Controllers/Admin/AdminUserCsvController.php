<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Csv\ImportCsvRequest;
use App\Services\Admin\Csv\AdminUserCsvExporter;
use App\Services\Admin\Csv\AdminUserCsvImporter;
use App\Services\Admin\Csv\CsvReader;
use App\Services\Admin\Csv\CsvWriter;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserCsvController extends Controller
{
    public function __construct(
        private readonly CsvReader $reader,
        private readonly CsvWriter $writer,
    ) {}

    public function import(ImportCsvRequest $request, AdminUserCsvImporter $importer): RedirectResponse
    {
        $result = $importer->import($this->reader->read($request->file('file')));

        return back()->with('importResult', $result->toArray());
    }

    public function export(AdminUserCsvExporter $exporter): StreamedResponse
    {
        return $this->writer->stream('admins.csv', $exporter->header(), $exporter->rows());
    }
}
