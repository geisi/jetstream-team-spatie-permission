<?php


use App\Models\Team;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

it('can update a team', function () {
    $data = ['name' => 'foobar'];
    $team = Team::factory()->create();

    actingAs(User::factory()->create());
    put(route('api.teams.update', $team), $data)
        ->assertOk();

    expect($team->fresh())->name->toBe('foobar');
});

it('syncs team memberships', function () {
    $users = User::factory()->count(3)->create();
    $data = ['name' => 'foobar'];
    $data['memberships'] = $users->map(function (User $user) {
        return ['user_id' => $user->id, 'roles' => []];
    })->toArray();

    $team = Team::factory()->create();

    actingAs(User::factory()->create());
    put(route('api.teams.update', $team), $data)
        ->assertOk();

    expect($team->users)
        ->toHaveCount(3)
        ->each(function ($user) use ($users) {
            $user->roles->toHaveCount(0);
            $user->id->toBeIn($users->pluck('id'));
        });
});

it('syncs team roles', function () {
//    seed(PermissionSeeder::class);
    $users = User::factory()->count(3)->create();
    $data = ['name' => 'foobar'];
    $data['memberships'] = $users->map(function (User $user) {
        return ['user_id' => $user->id, 'roles' => ['Admin', 'Editor', 'Member']];
    })->toArray();

    $team = Team::factory()->create();

    actingAs(User::factory()->create());
    put(route('api.teams.update', $team), $data)
        ->assertOk();

    expect($team->users)
        ->toHaveCount(3)
        ->each(function ($user) use ($users) {
            $user->roles->toHaveCount(3);
            $user->roles->each(function ($role) {
                $role->name->toBeIn(['Admin', 'Editor', 'Member']);
            });
            $user->id->toBeIn($users->pluck('id'));
        });
});
