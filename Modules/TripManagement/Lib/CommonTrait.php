<?php

namespace Modules\TripManagement\Lib;

use Carbon\Carbon;
use Modules\TripManagement\Entities\FareBidding;
use Modules\TripManagement\Entities\TripRequestFee;
use Modules\TripManagement\Entities\TripRequestTime;

trait CommonTrait
{
    use DiscountCalculationTrait, CouponCalculationTrait;

    public function calculateFinalFare($trip, $fare): array
    {
        $admin_trip_commission = (double)get_cache('trip_commission') ?? 0;

        // =======================
        // PARCEL
        // =======================
        if ($trip->type == 'parcel') {
            $vat_percent   = (double)get_cache('vat_percent') ?? 1;
            $actual_fare   = $trip->actual_fare / (1 + ($vat_percent / 100));
            $parcel_payment = $actual_fare;
            $vat           = round(($vat_percent * $parcel_payment) / 100, 2);

            // Ensure fee row exists
            $fee = \Modules\TripManagement\Entities\TripRequestFee::firstOrCreate(
                ['trip_request_id' => $trip->id],
                ['toll_amount' => 0]
            );

            $toll_amount = (float)($fee->toll_amount ?? 0);

            $fee->vat_tax          = $vat;
            $fee->admin_commission = (($parcel_payment * $admin_trip_commission) / 100) + $vat;
            $fee->save();

            $final_fare = $parcel_payment + $vat + $toll_amount;

            return [
                'actual_fare'      => round($actual_fare, 2),
                'final_fare'       => round($final_fare, 2),
                'waiting_fee'      => 0,
                'idle_fare'        => 0,
                'cancellation_fee' => 0,
                'delay_fee'        => 0,
                'vat'              => $vat,
                'actual_distance'  => $trip->estimated_distance,
            ];
        }

        // =======================
        // RIDE REQUEST
        // =======================
        $fee  = \Modules\TripManagement\Entities\TripRequestFee::firstOrCreate(
            ['trip_request_id' => $trip->id],
            ['toll_amount' => 0]
        );
        $time = \Modules\TripManagement\Entities\TripRequestTime::query()->firstWhere('trip_request_id', $trip->id);

        $bid_on_fare     = \Modules\TripManagement\Entities\FareBidding::where('trip_request_id', $trip->id)->where('is_ignored', 0)->first();
        $current_status  = $trip->current_status;
        $cancellation_fee = 0;
        $waiting_fee      = 0;
        $distance_in_km   = 0;
        $idle_fee = 0;
        $delay_fee = 0;

        $drivingMode = $trip?->vehicleCategory?->type === 'motor_bike' ? 'TWO_WHEELER' : 'DRIVE';
        $drop_coordinate = [
            $trip->coordinate->drop_coordinates->latitude,
            $trip->coordinate->drop_coordinates->longitude
        ];
        $destination_coordinate = [
            $trip->coordinate->destination_coordinates->latitude,
            $trip->coordinate->destination_coordinates->longitude
        ];
        $pickup_coordinate = [
            $trip->coordinate->pickup_coordinates->latitude,
            $trip->coordinate->pickup_coordinates->longitude
        ];
        $intermediate_coordinate = [];
        if ($trip->coordinate->is_reached_1) {
            if ($trip->coordinate->is_reached_2) {
                $intermediate_coordinate[1] = [
                    $trip->coordinate->int_coordinate_2->latitude,
                    $trip->coordinate->int_coordinate_2->longitude
                ];
            }
            $intermediate_coordinate[0] = [
                $trip->coordinate->int_coordinate_1->latitude,
                $trip->coordinate->int_coordinate_1->longitude
            ];
        }

        if ($current_status === 'cancelled') {
            $route = getRoutes($pickup_coordinate, $drop_coordinate, $intermediate_coordinate, [$drivingMode]);

            $distance_in_km = $route[0]['distance'];
            $distance_wise_fare_cancelled = $fare->base_fare_per_km * $distance_in_km;
            $actual_fare = $fare->base_fare + $distance_wise_fare_cancelled;

            if ($trip->fee->cancelled_by === 'customer') {
                $cancellation_percent = $fare->cancellation_fee_percent;
                $cancellation_fee = max((($cancellation_percent * $distance_wise_fare_cancelled) / 100), $fare->min_cancellation_fee);
            }
        } elseif ($current_status == 'completed') {
            $route = getRoutes(
                $pickup_coordinate,
                $drop_coordinate,
                $intermediate_coordinate,
                [$drivingMode]
            );

            $distance_in_km = $route[0]['distance'];
            $distance_wise_fare_completed = $fare->base_fare_per_km * $distance_in_km;
            $vat_percent  = (double)get_cache('vat_percent') ?? 1;
            $distanceFare = $trip->rise_request_count > 0
                ? $trip->actual_fare / (1 + ($vat_percent / 100))
                : $fare->base_fare + $distance_wise_fare_completed;

            $actual_fare = $bid_on_fare
                ? $bid_on_fare->bid_fare / (1 + ($vat_percent / 100))
                : $distanceFare;
        } else {
            $actual_fare = 0;
        }

        $trip_started = \Carbon\Carbon::parse($trip->tripStatus->ongoing ?? now());
        $trip_ended   = \Carbon\Carbon::parse($trip->tripStatus->$current_status ?? now());
        $actual_time  = $trip_started->diffInMinutes($trip_ended);

        // Idle time & fee
        $idle_fee_buffer = (double)get_cache('idle_fee') ?? 0;
        $idle_diff = ($trip->time->idle_time ?? 0) - $idle_fee_buffer;
        $idle_time = max($idle_diff, 0);
        $idle_fee  = $idle_time * ($fare->idle_fee_per_min ?? 0);

        // Delay time & fee
        $delay_fee_buffer = (double)get_cache('delay_fee') ?? 0;
        $delay_diff = $actual_time - (($trip->time->estimated_time ?? 0) + $delay_fee_buffer + ($trip->time->idle_time ?? 0));
        $delay_time = max($delay_diff, 0);
        $delay_fee  = $delay_time * ($fare->trip_delay_fee_per_min ?? 0);

        // ✅ FIX: initialize variable before using it (line below was missing earlier)
        $final_fare_without_tax = ($actual_fare + $waiting_fee + $idle_fee + $cancellation_fee + $delay_fee);  // ✅ [LINE FIXED]
        $vat_percent = (double)get_cache('vat_percent') ?? 1;
        $vat = ($final_fare_without_tax * $vat_percent) / 100;

        // ✅ Toll (from fee row)
        $toll_amount = (float)($fee->toll_amount ?? 0);

        $fee->vat_tax          = round($vat, 2);
        $fee->admin_commission = (($final_fare_without_tax * $admin_trip_commission) / 100) + $vat;
        $fee->cancellation_fee = round($cancellation_fee, 2);
        $fee->idle_fee         = round($idle_fee, 2);
        $fee->delay_fee        = round($delay_fee, 2);
        $fee->save();

        $time->actual_time = $actual_time;
        $time->idle_time   = $idle_time;
        $time->delay_time  = $delay_time;
        $time->save();

        // ✅ Include toll in final_fare
        $final_fare = $final_fare_without_tax + $vat + $toll_amount;

        return [
            'actual_fare'      => round($actual_fare, 2),
            'final_fare'       => round($final_fare, 2),
            'waiting_fee'      => $waiting_fee,
            'idle_fare'        => $idle_fee,
            'cancellation_fee' => $cancellation_fee,
            'delay_fee'        => $delay_fee,
            'vat'              => $vat,
            'actual_distance'  => $distance_in_km,
        ];
    }

    // --------------------------
    // ESTIMATED FARE (unchanged)
    // --------------------------
    public function estimatedFare($tripRequest, $routes, $zone_id, $tripFare = null, $area_id = null, $beforeCreate = false): mixed
    {
        if ($tripRequest['type'] == 'parcel') {
            abort_if(boolean: empty($tripFare), code: 403, message: translate('invalid_or_missing_information'));
            abort_if(boolean: empty($tripFare->fares), code: 403, message: translate('no_fares_found'));

            $distance_wise_fare = $tripFare->fares[0]->fare_per_km * $routes[0]['distance'];
            $est_fare = $tripFare->fares[0]->base_fare + $distance_wise_fare;
            $returnFee = ($est_fare * $tripFare->fares[0]->return_fee) / 100;
            $cancellationFee = ($est_fare * $tripFare->fares[0]->cancellation_fee) / 100;
            $user = auth('api')->user();
            $discount = $this->getEstimatedDiscount(user: $user, zoneId: $zone_id, tripType: $tripRequest['type'], vehicleCategoryId: null, estimatedAmount: $est_fare, beforeCreate: $beforeCreate);
            $vat_percent = (double)get_cache('vat_percent') ?? 1;
            $discountEstFare = $est_fare - ($discount ? $discount['discount_amount'] : 0);
            $coupon = $this->getEstimatedCouponDiscount(user: $user, zoneId: $zone_id, tripType: $tripRequest['type'], vehicleCategoryId: null, estimatedAmount: $discountEstFare);
            $discountFareVat = ($discountEstFare * $vat_percent) / 100;
            $discountEstFare += $discountFareVat;
            $vat = ($est_fare * $vat_percent) / 100;
            $est_fare += $vat;
            $points = (int)getSession('currency_decimal_point') ?? 0;
            $estimated_fare = [
                'id' => $tripFare->id,
                'zone_id' => $zone_id,
                'area_id' => $area_id,
                'base_fare' => $tripFare->base_fare,
                'base_fare_per_km' => $tripFare->base_fare_per_km,
                'fare' => $tripFare->fares,
                'estimated_distance' => (double)$routes[0]['distance'],
                'estimated_duration' => $routes[0]['duration'],
                'estimated_fare' => round($est_fare, $points),
                'discount_fare' => round($discountEstFare, $points),
                'discount_amount' => round(($discount ? $discount['discount_amount'] : 0), $points),
                'coupon_applicable' => $coupon,
                'request type' => $tripRequest['type'],
                'encoded_polyline' => $routes[0]['encoded_polyline'],
                'return_fee' => $returnFee,
                'cancellation_fee' => $cancellationFee
            ];
        } else {
            $estimated_fare = $tripFare->map(function ($trip) use ($routes, $tripRequest, $area_id, $beforeCreate) {
                foreach ($routes as $route) {
                    if ($route['drive_mode'] === 'DRIVE') {
                        $distance = $route['distance'];
                        $drive_fare = $trip->base_fare_per_km * $distance;
                        $drive_est_distance = (double)$routes[0]['distance'];
                        $drive_est_duration = $route['duration'];
                        $drive_polyline = $route['encoded_polyline'];
                    } elseif ($route['drive_mode'] === 'TWO_WHEELER') {
                        $distance = $route['distance'];
                        $bike_fare = $trip->base_fare_per_km * $distance;
                        $bike_est_distance = (double)$routes[0]['distance'];
                        $bike_est_duration = $route['duration'];
                        $bike_polyline = $route['encoded_polyline'];
                    }
                }

                $points = (int)getSession('currency_decimal_point') ?? 0;
                $est_fare = $trip->vehicleCategory->type === 'car' ? round(($trip->base_fare + $drive_fare), $points) : round(($trip->base_fare + $bike_fare), $points);
                $user = auth('api')->user();
                $discount = $this->getEstimatedDiscount(user: $user, zoneId: $trip->zone_id, tripType: $tripRequest['type'], vehicleCategoryId: $trip->vehicleCategory->id, estimatedAmount: $est_fare, beforeCreate: $beforeCreate);
                $vat_percent = (double)get_cache('vat_percent') ?? 1;
                $discountEstFare = $est_fare - ($discount ? $discount['discount_amount'] : 0);
                $coupon = $this->getEstimatedCouponDiscount(user: $user, zoneId: $trip->zone_id, tripType: $tripRequest['type'], vehicleCategoryId: $trip->vehicleCategory->id, estimatedAmount: $discountEstFare);
                $discountFareVat = ($discountEstFare * $vat_percent) / 100;
                $discountEstFare += $discountFareVat;
                $vat = ($est_fare * $vat_percent) / 100;
                $est_fare += $vat;

                return [
                    "id" => $trip->id,
                    "zone_id" => $trip->zone_id,
                    'area_id' => $area_id,
                    "vehicle_category_id" => $trip->vehicle_category_id,
                    'base_fare' => $trip->base_fare,
                    'base_fare_per_km' => $trip->base_fare_per_km,
                    'fare' => $trip->VehicleCategory->type === 'car' ? round($drive_fare, 2) : round($bike_fare, 2),
                    'estimated_distance' => $trip->VehicleCategory->type === 'car' ? $drive_est_distance : $bike_est_distance,
                    'estimated_duration' => $trip->VehicleCategory->type === 'car' ? $drive_est_duration : $bike_est_duration,
                    'vehicle_category_type' => $trip->VehicleCategory->type === 'car' ? 'Car' : 'Motorbike',
                    'estimated_fare' => round($est_fare, $points),
                    'discount_fare' => round($discountEstFare, $points),
                    'discount_amount' => round(($discount ? $discount['discount_amount'] : 0), $points),
                    'coupon_applicable' => $coupon,
                    'request_type' => $tripRequest['type'],
                    'encoded_polyline' => $trip->VehicleCategory->type === 'car' ? $drive_polyline : $bike_polyline,
                    'return_fee' => 0
                ];
            });
        }

        return $estimated_fare;
    }
}
