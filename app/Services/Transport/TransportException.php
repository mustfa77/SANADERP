<?php

namespace App\Services\Transport;

use RuntimeException;

/**
 * A transport step that cannot be done now (translated message).
 */
class TransportException extends RuntimeException {}
