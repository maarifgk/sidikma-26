<?php
namespace App\Contracts;
use App\Models\PaymentInvoice;
interface PaymentGatewayInterface { public function create(PaymentInvoice $invoice,string $orderId):array; public function callback(string $orderId,string $status,?string $transactionId=null):array; }
