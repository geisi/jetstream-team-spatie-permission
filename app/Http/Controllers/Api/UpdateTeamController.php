<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class UpdateTeamController extends Controller
{
    public function __invoke(Request $request, Team $team): Team
    {
        $data = $request->validate([
            'name' => ['string', 'required', 'max:255'],
            'memberships' => ['array', 'sometimes'],
            'memberships.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'memberships.*.roles' => ['present', 'array'],
            'memberships.*.roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')]
        ]);

        DB::transaction(function () use (&$team, $data, $request) {
            //Update team fields
            $team->forceFill($request->except(['memberships']));
            $team->saveOrFail();

            if ($request->has('memberships')) {
                $memberships = collect($data['memberships']);
                //sync team memberships
                $team->users()->sync($memberships->map(function ($membership) {
                    return $membership['user_id'];
                })->toArray());
                //set Spatie permissions team foreign id scope
                app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
                //sync roles
                $memberships->each(function ($membership) {
                    $user = User::findOrFail($membership['user_id']);
                    $user->syncRoles($membership['roles']);
                });
            }

        });
        return $team;
    }
}
