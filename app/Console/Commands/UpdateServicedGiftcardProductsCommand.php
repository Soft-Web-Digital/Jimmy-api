<?php

namespace App\Console\Commands;

use App\Enums\GiftcardServiceProvider;
use Illuminate\Console\Command;

class UpdateServicedGiftcardProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ksb_giftcard:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the serviced Giftcard products';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (GiftcardServiceProvider::cases() as $serviceProvider) {
            $serviceProvider->productFiller()?->fillGiftcardProductsInStorage();
        }

        return Command::SUCCESS;
    }
}
