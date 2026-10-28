<?php

namespace App\Http\Middleware\Backend;

use App\Libs\Security\Contracts\SecurityPasswordAccount;
use App\Libs\Security\SecurityPassword;
use App\Libs\Security\SecurityPasswordRequirement;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class BackendSecurityPasswordMid
{
    public function __construct(private readonly SecurityPasswordRequirement $requirements, private readonly SecurityPassword $passwords) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->requirements->forRoute($request->route()) === null) {
            return $next($request);
        }
        $account = Auth::guard('backend')->user();
        abort_unless($account instanceof SecurityPasswordAccount, 401);
        try {
            $body = $request->isJson() ? $request->json()->all() : $request->request->all();
            $validated = Validator::make(['security_password' => $body['security_password'] ?? null], [
                'security_password' => ['required', 'string', 'max:128'],
            ])->validate();
            $this->passwords->assertValid($account, $validated['security_password'], 'backend', $request->ip());
        } finally {
            $request->request->remove('security_password');
            $request->query->remove('security_password');
            if ($request->isJson()) {
                $request->json()->remove('security_password');
            }
        }

        return $next($request);
    }
}
