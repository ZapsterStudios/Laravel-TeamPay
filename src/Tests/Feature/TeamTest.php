<?php

namespace ZapsterStudios\TeamPay\Tests\Feature;

use App\Team;
use App\User;
use Laravel\Passport\Passport;
use ZapsterStudios\TeamPay\Tests\TestCase;

class TeamTest extends TestCase
{
    public $teamStructure = [
        'id', 'name', 'slug',
        'created_at', 'updated_at',
    ];

    /** @test */
    public function guestCanNotRetrieveTeams()
    {
        $response = $this->json('GET', '/teams');

        $response->assertStatus(401);
    }

    /** @test */
    public function userCanRetrieveTeams()
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user, ['view-teams']);

        $response = $this->json('GET', '/teams');

        $response->assertStatus(200);
    }

    /** @test */
    public function userCanViewOwnTeam()
    {
        $user = factory(User::class)->create();
        $team = $user->teams()->save(factory(Team::class)->create());
        Passport::actingAs($user, ['view-teams']);

        $response = $this->json('GET', '/teams/'.$team->slug);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->teamStructure);
    }

    /** @test */
    public function userCanCreateNewTeam()
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user, ['manage-teams']);

        $response = $this->json('POST', '/teams', [
            'name' => 'Example',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->teamStructure);

        $this->assertDatabaseHas('teams', [
            'name' => 'Example',
        ]);
    }

    /** @test */
    public function userCanUpdateExistingTeam()
    {
        $user = factory(User::class)->create();
        $team = $user->teams()->save(factory(Team::class)->create());
        Passport::actingAs($user, ['manage-teams']);

        $response = $this->json('PUT', '/teams/'.$team->slug, [
            'name' => 'Foobar',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure($this->teamStructure);

        $this->assertDatabaseHas('teams', [
            'name' => 'Foobar',
            'slug' => str_slug('Foobar'),
        ]);
    }

    /** @test */
    public function userCanDeleteTeam()
    {
        $user = factory(User::class)->create();
        $team = $user->teams()->save(factory(Team::class)->create());
        Passport::actingAs($user, ['manage-teams']);

        $response = $this->json('DELETE', '/teams/'.$team->slug);

        $response->assertStatus(200);

        $this->assertSoftDeleted('teams', [
            'slug' => $team->slug,
        ]);
    }
}