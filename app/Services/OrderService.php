<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    protected $affiliateService;
    public function __construct(AffiliateService $affiliateService) {
        $this->affiliateService = $affiliateService;
    }

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method
        // Check if there is an existing order with the same external_order_id
        $existingOrder = Order::where('external_order_id', $data['order_id'])->first();
        if ($existingOrder) {
            return; // Ignore duplicate orders
        }

        // Create a new affiliate if the customer email is not already associated with one
        $affiliate = Affiliate::where('merchant_id', $this->merchant->id)
            ->where('discount_code', $data['discount_code'])
            ->first();
        if (!$affiliate) {
            $affiliateService = app(AffiliateService::class);
            $affiliate = $affiliateService->register($this->merchant, $data['customer_email'], $data['customer_name'], 0.1, $data['discount_code']);
        }

        // Create a new order and log the commission
        $order = new Order();
        $order->subtotal = $data['subtotal_price'];
        $order->affiliate_id = $affiliate->id;
        $order->merchant_id = $this->merchant->id;
        $order->commission_owed = $data['subtotal_price'] * $affiliate->commission_rate;
        $order->external_order_id = $data['order_id'];
        $order->save();
    }
}
