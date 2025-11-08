<?php

namespace EkstreMedia\LaravelYouTube\Exceptions;

use Exception;

/**
 * Base exception for YouTube package
 */
class YouTubeException extends Exception
{
    /**
     * Error code from YouTube API
     */
    protected ?string $youtubeErrorCode = null;

    /**
     * Error reason from YouTube API
     */
    protected ?string $youtubeErrorReason = null;

    /**
     * Create a new YouTube exception
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param string|null $youtubeErrorCode
     * @param string|null $youtubeErrorReason
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $youtubeErrorCode = null,
        ?string $youtubeErrorReason = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->youtubeErrorCode = $youtubeErrorCode;
        $this->youtubeErrorReason = $youtubeErrorReason;
    }

    /**
     * Get YouTube error code
     *
     * @return string|null
     */
    public function getYouTubeErrorCode(): ?string
    {
        return $this->youtubeErrorCode;
    }

    /**
     * Get YouTube error reason
     *
     * @return string|null
     */
    public function getYouTubeErrorReason(): ?string
    {
        return $this->youtubeErrorReason;
    }

    /**
     * Create exception from Google service exception
     *
     * @param \Google_Service_Exception $exception
     * @return static
     */
    public static function fromGoogleServiceException(\Google_Service_Exception $exception): static
    {
        $errors = $exception->getErrors();
        $errorCode = null;
        $errorReason = null;

        if (!empty($errors) && isset($errors[0])) {
            $error = $errors[0];
            $errorCode = $error['domain'] ?? null;
            $errorReason = $error['reason'] ?? null;
        }

        return new static(
            $exception->getMessage(),
            $exception->getCode(),
            $exception,
            $errorCode,
            $errorReason
        );
    }
}