<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ContainerCommandLoader as BaseContainerCommandLoader;

class ContainerCommandLoader extends BaseContainerCommandLoader
{
    /**
     * Resolve a command from the container.
     *
     * @param  string  $name
     * @return \Symfony\Component\Console\Command\Command
     *
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    public function get(string $name): \Symfony\Component\Console\Command\Command
    {
        if (! $this->has($name)) {
            throw new \Symfony\Component\Console\Exception\CommandNotFoundException(sprintf('Command "%s" does not exist.', $name));
        }

        $command = $this->container->get($this->commandMap[$name]);

        // Ensure Laravel instance is set on Laravel commands
        if ($command instanceof Command && method_exists($command, 'setLaravel')) {
            $command->setLaravel($this->container);
        }

        return $command;
    }
}
