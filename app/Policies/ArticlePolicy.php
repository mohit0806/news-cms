<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Article authorization (TRD §7; P2-T03.2).
 *
 * Ownership rule: `update` = `articles.edit.any`, or `articles.edit.own`
 * on the author's own article while it is still draft/in_review. Publishing
 * powers stay editorial (Admin/Editor). Responses explain denials.
 */
class ArticlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('articles.create')
            || $user->can('articles.edit.own')
            || $user->can('articles.edit.any');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Article $article): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('articles.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Article $article): Response
    {
        if ($user->can('articles.edit.any')) {
            return Response::allow();
        }

        if ($user->can('articles.edit.own')
            && (int) $article->created_by === (int) $user->getKey()
            && in_array($article->status, ['draft', 'in_review'], true)) {
            return Response::allow();
        }

        return Response::deny('Only editors may edit this article in its current state.');
    }

    /**
     * Determine whether the user can submit the model for review.
     */
    public function submit(User $user, Article $article): Response
    {
        if (! $user->can('articles.submit')) {
            return Response::deny('Submitting for review is not permitted.');
        }

        return $this->update($user, $article);
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user): bool
    {
        return $user->can('articles.publish');
    }

    /**
     * Determine whether the user can unpublish the model.
     */
    public function unpublish(User $user): bool
    {
        return $user->can('articles.unpublish');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->can('articles.delete');
    }

    /**
     * Determine whether the user can mark the model as breaking.
     */
    public function setBreaking(User $user): bool
    {
        return $user->can('articles.set_breaking');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article): Response
    {
        return $this->update($user, $article);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return false;
    }
}
