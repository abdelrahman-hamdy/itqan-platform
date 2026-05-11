<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown by the HorizonRunningCheck when no Horizon master supervisor is
 * alive. Implements CriticalException so the global report hook pages
 * Telegram even though the Spatie Health pipeline also surfaces it.
 */
class HorizonNotRunningException extends RuntimeException implements CriticalException {}
