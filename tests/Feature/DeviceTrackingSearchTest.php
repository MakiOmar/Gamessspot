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

    /**
     * Public API must return delivered repairs when searching by phone.
     */
    public function test_api_phone_track_returns_delivered_repairs(): void
    {
        $phone = '+20122' . random_int(1000000, 9999999);

        $user = User::factory()->create([
            'phone' => $phone,
            'is_active' => true,
        ]);

        $deviceModel = DeviceModel::create([
            'name' => 'PS5 Slim Digital',
            'brand' => 'Sony',
            'is_active' => true,
        ]);

        $trackingCode = 'DR' . strtoupper(substr(md5(uniqid('api', true)), 0, 8));

        DeviceRepair::create([
            'user_id' => $user->id,
            'device_model_id' => $deviceModel->id,
            'device_serial_number' => '0000',
            'tracking_code' => $trackingCode,
            'status' => 'delivered',
            'submitted_at' => now(),
            'status_updated_at' => now(),
        ]);

        $response = $this->postJson('/api/device/track', [
            'phone_number' => $phone,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
            ])
            ->assertJsonPath('data.0.tracking_code', $trackingCode)
            ->assertJsonPath('data.0.status', 'delivered')
            ->assertJsonPath('data.0.phone', $phone);
    }

    /**
     * Public API returns 404 when the phone has no repairs.
     */
    public function test_api_phone_track_returns_not_found_for_unknown_phone(): void
    {
        $response = $this->postJson('/api/device/track', [
            'phone_number' => '+20129' . random_int(1000000, 9999999),
        ]);

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'count' => 0,
                'data' => [],
            ]);
    }

    /**
     * Public API requires a phone number.
     */
    public function test_api_phone_track_requires_phone_number(): void
    {
        $response = $this->postJson('/api/device/track', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }
}
