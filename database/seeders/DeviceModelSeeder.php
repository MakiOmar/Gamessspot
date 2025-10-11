<?php

namespace Database\Seeders;

use App\Models\DeviceModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceModelSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$deviceModels = [
			// PlayStation Consoles
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation 5',
				'description' => 'PlayStation 5 Standard Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation 5 Digital Edition',
				'description' => 'PlayStation 5 Digital Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation 4 Pro',
				'description' => 'PlayStation 4 Pro',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation 4 Slim',
				'description' => 'PlayStation 4 Slim',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation 4',
				'description' => 'PlayStation 4 Standard',
				'is_active'   => true,
			],

			// Xbox Consoles
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox Series X',
				'description' => 'Xbox Series X',
				'is_active'   => true,
			],
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox Series S',
				'description' => 'Xbox Series S',
				'is_active'   => true,
			],
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox One X',
				'description' => 'Xbox One X',
				'is_active'   => true,
			],
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox One S',
				'description' => 'Xbox One S',
				'is_active'   => true,
			],
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox One',
				'description' => 'Xbox One Standard',
				'is_active'   => true,
			],

			// Nintendo Consoles
			[
				'brand'       => 'Nintendo',
				'name'        => 'Switch',
				'description' => 'Nintendo Switch',
				'is_active'   => true,
			],
			[
				'brand'       => 'Nintendo',
				'name'        => 'Switch Lite',
				'description' => 'Nintendo Switch Lite',
				'is_active'   => true,
			],
			[
				'brand'       => 'Nintendo',
				'name'        => 'Switch OLED',
				'description' => 'Nintendo Switch OLED Model',
				'is_active'   => true,
			],

			// Controllers
			[
				'brand'       => 'Sony',
				'name'        => 'DualSense Controller',
				'description' => 'PlayStation 5 DualSense Wireless Controller',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'DualShock 4 Controller',
				'description' => 'PlayStation 4 DualShock 4 Wireless Controller',
				'is_active'   => true,
			],
			[
				'brand'       => 'Microsoft',
				'name'        => 'Xbox Wireless Controller',
				'description' => 'Xbox Series X|S Wireless Controller',
				'is_active'   => true,
			],

			// VR Headsets
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation VR2',
				'description' => 'PlayStation VR2 Headset',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PlayStation VR',
				'description' => 'PlayStation VR Headset (PS4)',
				'is_active'   => true,
			],
		];

		foreach ( $deviceModels as $model ) {
			DeviceModel::firstOrCreate(
				[
					'brand' => $model['brand'],
					'name'  => $model['name'],
				],
				[
					'description' => $model['description'],
					'is_active'   => $model['is_active'],
				]
			);
		}
	}
}
