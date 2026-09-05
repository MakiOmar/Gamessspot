<?php

namespace Tests\Feature;

use App\Models\DeviceModel;
use App\Models\DeviceRepair;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeviceTrackingSearchTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Phone search on /device/track must include delivered repairs, not only active ones.
     */
    public function test_phone_search_returns_delivered_repairs(): void
    {
        $phone = '+20100' . random_int(1000000, 9999999);

        $user = User::factory()->create([
            'phone' => $phone,
            'is_active' => true,
        ]);

        $deviceModel = DeviceModel::create([
            'name' => 'PS5 Slim Digital',
            'brand' => 'Sony',
            'is_active' => true,
        ]);

        $trackingCode = 'DR' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

        DeviceRepair::create([
            'user_id' => $user->id,
            'device_model_id' => $deviceModel->id,
            'device_serial_number' => '8563',
            'tracking_code' => $trackingCode,
            'status' => 'delivered',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $response = $this->from(route('device.tracking'))
            ->post(route('device.search'), [
                'phone_number' => $phone,
            ]);

        $response->assertOk();
        $response->assertViewIs('public.device-tracking');
        $response->assertViewHas('deviceRepairs', function ($deviceRepairs) use ($trackingCode) {
            return $deviceRepairs->contains(function ($repair) use ($trackingCode) {
                return $repair->tracking_code === $trackingCode
                    && $repair->status === 'delivered';
            });
        });
        $response->assertSee($trackingCode);
        $response->assertSee('Delivered');
    }

    /**
     * Phone search should still return non-delivered repairs alongside delivered ones.
     */
    public function test_phone_search_returns_all_statuses_for_the_same_phone(): void
    {
        $phone = '+20111' . random_int(1000000, 9999999);

        $user = User::factory()->create([
            'phone' => $phone,
            'is_active' => true,
        ]);

        $deviceModel = DeviceModel::create([
            'name' => 'PS4 Slim',
            'brand' => 'Sony',
            'is_active' => true,
        ]);

        $receivedCode = 'DR' . strtoupper(substr(md5(uniqid('received', true)), 0, 8));
        $deliveredCode = 'DR' . strtoupper(substr(md5(uniqid('delivered', true)), 0, 8));

        DeviceRepair::create([
            'user_id' => $user->id,
            'device_model_id' => $deviceModel->id,
            'device_serial_number' => '1111',
            'tracking_code' => $receivedCode,
            'status' => 'received',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        DeviceRepair::create([
            'user_id' => $user->id,
            'device_model_id' => $deviceModel->id,
            'device_serial_number' => '2222',
            'tracking_code' => $deliveredCode,
            'status' => 'delivered',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $response = $this->from(route('device.tracking'))
            ->post(route('device.search'), [
                'phone_number' => $phone,
            ]);

        $response->assertOk();
        $response->assertSee($receivedCode);
        $response->assertSee($deliveredCode);
    }
}
