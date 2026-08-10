<?php

use App\Broadcasting\FormBuilderChannelAuthorizer;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| A form's builder presence channel replaces the old Cache+polling
| "activeEditors" system with real presence: Echo's join()/here()/
| joining()/leaving() on this channel gives every collaborator an
| accurate, immediate view of who else has the builder open, instead of
| a snapshot up to 20 seconds stale. See FormBuilderChannelAuthorizer for
| the actual check and why it's factored out into its own class.
|
*/

Broadcast::channel(
    'form-builder.{formId}',
    fn ($user, int $formId) => FormBuilderChannelAuthorizer::authorize($user, $formId)
);
