<?php namespace Vebto\Commands;

use File;
use Illuminate\Console\Command;
use League\Flysystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Vebto\Appearance\CssThemeGenerator;

class SeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vebto:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute all vebto package seeders.';

    /**
     * @var CssThemeGenerator;
     */
    protected $generator;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $paths = File::files(__DIR__.'/../Database/Seeds');

        foreach ($paths as $path) {
             Model::unguarded(function () use ($path) {
                 $namespace = 'Vebto\Database\Seeds\\'.basename($path, '.php');
                 $this->getSeeder($namespace)->__invoke();
             });
        }

        $this->info('Seeded database successfully.');
    }

    /**
     * Get a seeder instance from the container.
     *
     * @param string $namespace
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder($namespace)
    {
        $class = $this->laravel->make($namespace);

        return $class->setContainer($this->laravel)->setCommand($this);
    }
}
