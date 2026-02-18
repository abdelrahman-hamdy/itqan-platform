<?php

namespace App\Support;

use Wirechat\Wirechat\Panel;

/**
 * Custom WireChat Panel that fixes route name generation.
 *
 * The vendor Panel::generateRouteName() returns "wirechat.{path}.{name}"
 * which produces "wirechat..chat" when path is empty (double dot).
 * Our routes are registered with simple names ('chat', 'chats') in
 * routes/web/chat.php, so we override to return just the name.
 */
class ItqanPanel extends Panel
{
    /**
     * Generate route name matching our custom chat routes.
     *
     * Our chat routes are defined in routes/web/chat.php with simple names:
     * - 'chats' (index)
     * - 'chat' (show conversation)
     *
     * The vendor default would produce 'wirechat..chat' which doesn't exist.
     */
    public function generateRouteName(string $name): string
    {
        return $name;
    }
}
