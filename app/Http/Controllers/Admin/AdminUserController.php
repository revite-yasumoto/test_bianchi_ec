<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AdminUser\GenerateAdminCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUser\StoreAdminUserRequest;
use App\Http\Requests\Admin\AdminUser\UpdateAdminUserRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(): Response
    {
        $admins = Admin::query()
            ->orderBy('admin_code')
            ->get()
            ->map(fn (Admin $admin): array => [
                'id' => $admin->id,
                'admin_code' => $admin->admin_code,
                'name' => $admin->name,
                'email' => $admin->email,
                'registered_on' => $admin->created_at->format('Y.m.d'),
            ])
            ->all();

        return Inertia::render('admin/AdminUser/Index', [
            'admins' => $admins,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/AdminUser/Form', ['admin' => null]);
    }

    public function store(StoreAdminUserRequest $request, GenerateAdminCode $generateAdminCode): RedirectResponse
    {
        Admin::query()->create([
            'admin_code' => $generateAdminCode(),
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        return redirect()->route('admin.admins.index');
    }

    public function edit(Admin $admin): Response
    {
        return Inertia::render('admin/AdminUser/Form', [
            'admin' => [
                'id' => $admin->id,
                'admin_code' => $admin->admin_code,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }

    public function update(UpdateAdminUserRequest $request, Admin $admin): RedirectResponse
    {
        $attributes = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
        ];

        if ($request->filled('password')) {
            $attributes['password'] = $request->string('password')->toString();
        }

        $admin->update($attributes);

        return redirect()->route('admin.admins.index');
    }

    public function destroy(Request $request, Admin $admin): RedirectResponse
    {
        if ($request->user('admin')?->is($admin)) {
            return back()->withErrors([
                'delete' => 'ログイン中の自分自身は削除できません。',
            ]);
        }

        // 全員を削除すると誰も管理画面に入れなくなるため、最後の1名は残す
        if (Admin::query()->count() <= 1) {
            return back()->withErrors([
                'delete' => '管理者が1名のため削除できません。',
            ]);
        }

        $admin->delete();

        return redirect()->route('admin.admins.index');
    }
}
