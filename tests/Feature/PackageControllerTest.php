<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Package;
use App\Models\Addon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PackageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor;
    protected $addons;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a vendor user
        $this->vendor = User::factory()->create([
            'role' => 'vendor',
        ]);

        // Create some addons for testing
        $this->addons = Addon::factory()->count(3)->create();
    }

    /**
     * Test creating a package with services and addons
     */
    public function test_create_package_with_services_and_addons()
    {
        $payload = [
            'title' => 'Premium Cleaning Package',
            'description' => 'Complete house cleaning service',
            'price' => 150.50,
            'services' => [
                ['title' => 'Deep Cleaning'],
                ['title' => 'Window Cleaning'],
                ['title' => 'Carpet Shampooing'],
            ],
            'addons' => [
                [
                    'addon_id' => $this->addons[0]->id,
                    'price' => 25.00,
                ],
                [
                    'addon_id' => $this->addons[1]->id,
                    'price' => 15.50,
                ],
            ],
        ];

        $response = $this->actingAs($this->vendor)
            ->postJson('/api/vendor/package/create', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'title',
                'description',
                'price',
                'vendor_id',
                'status',
                'services' => [
                    '*' => ['id', 'title', 'description', 'price', 'status'],
                ],
                'addons' => [
                    '*' => ['id', 'name', 'pivot'],
                ],
            ],
        ]);

        // Verify package was created
        $this->assertDatabaseHas('packages', [
            'title' => 'Premium Cleaning Package',
            'vendor_id' => $this->vendor->id,
        ]);

        // Verify services were created
        $this->assertDatabaseHas('services', [
            'title' => 'Deep Cleaning',
            'package_id' => $response['data']['id'],
        ]);

        $this->assertDatabaseHas('services', [
            'title' => 'Window Cleaning',
            'package_id' => $response['data']['id'],
        ]);

        $this->assertDatabaseHas('services', [
            'title' => 'Carpet Shampooing',
            'package_id' => $response['data']['id'],
        ]);

        // Verify addons were attached
        $package = Package::find($response['data']['id'], ['*']);
        $this->assertCount(2, $package->addons);

        echo "\n✅ Test: Create Package with Services - PASSED\n";
    }

    /**
     * Test updating a package with new services and addons
     */
    public function test_update_package_with_services_and_addons()
    {
        // Create initial package
        $package = Package::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $updatePayload = [
            'title' => 'Updated Premium Package',
            'description' => 'Updated description',
            'price' => 200.00,
            'services' => [
                ['title' => 'Full House Cleaning'],
                ['title' => 'Bathroom Sanitizing'],
            ],
            'addons' => [
                [
                    'addon_id' => $this->addons[2]->id,
                    'price' => 30.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->vendor)
            ->putJson("/api/vendor/package/{$package->id}", $updatePayload);

        $response->assertStatus(200);

        // Verify package was updated
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'title' => 'Updated Premium Package',
            'price' => 200.00,
        ]);

        // Verify old services are deleted
        $this->assertDatabaseMissing('services', [
            'package_id' => $package->id,
            'title' => 'Old Service',
        ]);

        // Verify new services exist
        $this->assertDatabaseHas('services', [
            'package_id' => $package->id,
            'title' => 'Full House Cleaning',
        ]);

        $this->assertDatabaseHas('services', [
            'package_id' => $package->id,
            'title' => 'Bathroom Sanitizing',
        ]);

        echo "\n✅ Test: Update Package with Services - PASSED\n";
    }

    /**
     * Test addon price is correctly stored
     */
    public function test_addon_price_stored_correctly()
    {
        $payload = [
            'title' => 'Test Package',
            'description' => 'Test package for addon pricing',
            'price' => 100.00,
            'services' => [
                ['title' => 'Service 1'],
            ],
            'addons' => [
                [
                    'addon_id' => $this->addons[0]->id,
                    'price' => 45.75,
                ],
                [
                    'addon_id' => $this->addons[1]->id,
                    'price' => 32.25,
                ],
            ],
        ];

        $response = $this->actingAs($this->vendor)
            ->postJson('/api/vendor/package/create', $payload);

        $response->assertStatus(201);

        $package = Package::find($response['data']['id'], ['*']);

        // Verify addon prices
        $addon1 = $package->addons()->where('addon_id', $this->addons[0]->id)->first();
        $this->assertEquals(45.75, $addon1->pivot->price);

        $addon2 = $package->addons()->where('addon_id', $this->addons[1]->id)->first();
        $this->assertEquals(32.25, $addon2->pivot->price);

        echo "\n✅ Test: Addon Price Storage - PASSED\n";
    }

    /**
     * Test validation errors for required fields
     */
    public function test_validation_error_for_missing_services()
    {
        $payload = [
            'title' => 'Invalid Package',
            'description' => 'Missing services',
            'price' => 100.00,
            // Missing 'services' array
        ];

        $response = $this->actingAs($this->vendor)
            ->postJson('/api/vendor/package/create', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('services');

        echo "\n✅ Test: Validation for Missing Services - PASSED\n";
    }

    /**
     * Test validation error for empty services array
     */
    public function test_validation_error_for_empty_services()
    {
        $payload = [
            'title' => 'Invalid Package',
            'description' => 'Empty services',
            'price' => 100.00,
            'services' => [], // Empty array
            'addons' => [],
        ];

        $response = $this->actingAs($this->vendor)
            ->postJson('/api/vendor/package/create', $payload);

        $response->assertStatus(422);

        echo "\n✅ Test: Validation for Empty Services - PASSED\n";
    }

    /**
     * Test retrieving vendor packages
     */
    public function test_get_vendor_packages()
    {
        // Create packages
        Package::factory()->count(2)->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/api/vendor/packages');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'packages' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'price',
                    'vendor_id',
                    'services',
                    'addons',
                ],
            ],
        ]);

        echo "\n✅ Test: Get Vendor Packages - PASSED\n";
    }

    /**
     * Test vendor authorization
     */
    public function test_non_vendor_cannot_create_package()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $payload = [
            'title' => 'Unauthorized Package',
            'description' => 'Should fail',
            'price' => 100.00,
            'services' => [
                ['title' => 'Service'],
            ],
        ];

        $response = $this->actingAs($customer)
            ->postJson('/api/vendor/package/create', $payload);

        // Should fail due to not being a vendor
        $response->assertStatus(403);

        echo "\n✅ Test: Non-Vendor Authorization - PASSED\n";
    }
}
