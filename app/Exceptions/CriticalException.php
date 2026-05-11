<?php

namespace App\Exceptions;

/**
 * Marker interface — implementing exceptions opt into Telegram paging
 * via the global report() hook. Default behavior is silent.
 */
interface CriticalException {}
