<?php

namespace Tests\Feature;

use App\Livewire\Admin\Prompts\Form as AdminPromptForm;
use App\Livewire\Admin\Prompts\Index as AdminPromptIndex;
use App\Livewire\Admin\Tags\Form as AdminTagForm;
use App\Models\Prompt;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private array $adminPaths = [
        '/admin',
        '/admin/prompts',
        '/admin/prompts/create',
        '/admin/tags',
        '/admin/tags/create',
    ];

    public function test_guest_is_redirected_from_every_admin_route(): void
    {
        foreach ($this->adminPaths as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_non_admin_user_receives_403_from_admin_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        foreach ($this->adminPaths as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }
    }

    public function test_admin_user_can_reach_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->adminPaths as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_admin_can_create_a_prompt_with_user_id_set_to_self(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create();

        Livewire::actingAs($admin)
            ->test(AdminPromptForm::class)
            ->set('title', 'A new prompt from a test')
            ->set('body', 'The body of the test prompt.')
            ->set('is_public', true)
            ->set('tagIds', [$tag->id])
            ->call('save');

        $this->assertDatabaseHas('prompts', [
            'title' => 'A new prompt from a test',
            'is_public' => true,
            'user_id' => $admin->id,
        ]);

        $prompt = Prompt::where('title', 'A new prompt from a test')->firstOrFail();
        $this->assertTrue($prompt->tags->contains('id', $tag->id));
    }

    public function test_admin_can_edit_a_prompt(): void
    {
        $admin = User::factory()->admin()->create();
        $prompt = Prompt::factory()->private()->for($admin, 'user')->create(['title' => 'Old']);

        Livewire::actingAs($admin)
            ->test(AdminPromptForm::class, ['prompt' => $prompt])
            ->set('title', 'New title')
            ->call('save');

        $this->assertSame('New title', $prompt->fresh()->title);
    }

    public function test_admin_can_delete_a_prompt(): void
    {
        $admin = User::factory()->admin()->create();
        $prompt = Prompt::factory()->for($admin, 'user')->create();

        Livewire::actingAs($admin)
            ->test(AdminPromptForm::class, ['prompt' => $prompt])
            ->call('delete');

        $this->assertNull(Prompt::find($prompt->id));
    }

    public function test_admin_can_toggle_public_visibility(): void
    {
        $admin = User::factory()->admin()->create();
        $prompt = Prompt::factory()->private()->for($admin, 'user')->create();

        Livewire::actingAs($admin)
            ->test(AdminPromptIndex::class)
            ->call('togglePublic', $prompt->id);

        $this->assertTrue($prompt->fresh()->is_public);

        Livewire::actingAs($admin)
            ->test(AdminPromptIndex::class)
            ->call('togglePublic', $prompt->id);

        $this->assertFalse($prompt->fresh()->is_public);
    }

    public function test_admin_can_create_a_tag(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(AdminTagForm::class)
            ->set('name', 'BrandNewTag')
            ->call('save');

        $this->assertDatabaseHas('tags', ['name' => 'BrandNewTag', 'slug' => 'brandnewtag']);
    }

    public function test_admin_can_edit_a_tag(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create(['name' => 'OldName']);

        Livewire::actingAs($admin)
            ->test(AdminTagForm::class, ['tag' => $tag])
            ->set('name', 'RenamedTag')
            ->call('save');

        $this->assertSame('RenamedTag', $tag->fresh()->name);
        $this->assertSame('renamedtag', $tag->fresh()->slug);
    }

    public function test_no_tag_delete_route_exists(): void
    {
        $admin = User::factory()->admin()->create();
        $tag = Tag::factory()->create();

        // /admin/tags/{tag} is not defined at all (only /admin/tags/{tag}/edit is).
        // Confirm the DELETE verb has no matching route — 404 proves no delete handler.
        $this->actingAs($admin)
            ->delete("/admin/tags/{$tag->id}")
            ->assertNotFound();

        // The tag must still exist after the failed delete attempt.
        $this->assertDatabaseHas('tags', ['id' => $tag->id]);
    }
}
