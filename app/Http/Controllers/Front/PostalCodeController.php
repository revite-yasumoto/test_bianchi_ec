<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Exceptions\PostalCodeLookupFailedException;
use App\Http\Controllers\Controller;
use App\Services\Front\PostalCode\PostalCodeLookupService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PostalCodeController extends Controller
{
    public function __invoke(string $postalCode, PostalCodeLookupService $lookup): JsonResponse
    {
        try {
            $address = $lookup->lookup($postalCode);
        } catch (PostalCodeLookupFailedException) {
            return response()->json(
                ['message' => '住所を取得できませんでした。'],
                Response::HTTP_BAD_GATEWAY,
            );
        }

        if ($address === null) {
            return response()->json(
                ['message' => '該当する住所が見つかりませんでした。'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return response()->json($address);
    }
}
