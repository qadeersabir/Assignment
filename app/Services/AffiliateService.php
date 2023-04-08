<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Psy\Util\Str;

class AffiliateService
{
    protected $apiService;
    public function __construct(ApiService $apiService) {
        $this->apiService = $apiService;
    }
    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method


        // Check if the email is already in use as a merchant's email
        if ($merchant->user->email === $email) {
            throw new AffiliateCreateException('Email is already in use as a merchant\'s email');
        }

        // Check if the email is already in use as an affiliate's email
        if (Affiliate::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists()) {
            throw new AffiliateCreateException('Email is already in use as an affiliate\'s email');
        }

        // Create a new user for the affiliate
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_AFFILIATE,
            'password' => bcrypt('password')
        ]);

        // Create a new affiliate
        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $this->apiService->createDiscountCode($merchant)['code'],
        ]);



        // Send an email to the new affiliate
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
