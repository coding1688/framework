<?php

namespace App\Console\Commands\Backend;

use App\Models\Admin\AdminDepartment;
use App\Models\Admin\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'backend:create-admin {email} {--name=Administrator}';

    protected $description = 'Create a backend super administrator using a hidden password prompt';

    public function handle(): int
    {
        $data = ['email' => strtolower($this->argument('email')), 'name' => $this->option('name'), 'password' => $this->secret('Password (12+ characters, upper/lowercase and number)')];
        $validator = Validator::make($data, [
            'email' => ['required', 'email', 'unique:admin_users', 'max:256'],
            'name' => ['required', 'string', 'max:128'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers(), 'max:128'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $admin = new AdminUser($data);
        $admin->is_super = true;
        $admin->department_id = AdminDepartment::where('code', 'board')->sole()->id;
        $admin->save();
        $this->info('Backend super administrator created.');

        return self::SUCCESS;
    }
}
