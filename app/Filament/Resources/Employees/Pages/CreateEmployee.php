<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Tambah Karyawan';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userData = [
                'name' => $data['full_name'], 
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ];

            $user = User::create($userData);

            if (isset($data['roles']) && !empty($data['roles'])) {
                $user->assignRole($data['roles']);
            }

            unset($data['roles']);

            $data['user_id'] = $user->id;

            unset($data['email']);
            unset($data['password']);

            return $data;
        });
    }
}
