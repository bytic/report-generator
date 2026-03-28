<?php

declare(strict_types=1);

namespace ByTIC\ReportGenerator\Tests\AsyncReport;

use ByTIC\ReportGenerator\AsyncReport\ContinuationToken;
use ByTIC\ReportGenerator\AsyncReport\ReportJobState;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ByTIC\ReportGenerator\AsyncReport\ContinuationToken
 */
class ContinuationTokenTest extends TestCase
{
    private const SECRET = 's3cr3t-t3st-k3y';

    // -------------------------------------------------------------------------

    public function test_encode_returns_non_empty_string(): void
    {
        $state = $this->makeState();
        $token = ContinuationToken::encode($state, self::SECRET);

        self::assertNotEmpty($token);
        self::assertIsString($token);
    }

    public function test_decode_round_trip(): void
    {
        $state = $this->makeState();
        $token = ContinuationToken::encode($state, self::SECRET);
        $decoded = ContinuationToken::decode($token, self::SECRET);

        self::assertSame($state->jobId, $decoded->jobId);
        self::assertSame($state->reportClass, $decoded->reportClass);
        self::assertSame($state->processedRows, $decoded->processedRows);
        self::assertSame($state->totalRows, $decoded->totalRows);
        self::assertSame($state->status, $decoded->status);
        self::assertSame($state->params, $decoded->params);
    }

    public function test_decode_throws_on_wrong_secret(): void
    {
        $state = $this->makeState();
        $token = ContinuationToken::encode($state, self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        ContinuationToken::decode($token, 'wrong-secret');
    }

    public function test_decode_throws_on_tampered_payload(): void
    {
        $state = $this->makeState();
        $token = ContinuationToken::encode($state, self::SECRET);

        // Tamper: flip a character in the middle
        $tampered = $token;
        $mid = (int) (strlen($tampered) / 2);
        $tampered[$mid] = $tampered[$mid] === 'A' ? 'B' : 'A';

        $this->expectException(InvalidArgumentException::class);
        ContinuationToken::decode($tampered, self::SECRET);
    }

    public function test_decode_throws_on_invalid_base64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ContinuationToken::decode('not-valid-base64!!!', self::SECRET);
    }

    public function test_token_is_url_safe(): void
    {
        $state = $this->makeState();
        $token = ContinuationToken::encode($state, self::SECRET);

        // Must not contain '+', '/', or padding '='
        self::assertStringNotContainsString('+', $token);
        self::assertStringNotContainsString('/', $token);
        self::assertStringNotContainsString('=', $token);
    }

    public function test_state_with_params_survives_round_trip(): void
    {
        $state = ReportJobState::initial(
            'App\\Reports\\SalesReport',
            ['year' => 2024, 'region' => 'EU'],
            'xlsx',
            200,
        );

        $token = ContinuationToken::encode($state, self::SECRET);
        $decoded = ContinuationToken::decode($token, self::SECRET);

        self::assertSame(2024, $decoded->params['year']);
        self::assertSame('EU', $decoded->params['region']);
        self::assertSame(200, $decoded->chunkSize);
    }

    // -------------------------------------------------------------------------

    private function makeState(): ReportJobState
    {
        return ReportJobState::initial(
            'App\\Reports\\TestReport',
            ['id' => 42],
            'xlsx',
            500,
        );
    }
}
