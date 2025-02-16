<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from CSV file into database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filepath = $this->argument('filepath');

        if (!file_exists($filepath)) {
            $this->error("File doesn't exists: $filepath");
            return;
        }

        $handle = fopen($filepath, "r");
        fgetcsv($handle);

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($handle)) !== FALSE) {
                [$supplier_name, $days_valid, $priority, $part_number, $part_desc, $quantity, $price, $condition, $category] = $data;

                if (empty($supplier_name) || !is_numeric($days_valid) || !is_numeric($priority)) {
                    $this->warn("Skipping row with invalid data: " . implode(', ', $data));
                    continue;
                }   

                $days_valid = (int) $days_valid;
                $priority = (int) $priority;
                $quantity = (int) $quantity;
                $price = (float) $price;

                $supplier = Supplier::firstOrCreate(
                    ['name' => $supplier_name],
                    ['days_valid' => $days_valid, 'priority' => $priority]
                );

                Product::create([
                    'supplier_id' => $supplier->id,
                    'part_number' => $part_number,
                    'part_desc' => $part_desc,
                    'quantity' => $quantity,
                    'price' => $price,
                    'condition' => $condition,
                    'category' => $category,
                ]);
            }

            DB::commit();
            $this->info("Import finished successfully!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred: " . $e->getMessage());
        }

        fclose($handle);
        $this->info("Import finished successfully!");
    }
}
