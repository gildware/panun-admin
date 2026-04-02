<?php

namespace Modules\PaymentModule\Traits;

use Modules\BidModule\Entities\PostBid;
use Modules\BidModule\Http\Controllers\APi\V1\Customer\PostBidController;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAddress;

trait PaymentHelperTrait
{
    public function find_total_Booking_amount($customer_user_id, $post_id, $provider_id): float
    {
        if (! isset($post_id)) {
            return (float) cart_total($customer_user_id) + getServiceFee($customer_user_id);
        }

        $post_bid = PostBid::with(['post.service.category', 'post.service.subCategory'])
                ->whereHas('post', fn ($query) => $query->where('is_booked', '!=', 1))
                ->where('post_id', $post_id)
                ->where('provider_id', $provider_id)
                ->where('status', 'pending')
                ->first();

        if (! $post_bid) {
            return 0.0;
        }

        $basis = (float) $post_bid->offered_price;
        $service = $post_bid->relationLoaded('post')
            ? ($post_bid->post?->service ?? null)
            : null;
        if (! $service && $post_bid->post_id) {
            $post_bid->load(['post.service.category', 'post.service.subCategory']);
            $service = $post_bid->post?->service;
        }

        $extra = compute_additional_charges_for_service_basis($basis, $service)['total'];

        return round($basis + $extra, 2);
    }

    public function find_customer_info($customer_user_id, $service_address_id): array
    {
        $user = User::find($customer_user_id);
        $address = UserAddress::find($service_address_id);

        $customer_info = [];
        if (isset($user)) {
            $customer_info = [
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'phone' => $user->phone,
                'email' => $user->email,
            ];
        }  elseif (isset($address)) {
            $customer_info = [
                'full_name' => $address?->contact_person_name,
                'phone' => $address?->contact_person_number,
                'email' => null,
            ];
        }

        return $customer_info;
    }

    public function payment_success($request, $customer_user_id, $tran_id): array
    {
        if (is_null($request['post_id'])) {
            $is_guest = !User::where('id', $customer_user_id)->exists();
            $response = $this->place_booking_request($customer_user_id, $request, $tran_id, $is_guest);
        } else {
            //for bidding
            $post_bid = PostBid::with(['post.service.category', 'post.service.subCategory'])
                ->where('post_id', $request['post_id'])
                ->where('provider_id', $request['provider_id'])
                ->first();

            $data = [
                'payment_method' => $request['payment_method'],
                'zone_id' => $request['zone_id'],
                'service_tax' => $post_bid?->post?->service ? effective_service_tax_percentage($post_bid->post->service) : null,
                'provider_id' => $post_bid->provider_id,
                'price' => $post_bid->offered_price,
                'service_schedule' => !is_null($request['booking_schedule']) ? $request['booking_schedule'] : $post_bid->post->booking_schedule,
                'service_id' => $post_bid->post->service_id,
                'category_id' => $post_bid->post->category_id,
                'sub_category_id' => $post_bid->post->category_id,
                'service_address_id' => !is_null($request['service_address_id']) ? $request['service_address_id'] : $post_bid->post->service_address_id,
            ];

            $response = $this->place_booking_request_for_bidding($request['access_token'], $request, $tran_id, $data);
            if ($response['flag'] == 'success') {
                PostBidController::acceptPostBidOffer($post_bid->id, $response['booking_id']);
            }
        }

        return $response;
    }

}
