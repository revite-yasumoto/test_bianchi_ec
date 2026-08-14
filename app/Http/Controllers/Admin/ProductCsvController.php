<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Csv\ImportCsvRequest;
use App\Services\Admin\Csv\CsvReader;
use App\Services\Admin\Csv\CsvWriter;
use App\Services\Admin\Csv\ProductCsvExporter;
use App\Services\Admin\Csv\ProductCsvImporter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCsvController extends Controller
{
    public function __construct(
        private readonly CsvReader $reader,
        private readonly CsvWriter $writer,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/Product/Csv', [
            'columns' => ProductCsvImporter::HEADER,
        ]);
    }

    public function import(ImportCsvRequest $request, ProductCsvImporter $importer): RedirectResponse
    {
        $result = $importer->import($this->reader->read($request->file('file')));

        return back()->with('importResult', $result->toArray());
    }

    public function export(ProductCsvExporter $exporter): StreamedResponse
    {
        return $this->writer->stream('products.csv', $exporter->header(), $exporter->rows());
    }

    public function template(): StreamedResponse
    {
        return $this->writer->stream('products_template.csv', ProductCsvImporter::HEADER, []);
    }
}
