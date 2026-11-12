<?php

namespace App\Services\Cases;

use RuntimeException;

/**
 * A case step that cannot be done now (translated message).
 */
class CaseException extends RuntimeException {}
