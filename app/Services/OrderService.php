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
        $orderExists = Order::where('external_order_id', $data['order_id'])->exists();
        if ($orderExists) {
            return; // Ignore duplicate orders
        }

        $merchant = Merchant::where('domain', $data['merchant_domain'])->first();
        if (!$merchant) {
            return; // Ignore orders from unknown merchants
        }

        $customerEmail = $data['customer_email'];
        $customerName = $data['customer_name'];

        // Check if the customer email is associated with an existing affiliate
        $affiliate = Affiliate::where('merchant_id', $merchant->id)
            ->where('customer_email', $customerEmail)
            ->first();

        if (!$affiliate) {
            // If there is no existing affiliate, create a new one
            $discountCode = $data['discount_code'] ?? $this->generateDiscountCode();
            $affiliate = Affiliate::create([
                'user_id' => $merchant->id,
                'merchant_id' => $merchant->id,
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'discount_code' => $discountCode,
                'commission_rate' => 0.1,
            ]);

            // Register the new affiliate with the affiliate service
            $this->affiliateService->register($merchant, $customerEmail, $customerName, 0.1);
        }

        // Log the order and commission information
        $subtotal = $data['subtotal_price'];
        $commissionRate = $affiliate->commission_rate;
        $commissionOwed = $subtotal * $commissionRate;

        Order::create([
            'external_order_id' => $data['order_id'],
            'subtotal' => $subtotal,
            'commission_owed' => $commissionOwed,
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
        ]);
    }

    private function generateDiscountCode(): string
    {
        // Generate a random discount code
        return substr(md5(rand()), 0, 8);
    }
}
