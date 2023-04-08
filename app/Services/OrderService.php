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
        $order = Order::where('external_order_id', $data['order_id'])->first();

        if ($order) {
            return;
        }

        $merchant = Merchant::where('domain', $data['merchant_domain'])->firstOrFail();

        $affiliate = Affiliate::where('merchant_id', $merchant->id)
            ->where('discount_code', $data['discount_code'])
            ->first();

        if (!$affiliate) {
            $affiliate = new Affiliate([
                'merchant_id' => $merchant->id,
                'discount_code' => $data['discount_code'],
                'commission_rate' => 0.1
            ]);
            $affiliate->save();
        }

        $customer = User::where('email', $data['customer_email'])->first();

        if (!$customer) {
            $customer = new User([
                'name' => $data['customer_name'],
                'email' => $data['customer_email'],
                'type' => User::TYPE_AFFILIATE,
            ]);
            $customer->save();
        }

        $order = new Order([
            'subtotal' => $data['subtotal_price'],
            'affiliate_id' => $affiliate->id,
            'merchant_id' => $merchant->id,
            'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
            'external_order_id' => $data['order_id']
        ]);
        $order->save();

        $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1);
    }


}
