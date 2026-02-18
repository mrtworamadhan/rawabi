<?php

namespace App\Filament\Resources\Jamaahs\Pages;

use App\Filament\Resources\Jamaahs\JamaahResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateJamaah extends CreateRecord
{
    protected static string $resource = JamaahResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('name')) {
            $this->form->fill([
                'name' => request()->query('name'),
                'phone' => request()->query('phone'), 
                // Jika di tabel jamaah ada kolom agent_id, isi juga:
                // 'agent_id' => request()->query('agent_id'), 
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $userData = [
                'name' => $data['name'], 
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ];

            $user = User::create($userData);

            $user->assignRole('jamaah'); 

            $data['user_id'] = $user->id;

            unset($data['email']);
            unset($data['password']);

            return $data;
        });
    }
}
