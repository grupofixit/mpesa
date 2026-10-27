# Catálogo Oficial de Códigos de Resposta M-Pesa Moçambique

> Baseado na documentação oficial da Vodacom M-Pesa Moçambique e implementado em `GrupoFixIT\Mpesa\Enums\ResponseCode`.

| Código | Categoria | Descrição (EN) | Descrição (PT) |
|---|---|---|---|
| `INS-0` | Success | Request processed successfully | Pedido processado com sucesso |
| `INS-1` | Retryable | Internal Error | Erro interno na plataforma M-Pesa |
| `INS-2` | ClientError | Invalid API Key | Chave de API inválida |
| `INS-4` | ClientError | User is not active | Utilizador inativo |
| `INS-5` | FinalFailure | Transaction cancelled by customer | Transação cancelada pelo cliente |
| `INS-6` | FinalFailure | Transaction failed | Transação falhou |
| `INS-9` | NeedsReview | Request timeout | Tempo limite do pedido excedido |
| `INS-10` | FinalFailure | Duplicate Transaction | Transação duplicada |
| `INS-13` | ClientError | Invalid Shortcode Used | Shortcode / ServiceProviderCode inválido |
| `INS-14` | ClientError | Invalid Reference Used | Referência inválida |
| `INS-15` | ClientError | Invalid Amount Used | Valor monetário inválido |
| `INS-16` | Retryable | Unable to handle the request due to temporary overloading | Serviço temporariamente sobrecarregado |
| `INS-17` | ClientError | Invalid Transaction Reference. Length Should Be Between 1 and 20 | Comprimento da TransactionReference deve ser entre 1 e 20 |
| `INS-18` | ClientError | Invalid TransactionID Used | TransactionID inválido |
| `INS-19` | ClientError | Invalid ThirdPartyReference Used | ThirdPartyReference inválida |
| `INS-20` | ClientError | Not All Parameters Provided. Please try again | Parâmetros obrigatórios em falta |
| `INS-21` | ClientError | Parameter validations failed. Please try again | Validação de parâmetros falhou |
| `INS-22` | ClientError | Invalid Operation Type | Tipo de operação inválido |
| `INS-23` | NeedsReview | Unknown Status. Contact M-Pesa Support | Estado desconhecido. Contacte o suporte M-Pesa |
| `INS-24` | ClientError | Invalid InitiatorIdentifier Used | InitiatorIdentifier inválido |
| `INS-25` | ClientError | Invalid SecurityCredential Used | SecurityCredential inválida |
| `INS-26` | ClientError | Not authorized | Não autorizado |
| `INS-993` | ClientError | Direct Debit Missing | Débito direto em falta |
| `INS-994` | ClientError | Direct Debit Already Exists | Débito direto já existente |
| `INS-995` | ClientError | Customer's Profile Has Problems | Perfil do cliente com problemas |
| `INS-996` | ClientError | Customer Account Status Not Active | Conta do cliente não está ativa |
| `INS-997` | ClientError | Linking Transaction Not Found | Transação vinculada não encontrada |
| `INS-998` | ClientError | Invalid Market | Mercado inválido |
| `INS-2001` | ClientError | Initiator authentication error | Erro de autenticação do iniciador |
| `INS-2002` | ClientError | Receiver invalid | Destinatário inválido |
| `INS-2006` | FinalFailure | Insufficient balance | Saldo insuficiente na carteira M-Pesa |
| `INS-2051` | ClientError | MSISDN invalid | Número de telefone (MSISDN) inválido |
| `INS-2057` | ClientError | Language code invalid | Código de idioma inválido |
| `UNKNOWN` | Unknown | Unknown response code | Código desconhecido (dispara Status Query obrigatório) |

---

## Regra de Ouro da SDK

1. Códigos desconhecidos são mapeados para `ResponseCode::Unknown` e **nunca** presumidos como sucesso.
2. Apenas `INS-0` representa sucesso oficial da chamada.
3. Respostas síncronas de sucesso em C2B não fecham transações sem confirmação posterior por **Transaction Status Query**.
