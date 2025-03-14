<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CreateTestUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user and generate a token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com'.uniqid(),
            'preferences' => [
                'keywords' => ['Elon Musk', 'Donald Trump'],
                'categories' => ['Business', 'Politics'],
            ],
        ];

        $user = User::factory()->create([
            'password' => bcrypt('password'),
           ...$data,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->info("User created with email: {$data['email']}");
        $this->info('The default preferences are:');
        $this->info(json_encode($data['preferences']));

        $this->info('Use the following token to authenticate your requests:');
        $this->info("Authorization: Bearer {$token}");
    }
}
