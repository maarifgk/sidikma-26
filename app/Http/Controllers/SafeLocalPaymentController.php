<?php
namespace App\Http\Controllers;
use App\Services\Payments\SafeLocalPaymentService;use Illuminate\Http\Request;
class SafeLocalPaymentController extends Controller{public function callback(Request $request,SafeLocalPaymentService $service){abort_unless($request->user()?->can('foundation.update'),403);$d=$request->validate(['order_id'=>['required','string'],'status'=>['required','string'],'transaction_id'=>['nullable','string'],'gross_amount'=>['nullable','numeric','gt:0'],'invoice_id'=>['nullable','integer','gt:0']]);return response()->json($service->callback($d['order_id'],$d['status'],$d['transaction_id']??null,isset($d['gross_amount'])?(string)$d['gross_amount']:null,$d['invoice_id']??null));}}
