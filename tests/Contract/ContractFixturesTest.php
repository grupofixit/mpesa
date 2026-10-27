<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Contract;

use GrupoFixIT\Mpesa\Callback\CallbackParser;
use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Enums\TransactionStatus;
use GrupoFixIT\Mpesa\Responses\CustomerNameResult;
use GrupoFixIT\Mpesa\Responses\ReversalResult;
use GrupoFixIT\Mpesa\Responses\TransactionResult;
use GrupoFixIT\Mpesa\Responses\TransactionStatusResult;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ContractFixturesTest extends TestCase
{
    public function testC2BSynchronousContract(): void
    {
        $fixture = [
            'output_ConversationID' => 'AG_20180206_00005e7dccc6da08efa8',
            'output_TransactionID' => '4XDF12345',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ResponseCode' => 'INS-0',
            'output_ThirdPartyReference' => '11114',
        ];

        $response = new Response(200, [], json_encode($fixture, JSON_THROW_ON_ERROR));
        $result = TransactionResult::fromPsrResponse($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('AG_20180206_00005e7dccc6da08efa8', $result->conversationId);
        $this->assertSame('4XDF12345', $result->transactionId);
        $this->assertSame('11114', $result->thirdPartyReference);
    }

    public function testC2BAsynchronousImmediateContract(): void
    {
        $fixture = [
            'output_ThirdPartyReference' => '11114',
            'output_ConversationID' => '18ffa4ccf93343379421d2eac15a3e8c',
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
        ];

        $response = new Response(200, [], json_encode($fixture, JSON_THROW_ON_ERROR));
        $result = TransactionResult::fromPsrResponse($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('18ffa4ccf93343379421d2eac15a3e8c', $result->conversationId);
        $this->assertNull($result->transactionId);
    }

    public function testTransactionStatusContract(): void
    {
        $fixture = [
            'output_ConversationID' => 'AG_20180206_00005e7dccc6da08efa8',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ResponseCode' => 'INS-0',
            'output_ThirdPartyReference' => '11114',
            'output_ResponseTransactionStatus' => 'Completed',
        ];

        $response = new Response(200, [], json_encode($fixture, JSON_THROW_ON_ERROR));
        $result = TransactionStatusResult::fromPsrResponse($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TransactionStatus::Completed, $result->transactionStatus);
        $this->assertSame('11114', $result->thirdPartyReference);
    }

    public function testCustomerNameContract(): void
    {
        $fixture = [
            'output_ConversationID' => 'AG_20180206_00005e7dccc6da08efa8',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ResponseCode' => 'INS-0',
            'output_ThirdPartyReference' => '11114',
            'output_CustomerName' => 'Jo** Sil**',
        ];

        $response = new Response(200, [], json_encode($fixture, JSON_THROW_ON_ERROR));
        $result = CustomerNameResult::fromPsrResponse($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Jo** Sil**', $result->customerName);
        $this->assertSame('Jo**', $result->firstNameMasked);
        $this->assertSame('Sil**', $result->secondNameMasked);
    }

    public function testReversalContract(): void
    {
        $fixture = [
            'output_ConversationID' => 'AG_20180206_00005e7dccc6da08efa8',
            'output_TransactionID' => 'REV_4XDF12345',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ResponseCode' => 'INS-0',
            'output_ThirdPartyReference' => '11114',
        ];

        $response = new Response(200, [], json_encode($fixture, JSON_THROW_ON_ERROR));
        $result = ReversalResult::fromPsrResponse($response);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('REV_4XDF12345', $result->transactionId);
    }

    public function testCallbackWebhookContract(): void
    {
        $fixture = [
            'input_OriginalConversationID' => '241fasif1f1n92fn129nfasnf91nf1',
            'input_ThirdPartyReference' => '11114',
            'input_TransactionID' => '4XDF12345',
            'input_ResultCode' => '0',
            'input_ResultDesc' => 'Request Processed Successfully',
        ];

        $payload = CallbackParser::parse($fixture);

        $this->assertSame('241fasif1f1n92fn129nfasnf91nf1', $payload->originalConversationId);
        $this->assertSame('11114', $payload->thirdPartyReference);
        $this->assertSame('4XDF12345', $payload->transactionId);
        $this->assertTrue($payload->isSuccess());
    }

    public function testErrorContracts(): void
    {
        $errorFixture = [
            'output_ResponseCode' => 'INS-2006',
            'output_ResponseDesc' => 'Insufficient balance',
        ];

        $response = new Response(422, [], json_encode($errorFixture, JSON_THROW_ON_ERROR));
        $result = TransactionResult::fromPsrResponse($response);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(ResponseCode::InsufficientBalance, $result->responseCode);
        $this->assertSame('Insufficient balance', $result->responseDescription);
    }
}
