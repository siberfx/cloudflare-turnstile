<?php

declare(strict_types=1);

namespace Siberfx\Turnstile\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'turnstile:publish')]
class PublishCommand extends Command
{
    protected $signature = 'turnstile:publish
                            {--force : Overwrite any existing files}';

    protected $description = 'Publish the Turnstile config, views and translations';

    public function handle(): int
    {
        return $this->call('vendor:publish', [
            '--tag' => 'turnstile',
            '--force' => (bool) $this->option('force'),
        ]);
    }
}
