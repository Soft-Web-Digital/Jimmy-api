<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as CommandAlias;

class GenerateRefCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ksb_ref:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ref for users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        DB::beginTransaction();
        foreach (User::query()->whereNull('ref_code')->get() as $user) {
            do {
                $ref = 'KSB-' . Str::upper(Str::random(6));
            } while (User::query()->where('ref_code', $ref)->exists());
            $user->update(['ref_code' => $ref]);
        }
        DB::commit();
        return CommandAlias::SUCCESS;
    }
}
