<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Enums\ResponseCodeCategory;
use GrupoFixIT\Mpesa\Exceptions\ApiException;
use GrupoFixIT\Mpesa\Responses\MpesaResponse;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ResponseCodeTest extends TestCase
{
    public function testOfficialSuccessCode(): void
    {
        $code = ResponseCode::fromRaw('INS-0');
        $this->assertSame(ResponseCode::Success, $code);
        $this->assertTrue($code->isSuccess());
        $this->assertSame(ResponseCodeCategory::Success, $code->category());
        $this->assertNotEmpty($code->descriptionEn());
        $this->assertNotEmpty($code->descriptionPt());
    }

    public function testCommonErrorCodes(): void
    {
        $this->assertSame(ResponseCodeCategory::Retryable, ResponseCode::InternalError->category());
        $this->assertSame(ResponseCodeCategory::ClientError, ResponseCode::InvalidApiKey->category());
        $this->assertSame(ResponseCodeCategory::FinalFailure, ResponseCode::InsufficientBalance->category());
        $this->assertSame(ResponseCodeCategory::FinalFailure, ResponseCode::CancelledByCustomer->category());
        $this->assertSame(ResponseCodeCategory::NeedsReview, ResponseCode::RequestTimeout->category());
    }

    public function testUnknownCodeNeverPresumesSuccess(): void
    {
        $unknown1 = ResponseCode::fromRaw('INS-99999');
        $this->assertSame(ResponseCode::Unknown, $unknown1);
        $this->assertFalse($unknown1->isSuccess());
        $this->assertSame(ResponseCodeCategory::Unknown, $unknown1->category());

        $unknown2 = ResponseCode::fromRaw(null);
        $this->assertSame(ResponseCode::Unknown, $unknown2);
        $this->assertFalse($unknown2->isSuccess());

        $unknown3 = ResponseCode::fromRaw('');
        $this->assertSame(ResponseCode::Unknown, $unknown3);
        $this->assertFalse($unknown3->isSuccess());
    }

    public function testSafeBodyParsingHtmlDoesNotThrowTypeError(): void
    {
        $htmlResponse = new Response(502, ['Content-Type' => 'text/html'], '<html><body>502 Bad Gateway</body></html>');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Resposta não-JSON');

        MpesaResponse::parseResponseBody($htmlResponse);
    }

    public function testSafeBodyParsingEmptyErrorResponse(): void
    {
        $emptyResponse = new Response(500, ['Content-Type' => 'application/json'], '');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Resposta vazia');

        MpesaResponse::parseResponseBody($emptyResponse);
    }

    public function testSafeBodyParsingValidJson(): void
    {
        $validJson = new Response(200, [], json_encode([
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ConversationID' => 'conv_12345',
        ], JSON_THROW_ON_ERROR));

        $data = MpesaResponse::parseResponseBody($validJson);

        $this->assertSame('INS-0', $data['output_ResponseCode']);
        $this->assertSame('conv_12345', $data['output_ConversationID']);
    }
}
