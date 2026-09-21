<?php
namespace App\Services\Payments;
use App\Contracts\PaymentGatewayInterface; use App\Models\PaymentInvoice;
final class LocalPaymentGateway implements PaymentGatewayInterface { public function create(PaymentInvoice $invoice,string $orderId):array{return ['order_id'=>$orderId,'status'=>'pending','gross_amount'=>(string)$invoice->amount];} public function callback(string $orderId,string $status,?string $transactionId=null):array{return array_filter(['order_id'=>$orderId,'status'=>$status,'transaction_id'=>$transactionId],fn($v)=>$v!==null);} }
