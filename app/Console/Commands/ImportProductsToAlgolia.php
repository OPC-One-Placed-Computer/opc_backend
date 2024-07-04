<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;


class ImportProductsToAlgolia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'algolia:import-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products into Algolia';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Product::all()->searchable();
        $this->info('All products have been imported to Algolia.');
    }
}
