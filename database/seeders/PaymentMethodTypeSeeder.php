<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethodType;

class PaymentMethodTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Seeding Payment Method Types...');

        $paymentMethods = [
            // E-Wallets
            [
                'code' => 'gcash',
                'name' => 'GCash',
                'category' => 'e_wallet',
                'icon_name' => 'wallet-outline',
                'description' => 'GCash mobile wallet',
                'is_active' => true,
            ],
            [
                'code' => 'maya',
                'name' => 'Maya',
                'category' => 'e_wallet',
                'icon_name' => 'wallet-outline',
                'description' => 'Maya mobile wallet',
                'is_active' => true,
            ],
            [
                'code' => 'paypal',
                'name' => 'PayPal',
                'category' => 'e_wallet',
                'icon_name' => 'logo-paypal',
                'description' => 'PayPal account transfer',
                'is_active' => true,
            ],
            [
                'code' => 'mbank',
                'name' => 'Mari Bank',
                'category' => 'e_wallet',
                'icon_name' => 'wallet-outline',
                'description' => 'Mari Bank app',
                'is_active' => true,
            ],

            // Bank Transfers
            [
                'code' => 'bdo',
                'name' => 'BDO',
                'category' => 'bank_transfer',
                'icon_name' => 'business-outline',
                'description' => 'BDO Bank Transfer',
                'is_active' => true,
            ],
            [
                'code' => 'bpi',
                'name' => 'BPI',
                'category' => 'bank_transfer',
                'icon_name' => 'business-outline',
                'description' => 'BPI Bank Transfer',
                'is_active' => true,
            ],
            [
                'code' => 'unionbank',
                'name' => 'Union Bank',
                'category' => 'bank_transfer',
                'icon_name' => 'business-outline',
                'description' => 'Union Bank Transfer',
                'is_active' => true,
            ],
            [
                'code' => 'pnb',
                'name' => 'PNB',
                'category' => 'bank_transfer',
                'icon_name' => 'business-outline',
                'description' => 'PNB Bank Transfer',
                'is_active' => true,
            ],
            [
                'code' => 'ewb',
                'name' => 'East West Bank',
                'category' => 'bank_transfer',
                'icon_name' => 'business-outline',
                'description' => 'East West Bank Transfer',
                'is_active' => true,
            ],

            // Credit Card
            [
                'code' => 'creditcard',
                'name' => 'Credit Card',
                'category' => 'credit_card',
                'icon_name' => 'card-outline',
                'description' => 'Credit Card Payment',
                'is_active' => true,
            ],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethodType::firstOrCreate(
                ['code' => $method['code']],
                $method
            );
            $this->command->comment("  ✓ {$method['name']} ({$method['code']}) - {$method['category']}");
        }

        $this->command->info('✅ Payment method types seeded successfully!');
        $this->command->line('');
        $this->command->info('Summary:');
        $this->command->line('  • E-Wallets: 4 methods');
        $this->command->line('  • Bank Transfers: 5 methods');
        $this->command->line('  • Credit Card: 1 method');
        $this->command->line('  Total: 10 payment method types');
    }
}
