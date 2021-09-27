<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\Company;
use App\Models\CompanyToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Utils\Traits\MakesHash;
use Illuminate\Support\Str;



class CreateMyAccount extends Command
{
  use MakesHash;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create-my-account {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creating a custom account to test all its features.
                                        Two arguments are optional. First for email and
                                        second for Password.';

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
      $this->info(date('r').' Create My Account...');
      // print_r($this->argument());
      $email = $this->argument('email');
      $password = $this->argument('password');

      $this->createMyAccount($email, $password);
    }

    private function createMyAccount($email, $password)
    {
      $this->email = $email;
      $this->password = $password;

      $user = User::find($email);

      if(!$user) {
        $this->info(date('r').' Creating Account and Company...');
        $account = Account::factory()->create();
        $company = Company::factory()->create([
            'account_id' => $account->id,
        ]);

        $account->default_company_id = $company->id;
        $account->save();

        $this->info(date('r').' Creating User...');

        $user = User::factory()->create([
            'account_id' => $account->id,
            'email' => $email,
            'password' => Hash::make($password),
            'confirmation_code' => $this->createDbHash(config('database.default')),
            'email_verified_at' => now(),
            'first_name'        => 'Badshah',
            'last_name'         => 'Nandi',
            'phone'             => '9876543210',
        ]);

        $this->info(date('r').' Creating Company Token...');

        $company_token = new CompanyToken;
        $company_token->user_id = $user->id;
        $company_token->company_id = $company->id;
        $company_token->account_id = $account->id;
        $company_token->name = 'Badshah\'s Token';
        $company_token->token = Str::random(64);
        $company_token->is_system = true;

        $company_token->save();

        // $this->saveAll($account, $company_token);

      } else {
        $this->info(date('r').' User already created...');
      }

    }

    // private function saveAll($first, $second) {
    //   $this->account = $first;
    //   $this->company_token = $second;
    //
    //   $account->save();
    //   $company_token->save();
    // }

}
