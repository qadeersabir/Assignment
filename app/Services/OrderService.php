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
        // Check if an affiliate with the given email already exists
        $affiliate = Affiliate::where('email', $data['customer_email'])->first();
        if (!$affiliate) {
            // If not, create a new affiliate associated with the merchant
            $affiliate = $this->affiliateService->register(
                $this->merchant,
                $data['customer_email'],
                $data['customer_name'],
                0.1 // Default commission rate for new affiliates
            );
        }

        // Check if an order with the given order_id already exists
        if (Order::where('external_order_id', $data['order_id'])->exists()) {
            // If so, return without processing the order any further
            return;
        }

        // Create a new order and associate it with the merchant and affiliate
        $order = new Order();
        $order->subtotal = $data['subtotal_price'];
        $order->merchant_id = $this->merchant->id;
        $order->affiliate_id = $affiliate->id;
        $order->external_order_id = $data['order_id'];
        if ($data['discount_code']) {
            // Associate the discount code with the order if provided
            $order->discount_code = $data['discount_code'];
        }
        $order->save();

        // Calculate and log the commission for the affiliate
        $commission = $data['subtotal_price'] * $affiliate->commission_rate;
        $this->affiliateService->logCommission($affiliate, $commission);
    }


}
