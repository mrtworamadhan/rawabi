<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Buat Akun Login')
                            ->description('Data ini akan otomatis membuat User Login untuk karyawan.')
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email Login')
                                    ->email()
                                    ->required()
                                    ->unique(table: 'users', column: 'email', ignoreRecord: true),

                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->helperText(fn (string $context) => $context === 'edit' ? 'Kosongkan jika tidak ingin mengganti password.' : ''),

                                Select::make('roles')
                                    ->label('Role / Hak Akses')
                                    ->options(Role::all()->pluck('name', 'id'))
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->visible(fn (string $context): bool => $context === 'create'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (string $context): bool => $context === 'create'),

                Group::make()
                    ->schema([
                        Section::make('Akun & Jabatan')
                            ->schema([

                                TextInput::make('nik_karyawan')
                                    ->label('NIK / NIP')
                                    ->unique(ignoreRecord: true)
                                    ->required(),

                                TextInput::make('position')
                                    ->label('Jabatan')
                                    ->placeholder('Ex: Head of Marketing')
                                    ->required(),

                                Select::make('department_id')
                                    ->label('Departemen')
                                    ->relationship('departmentRel', 'name') 
                                    ->createOptionForm([ 
                                        TextInput::make('name')->required(),
                                        TextInput::make('code')->required(),
                                    ])
                                    ->required(),

                                Select::make('status')
                                    ->options([
                                        'probation' => 'Masa Percobaan (Probation)',
                                        'contract' => 'Kontrak',
                                        'permanent' => 'Tetap',
                                        'resign' => 'Non-Aktif / Resign',
                                    ])
                                    ->default('probation')
                                    ->required(),

                                DatePicker::make('join_date')
                                    ->label('Tanggal Bergabung')
                                    ->default(now())
                                    ->required(),
                            ])->columns(2),
                        
                        Section::make('Data Payroll & Legal')
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Nama Bank'),
                                TextInput::make('bank_account_number')
                                    ->label('No. Rekening'),
                                TextInput::make('npwp')
                                    ->label('NPWP'),
                                TextInput::make('bpjs_kesehatan')
                                    ->label('No. BPJS Kesehatan'),
                            ])->columns(2),
                    ])->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Identitas Pribadi')
                            ->schema([
                                TextInput::make('full_name')
                                    ->label('Nama Lengkap (Sesuai KTP)')
                                    ->required(),
                                
                                TextInput::make('nickname')
                                    ->label('Nama Panggilan'),

                                Select::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->options([
                                        'pria' => 'Pria',
                                        'wanita' => 'Wanita',
                                    ])
                                    ->required(),

                                TextInput::make('place_of_birth')
                                    ->label('Tempat Lahir'),

                                DatePicker::make('date_of_birth')
                                    ->label('Tanggal Lahir'),

                                TextInput::make('phone_number')
                                    ->label('No. HP / WA')
                                    ->tel()
                                    ->required(),

                                Textarea::make('address_ktp')
                                    ->label('Alamat KTP')
                                    ->rows(3),
                                
                                Textarea::make('address_domicile')
                                    ->label('Alamat Domisili')
                                    ->rows(3),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }
}
