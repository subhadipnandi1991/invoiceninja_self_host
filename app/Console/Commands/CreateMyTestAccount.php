<?php

namespace App\Console\Commands;

use App\DataMapper\CompanySettings;
use App\Events\Invoice\InvoiceWasCreated;
use App\Factory\InvoiceFactory;
use App\Factory\InvoiceItemFactory;
use App\Factory\QuoteFactory;
use App\Helpers\Invoice\InvoiceSum;
use App\Models\Account;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Company;
use App\Models\CompanyToken;
use App\Models\Country;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorContact;
use App\Repositories\InvoiceRepository;
use App\Utils\Ninja;
use App\Utils\Traits\GeneratesCounter;
use App\Utils\Traits\MakesHash;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class CreateMyTestAccount extends Command
{
  use MakesHash, GeneratesCounter;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create-my-test-account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'creating some test data accrding to my wish';

    protected $invoice_repo;

    /**
     * Create a new command instance.
     *
     * @param InvoiceRepository $invoice_repo
     */
    public function __construct(InvoiceRepository $invoice_repo)
    {
        parent::__construct();

        $this->invoice_repo = $invoice_repo;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
      $this->info(date('r').' Running CreateMyTestAccount...');

      $this->count = 1;
      $this->createMyTestAccount();
      // $this->testFunction();
    }

    private function testFunction(){

  //    $user = DB::table('users')->where('email', 'badshah@myexample.com')->get();

    }

    private function createMyTestAccount()
    {
      $this->info(date('r').' Creating My Test Account..');
      // $this->info(config('ninja.notification.slack'));

      $account = Account::factory()->create();
      $company = Company::factory()->create([
        'account_id' => $account->id,
      ]);

      $account->default_company_id = $company->id;
      $account->save();


      $user = User::whereEmail('badshah@example.com')->first();

      // $this->info($user);
      //
      // die();
      // dd($user);

      if (!$user) {
        $user = User::factory()->create([
          'account_id' => $account->id,
          'email' => 'badshah@example.com',
          'confirmation_code' => $this->createDbHash(config('database.default')),
          'email_verified_at' => now(),
          'first_name'        => 'Badshah',
          'last_name'         => 'Nandi',
          'phone'             => '9876543210',
        ]);
      }

      //
      if($user) {
        $user->password = Hash::make('Password2');
        $user->save();
      }

      $this->info(date('r').' Creating Company Token...');

      $company_token = new CompanyToken;
      $company_token->user_id = $user->id;
      $company_token->company_id = $company->id;
      $company_token->account_id = $account->id;
      $company_token->name = 'Badshah\'s Token';
      $company_token->token = Str::random(64);
      $company_token->is_system = true;

      $company_token->save();

      $user->companies()->attach($company->id, [
        'account_id' => $account->id,
        'is_owner' => 1,
        'is_admin' => 1,
        'is_locked' => 0,
        'settings' => null,
        'notifications' => CompanySettings::notificationDefaults(),

      ]);

      Product::factory()->count(5)->create([
              'user_id' => $user->id,
              'company_id' => $company->id,
          ]);

      // $this->count = $this->count * 5;

      $this->info('Creating '.$this->count.' clients');

      for ($x = 0; $x < $this->count; $x++) {
          $this->info('Creating client # '.($x+1));

          $this->createClient($company, $user);
      }

      for ($x = 0; $x < $this->count; $x++) {
          $client = $company->clients->random();

          $this->info('creating invoice for client #'.$client->id);
          $this->createInvoice($client);

          $client = $company->clients->random();

          $this->info('creating credit for client #'.$client->id);
          $this->createCredit($client);

          $client = $company->clients->random();

          $this->info('creating quote for client #'.$client->id);
          $this->createQuote($client);

          $client = $company->clients->random();

          $this->info('creating expense for client #'.$client->id);
          $this->createExpense($client);

          $client = $company->clients->random();

          $this->info('creating vendor for client #'.$client->id);
          $this->createVendor($client);

          $client = $company->clients->random();

          $this->info('creating task for client #'.$client->id);
          $this->createTask($client);

          $client = $company->clients->random();

          $this->info('creating project for client #'.$client->id);
          $this->createProject($client);
      }

    }

    private function createClient($company, $user)
    {
        $client = Client::factory()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
            ]);

        ClientContact::factory()->create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'company_id' => $company->id,
                    'is_primary' => 1,
                ]);

        ClientContact::factory()->count(rand(1, 5))->create([
                    'user_id' => $user->id,
                    'client_id' => $client->id,
                    'company_id' => $company->id,
                ]);

        $client->number = $this->getNextClientNumber($client);

        $settings = $client->settings;
        $settings->currency_id = (string) rand(1, 79);
        $client->settings = $settings;

        $country = Country::all()->random();

        $client->country_id = $country->id;
        $client->save();
    }

    private function createExpense($client)
    {
        Expense::factory()->count(rand(1, 5))->create([
                'user_id' => $client->user->id,
                'client_id' => $client->id,
                'company_id' => $client->company->id,
            ]);
    }

    private function createVendor($client)
    {
        $vendor = Vendor::factory()->create([
                'user_id' => $client->user->id,
                'company_id' => $client->company->id,
            ]);

        VendorContact::factory()->create([
                'user_id' => $client->user->id,
                'vendor_id' => $vendor->id,
                'company_id' => $client->company->id,
                'is_primary' => 1,
            ]);

        VendorContact::factory()->count(rand(1, 5))->create([
                'user_id' => $client->user->id,
                'vendor_id' => $vendor->id,
                'company_id' => $client->company->id,
                'is_primary' => 0,
            ]);
    }

    private function createTask($client)
    {
        $vendor = Task::factory()->create([
                'user_id' => $client->user->id,
                'company_id' => $client->company->id,
            ]);
    }

    private function createProject($client)
    {
        $vendor = Project::factory()->create([
                'user_id' => $client->user->id,
                'company_id' => $client->company->id,
            ]);
    }

    private function createInvoice($client)
    {
        $faker = Factory::create();

        $invoice = InvoiceFactory::create($client->company->id, $client->user->id); //stub the company and user_id
        $invoice->client_id = $client->id;
//        $invoice->date = $faker->date();
        $dateable = Carbon::now()->subDays(rand(0, 90));
        $invoice->date = $dateable;

        $invoice->line_items = $this->buildLineItems(rand(1, 10));
        $invoice->uses_inclusive_taxes = false;

        if (rand(0, 1)) {
            $invoice->tax_name1 = 'GST';
            $invoice->tax_rate1 = 10.00;
        }

        if (rand(0, 1)) {
            $invoice->tax_name2 = 'VAT';
            $invoice->tax_rate2 = 17.50;
        }

        if (rand(0, 1)) {
            $invoice->tax_name3 = 'CA Sales Tax';
            $invoice->tax_rate3 = 5;
        }

        $invoice->custom_value1 = $faker->date;
        $invoice->custom_value2 = rand(0, 1) ? 'yes' : 'no';

        $invoice->save();

        $invoice_calc = new InvoiceSum($invoice);
        $invoice_calc->build();

        $invoice = $invoice_calc->getInvoice();

        $invoice->save();
        $invoice->service()->createInvitations()->markSent();

        $this->invoice_repo->markSent($invoice);

        if (rand(0, 1)) {
            $invoice = $invoice->service()->markPaid()->save();
        }

        event(new InvoiceWasCreated($invoice, $invoice->company, Ninja::eventVars()));
    }

    private function createCredit($client)
    {
        $faker = Factory::create();

        $credit = Credit::factory()->create(['user_id' => $client->user->id, 'company_id' => $client->company->id, 'client_id' => $client->id]);

        $dateable = Carbon::now()->subDays(rand(0, 90));
        $credit->date = $dateable;

        $credit->line_items = $this->buildLineItems(rand(1, 10));
        $credit->uses_inclusive_taxes = false;

        if (rand(0, 1)) {
            $credit->tax_name1 = 'GST';
            $credit->tax_rate1 = 10.00;
        }

        if (rand(0, 1)) {
            $credit->tax_name2 = 'VAT';
            $credit->tax_rate2 = 17.50;
        }

        if (rand(0, 1)) {
            $credit->tax_name3 = 'CA Sales Tax';
            $credit->tax_rate3 = 5;
        }

        $credit->save();

        $invoice_calc = new InvoiceSum($credit);
        $invoice_calc->build();

        $credit = $invoice_calc->getCredit();

        $credit->save();
        $credit->service()->markSent()->save();
        $credit->service()->createInvitations();
    }

    private function createQuote($client)
    {
        $faker = Factory::create();

        //$quote = QuoteFactory::create($client->company->id, $client->user->id);//stub the company and user_id
        $quote = Quote::factory()->create(['user_id' => $client->user->id, 'company_id' => $client->company->id, 'client_id' => $client->id]);
        $quote->date = $faker->date();
        $quote->client_id = $client->id;

        $quote->setRelation('client', $client);

        $quote->line_items = $this->buildLineItems(rand(1, 10));
        $quote->uses_inclusive_taxes = false;

        if (rand(0, 1)) {
            $quote->tax_name1 = 'GST';
            $quote->tax_rate1 = 10.00;
        }

        if (rand(0, 1)) {
            $quote->tax_name2 = 'VAT';
            $quote->tax_rate2 = 17.50;
        }

        if (rand(0, 1)) {
            $quote->tax_name3 = 'CA Sales Tax';
            $quote->tax_rate3 = 5;
        }

        $quote->save();

        $quote_calc = new InvoiceSum($quote);
        $quote_calc->build();

        $quote = $quote_calc->getQuote();

        $quote->save();

        $quote->service()->markSent()->save();
        $quote->service()->createInvitations();
    }

    private function buildLineItems($count = 1)
    {
        $line_items = [];

        for ($x = 0; $x < $count; $x++) {
            $item = InvoiceItemFactory::create();
            $item->quantity = 1;
            //$item->cost = 10;

            if (rand(0, 1)) {
                $item->tax_name1 = 'GST';
                $item->tax_rate1 = 10.00;
            }

            if (rand(0, 1)) {
                $item->tax_name1 = 'VAT';
                $item->tax_rate1 = 17.50;
            }

            if (rand(0, 1)) {
                $item->tax_name1 = 'Sales Tax';
                $item->tax_rate1 = 5;
            }

            $product = Product::all()->random();

            $item->cost = (float) $product->cost;
            $item->product_key = $product->product_key;
            $item->notes = $product->notes;
            $item->custom_value1 = $product->custom_value1;
            $item->custom_value2 = $product->custom_value2;
            $item->custom_value3 = $product->custom_value3;
            $item->custom_value4 = $product->custom_value4;

            $line_items[] = $item;
        }

        return $line_items;
    }

}
