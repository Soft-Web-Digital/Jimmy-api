<?php

namespace App\Console\Commands;

use App\Contracts\Fillers\BankFiller;
use Illuminate\Console\Command;

class UpdateBanksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ksb_banks:update-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bank list in storage';

    /**
     * Execute the console command.
     *
     * @param \App\Contracts\Fillers\BankFiller $bankFiller
     * @return int
     */
    public function handle(BankFiller $bankFiller)
    {
        $this->components->task('Banks list updating', fn () => $bankFiller->fillBanksInStorage());

        return Command::SUCCESS;
    }
}
