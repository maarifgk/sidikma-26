<?php

namespace App\Http\Controllers;

use App\Services\Payments\MidtransPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request, MidtransPaymentService $service): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:50'],
            'status_code' => ['required', 'string', 'max:3'],
            'gross_amount' => ['required', 'string', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'signature_key' => ['required', 'string', 'size:128'],
        ]);
        $service->notification($data);

        return response()->json(['status' => 'ok']);
    }
}
