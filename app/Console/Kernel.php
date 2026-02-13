<?php

namespace App\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Foundation\Console\Kernel as BaseKernel;
use App\Console\ContainerCommandLoader;

class Kernel extends BaseKernel
{
    /**
     * The Artisan commands provided by the application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\BackfillDeliveryOrderInventoryTransactions::class,
        \App\Console\Commands\BackfillSalesOrderLineDeliveredQty::class,
        \App\Console\Commands\BackfillSalesInvoiceItemCodes::class,
        \App\Console\Commands\FixSalesOrderApproval::class,
        \App\Console\Commands\EnsureOfficerRole::class,
    ];
    
    /**
     * Get the Artisan application instance.
     *
     * @return \Illuminate\Console\Application
     */
    protected function getArtisan()
    {
        if (is_null($this->artisan)) {
            // Ensure commands are discovered before resolving
            if (!$this->commandsLoaded) {
                $this->discoverCommands();
            }
            
            $this->artisan = (new Artisan($this->app, $this->events, $this->app->version()))
                ->resolveCommands($this->commands);

            // Use our custom ContainerCommandLoader that sets Laravel instance
            $reflection = new \ReflectionClass($this->artisan);
            $property = $reflection->getProperty('commandMap');
            $property->setAccessible(true);
            $commandMap = $property->getValue($this->artisan);
            
            $this->artisan->setCommandLoader(
                new ContainerCommandLoader($this->app, $commandMap)
            );

            if ($this->symfonyDispatcher instanceof \Symfony\Component\EventDispatcher\EventDispatcher) {
                $this->artisan->setDispatcher($this->symfonyDispatcher);
                $this->artisan->setSignalsToDispatchEvent();
            }
        }

        return $this->artisan;
    }
}
