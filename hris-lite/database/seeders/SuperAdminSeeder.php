<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure the Super Admin role exists before assigning it.
        Role::findOrCreate('Super Admin', 'web');

        $user = User::updateOrCreate(
            ['email' => 'johnestobo23@gmail.com'],
            [
                'name' => 'John Philip Toledo Estobo',
                'password' => '12345678', // hashed via the model's 'password' cast
                'email_verified_at' => Carbon::now(),
            ],
        );

        $user->syncRoles(['Super Admin']);
    }
}
