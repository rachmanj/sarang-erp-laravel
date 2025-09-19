<?php

namespace Database\Seeders;

use App\Services\ControlAccountService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ControlAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $controlAccountService = app(ControlAccountService::class);
        
        // Set up business partner control accounts (AR/AP)
        $bpResults = $controlAccountService->setupBusinessPartnerControlAccounts();
        $this->command->info("Created {$bpResults['ar_created']} AR control accounts and {$bpResults['ap_created']} AP control accounts");
        $this->command->info("Created {$bpResults['subsidiaries_created']} business partner subsidiary accounts");
        
        // Set up inventory control account
        $invResults = $controlAccountService->setupInventoryControlAccount();
        $this->command->info("Created {$invResults['inventory_created']} inventory control accounts");
        $this->command->info("Created {$invResults['subsidiaries_created']} inventory subsidiary accounts");
        
        $this->command->info('Control account setup completed successfully.');
    }
}
