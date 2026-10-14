<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_page_can_be_accessed()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        // Create admin role first for the admin() factory method
        Roles::firstOrCreate(['name' => 'admin']);
        
        $user = User::factory()->admin()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'status' => 'active',
        ]);
        
        // Set password directly in database to avoid any factory/mutator issues
        \DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make('password')
        ]);

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_with_nonexistent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = $this->actingAsAdmin();

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $this->actingAsAdmin();

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_switch_roles()
    {
        // Use lowercase names because Roles model has a mutator that converts to lowercase
        $adminRole = Roles::firstOrCreate(['name' => 'admin']);
        $teacherRole = Roles::firstOrCreate(['name' => 'teacher']);
        
        $user = User::factory()->create();
        $user->roles()->attach([$adminRole->id, $teacherRole->id]);
        $user->active_role_id = $adminRole->id;
        $user->save();

        $this->actingAs($user);

        $response = $this->post('/switch-role', [
            'role' => $teacherRole->name,
        ]);

        $response->assertRedirect();
        // Check session instead of database, as controller stores role in session
        $response->assertSessionHas('active_role', $teacherRole->name);
    }
}
