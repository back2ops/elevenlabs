<?php

namespace Back2ops\ElevenLabs\Exceptions;

use RuntimeException;

/**
 * Thrown when the ElevenLabs API returns an error response.
 * The HTTP status code is available via getCode().
 */
class ElevenLabsApiException extends RuntimeException
{
    //
}
