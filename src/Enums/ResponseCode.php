<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Enums;

enum ResponseCode: string
{
    case Success = 'INS-0';
    case InternalError = 'INS-1';
    case InvalidApiKey = 'INS-2';
    case UserNotActive = 'INS-4';
    case CancelledByCustomer = 'INS-5';
    case TransactionFailed = 'INS-6';
    case RequestTimeout = 'INS-9';
    case DuplicateTransaction = 'INS-10';
    case InvalidShortcode = 'INS-13';
    case InvalidReference = 'INS-14';
    case InvalidAmount = 'INS-15';
    case ServiceOverloaded = 'INS-16';
    case InvalidTransactionReferenceLength = 'INS-17';
    case InvalidTransactionId = 'INS-18';
    case InvalidThirdPartyReference = 'INS-19';
    case MissingParameters = 'INS-20';
    case ParameterValidationFailed = 'INS-21';
    case InvalidOperationType = 'INS-22';
    case UnknownStatus = 'INS-23';
    case InvalidInitiatorIdentifier = 'INS-24';
    case InvalidSecurityCredential = 'INS-25';
    case NotAuthorized = 'INS-26';
    case DirectDebitMissing = 'INS-993';
    case DirectDebitAlreadyExists = 'INS-994';
    case CustomerProfileProblem = 'INS-995';
    case CustomerAccountNotActive = 'INS-996';
    case LinkingTransactionNotFound = 'INS-997';
    case InvalidMarket = 'INS-998';
    case InitiatorAuthError = 'INS-2001';
    case ReceiverInvalid = 'INS-2002';
    case InsufficientBalance = 'INS-2006';
    case MsisdnInvalid = 'INS-2051';
    case LanguageCodeInvalid = 'INS-2057';
    case Unknown = 'UNKNOWN';

    public function category(): ResponseCodeCategory
    {
        return match ($this) {
            self::Success => ResponseCodeCategory::Success,
            self::InternalError, self::ServiceOverloaded => ResponseCodeCategory::Retryable,
            self::RequestTimeout, self::UnknownStatus => ResponseCodeCategory::NeedsReview,
            self::CancelledByCustomer, self::TransactionFailed, self::DuplicateTransaction, self::InsufficientBalance => ResponseCodeCategory::FinalFailure,
            self::Unknown => ResponseCodeCategory::Unknown,
            default => ResponseCodeCategory::ClientError,
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function descriptionEn(): string
    {
        return match ($this) {
            self::Success => 'Request processed successfully',
            self::InternalError => 'Internal Error',
            self::InvalidApiKey => 'Invalid API Key',
            self::UserNotActive => 'User is not active',
            self::CancelledByCustomer => 'Transaction cancelled by customer',
            self::TransactionFailed => 'Transaction failed',
            self::RequestTimeout => 'Request timeout',
            self::DuplicateTransaction => 'Duplicate Transaction',
            self::InvalidShortcode => 'Invalid Shortcode Used',
            self::InvalidReference => 'Invalid Reference Used',
            self::InvalidAmount => 'Invalid Amount Used',
            self::ServiceOverloaded => 'Unable to handle the request due to temporary overloading',
            self::InvalidTransactionReferenceLength => 'Invalid Transaction Reference. Length Should Be Between 1 and 20',
            self::InvalidTransactionId => 'Invalid TransactionID Used',
            self::InvalidThirdPartyReference => 'Invalid ThirdPartyReference Used',
            self::MissingParameters => 'Not All Parameters Provided. Please try again',
            self::ParameterValidationFailed => 'Parameter validations failed. Please try again',
            self::InvalidOperationType => 'Invalid Operation Type',
            self::UnknownStatus => 'Unknown Status. Contact M-Pesa Support',
            self::InvalidInitiatorIdentifier => 'Invalid InitiatorIdentifier Used',
            self::InvalidSecurityCredential => 'Invalid SecurityCredential Used',
            self::NotAuthorized => 'Not authorized',
            self::DirectDebitMissing => 'Direct Debit Missing',
            self::DirectDebitAlreadyExists => 'Direct Debit Already Exists',
            self::CustomerProfileProblem => "Customer's Profile Has Problems",
            self::CustomerAccountNotActive => 'Customer Account Status Not Active',
            self::LinkingTransactionNotFound => 'Linking Transaction Not Found',
            self::InvalidMarket => 'Invalid Market',
            self::InitiatorAuthError => 'Initiator authentication error',
            self::ReceiverInvalid => 'Receiver invalid',
            self::InsufficientBalance => 'Insufficient balance',
            self::MsisdnInvalid => 'MSISDN invalid',
            self::LanguageCodeInvalid => 'Language code invalid',
            self::Unknown => 'Unknown response code from Vodacom M-Pesa',
        };
    }

    public function descriptionPt(): string
    {
        return match ($this) {
            self::Success => 'Pedido processado com sucesso',
            self::InternalError => 'Erro interno na plataforma M-Pesa',
            self::InvalidApiKey => 'Chave de API inválida',
            self::UserNotActive => 'Utilizador inativo',
            self::CancelledByCustomer => 'Transação cancelada pelo cliente',
            self::TransactionFailed => 'Transação falhou',
            self::RequestTimeout => 'Tempo limite do pedido excedido',
            self::DuplicateTransaction => 'Transação duplicada',
            self::InvalidShortcode => 'Shortcode / ServiceProviderCode inválido',
            self::InvalidReference => 'Referência inválida',
            self::InvalidAmount => 'Valor monetário inválido',
            self::ServiceOverloaded => 'Serviço temporariamente sobrecarregado',
            self::InvalidTransactionReferenceLength => 'Comprimento da TransactionReference deve ser entre 1 e 20',
            self::InvalidTransactionId => 'TransactionID inválido',
            self::InvalidThirdPartyReference => 'ThirdPartyReference inválida',
            self::MissingParameters => 'Parâmetros obrigatórios em falta',
            self::ParameterValidationFailed => 'Validação de parâmetros falhou',
            self::InvalidOperationType => 'Tipo de operação inválido',
            self::UnknownStatus => 'Estado desconhecido. Contacte o suporte M-Pesa',
            self::InvalidInitiatorIdentifier => 'InitiatorIdentifier inválido',
            self::InvalidSecurityCredential => 'SecurityCredential inválida',
            self::NotAuthorized => 'Não autorizado',
            self::DirectDebitMissing => 'Débito direto em falta',
            self::DirectDebitAlreadyExists => 'Débito direto já existente',
            self::CustomerProfileProblem => 'Perfil do cliente com problemas',
            self::CustomerAccountNotActive => 'Conta do cliente não está ativa',
            self::LinkingTransactionNotFound => 'Transação vinculada não encontrada',
            self::InvalidMarket => 'Mercado inválido',
            self::InitiatorAuthError => 'Erro de autenticação do iniciador',
            self::ReceiverInvalid => 'Destinatário inválido',
            self::InsufficientBalance => 'Saldo insuficiente na carteira M-Pesa',
            self::MsisdnInvalid => 'Número de telefone (MSISDN) inválido',
            self::LanguageCodeInvalid => 'Código de idioma inválido',
            self::Unknown => 'Código de resposta desconhecido da Vodacom M-Pesa',
        };
    }

    /**
     * Mapeia string recebida da Vodacom de forma estritamente segura.
     * Códigos não reconhecidos NUNCA são presumidos como sucesso.
     */
    public static function fromRaw(?string $code): self
    {
        if ($code === null || trim($code) === '') {
            return self::Unknown;
        }

        $trimmed = trim($code);

        return self::tryFrom($trimmed) ?? self::Unknown;
    }
}
