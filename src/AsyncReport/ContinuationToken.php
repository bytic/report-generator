<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\AsyncReport;

use InvalidArgumentException;
use JsonException;

/**
 * Encodes/decodes a {@see ReportJobState} as a URL-safe, HMAC-signed token.
 *
 * Token structure (before base64url encoding):
 *   {"state":{...},"sig":"<sha256-hmac-hex>"}
 *
 * The HMAC covers the JSON-encoded `state` object so that any tampering
 * with the state (e.g. forging a different `reportClass` or inflating
 * `processedRows`) will be detected on decode.
 *
 * Security note: the `secret` parameter should be derived from a
 * application-level secret (e.g. APP_KEY env var), never hardcoded.
 */
class ContinuationToken
{
    private const ALGO = 'sha256';

    /**
     * Encode a state object into a signed, URL-safe token string.
     *
     * @throws JsonException
     */
    public static function encode(ReportJobState $state, string $secret): string
    {
        $stateJson = json_encode($state->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $sig = hash_hmac(self::ALGO, $stateJson, $secret);

        $payload = json_encode(
            ['state' => $state->toArray(), 'sig' => $sig],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
        );

        return self::base64UrlEncode($payload);
    }

    /**
     * Decode and verify a token, returning the embedded state.
     *
     * @throws InvalidArgumentException if the token is malformed or the
     *                                  signature does not match.
     */
    public static function decode(string $token, string $secret): ReportJobState
    {
        try {
            $payload = self::base64UrlDecode($token);
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Malformed continuation token: ' . $e->getMessage(), 0, $e);
        }

        if (!isset($data['state'], $data['sig'])) {
            throw new InvalidArgumentException('Continuation token is missing required fields.');
        }

        // Re-compute the expected signature
        $stateJson = json_encode($data['state'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $expected = hash_hmac(self::ALGO, $stateJson, $secret);

        if (!hash_equals($expected, (string) $data['sig'])) {
            throw new InvalidArgumentException('Continuation token signature is invalid.');
        }

        return ReportJobState::fromArray($data['state']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Continuation token is not valid base64.');
        }

        return $decoded;
    }
}
