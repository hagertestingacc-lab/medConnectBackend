<?php
namespace App\Console\Commands;

use App\Models\ProductPart\Product;
use Illuminate\Console\Command;

class ArchiveOutOfStockProducts extends Command
{
protected $signature = 'products:archive-out-of-stock';
protected $description = 'Archive products that are out of stock and have no restock date';

public function handle(): int
{
$this->info('Checking for out-of-stock products...');

$count = Product::archiveOutOfStockProducts();

$this->info("Archived {$count} product(s).");

return self::SUCCESS;
}
}