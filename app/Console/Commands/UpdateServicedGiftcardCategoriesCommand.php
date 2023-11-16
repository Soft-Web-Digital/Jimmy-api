<?php

namespace App\Console\Commands;

use App\Enums\GiftcardServiceProvider;
use Illuminate\Console\Command;

class UpdateServicedGiftcardCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'giftcard:categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the serviced Giftcard categories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (GiftcardServiceProvider::cases() as $serviceProvider) {
            $serviceProvider->categoryFiller()?->fillGiftcardCategoriesInStorage();
        }

        return Command::SUCCESS;
    }
}
