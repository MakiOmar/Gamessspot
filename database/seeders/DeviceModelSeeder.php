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
			// PlayStation 4 Models
			[
				'brand'       => 'Sony',
				'name'        => 'PS4 Fat',
				'description' => 'PlayStation 4 Fat',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS4 Slim',
				'description' => 'PlayStation 4 Slim',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS4 Pro',
				'description' => 'PlayStation 4 Pro',
				'is_active'   => true,
			],

			// PlayStation 5 Models
			[
				'brand'       => 'Sony',
				'name'        => 'PS5 Fat Digital',
				'description' => 'PlayStation 5 Fat Digital Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS5 Fat CD',
				'description' => 'PlayStation 5 Fat CD Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS5 Slim Digital',
				'description' => 'PlayStation 5 Slim Digital Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS5 Slim CD',
				'description' => 'PlayStation 5 Slim CD Edition',
				'is_active'   => true,
			],
			[
				'brand'       => 'Sony',
				'name'        => 'PS5 Pro',
				'description' => 'PlayStation 5 Pro',
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
