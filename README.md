# Grupo FixIT M-Pesa PHP SDK (`grupofixit/mpesa`)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/grupofixit/mpesa.svg?style=flat-square)](https://packagist.org/packages/grupofixit/mpesa)
[![Software License](https://img.shields.io/badge/license-proprietary-blue.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-8892BF.svg?style=flat-square)](https://php.net)
[![Total Downloads](https://img.shields.io/packagist/dt/grupofixit/mpesa.svg?style=flat-square)](https://packagist.org/packages/grupofixit/mpesa)

SDK oficial e independente de framework do **Grupo FixIT Limitada** para integração segura e de alta confiabilidade com a API **Vodacom M-Pesa Moçambique** (`api.sandbox.vm.co.mz` e `api.vm.co.mz`).

---

## 🌟 Características Principais

- **PHP 8.3+ Nativo**: Desenvolvido com `declare(strict_types=1)`, classes `readonly`, tipos estritos e Enums nativos.
- **Conformidade Estrita com Padrões PSR**:
  - `PSR-18` (HTTP Client) e `PSR-17` (HTTP Factory)
  - `PSR-7` (HTTP Messages)
  - `PSR-3` (Logger com mascaramento automático de credenciais)
  - `PSR-16` (Simple Cache para armazenamento e reutilização de tokens)
- **Segurança de Nível Bancário**:
  - **TLS Sempre Verificado**: Impossível desativar a verificação de certificados SSL/TLS por configuração.
  - **Isolamento de PIN**: O sistema nunca solicita, manipula ou armazena o PIN do cliente (autorização via USSD Push no telemóvel do utilizador).
  - **Redação Automática de Segredos**: `apiKey`, `securityCredential`, tokens Bearer e números de telefone são automaticamente ofuscados em logs e rastreios (`Redactor`).
- **Consistência Financeira**:
  - **Zero Retries Automáticos**: Falhas de rede ou timeouts em movimentações de fundos lançam `OutcomeUnknownException` com a referência única para resolução obrigatória via **Transaction Status Query**.
- **Ferramentas de Teste Integradas**:
  - Inclui `FakeMpesa` e `FakeTransport` prontos para testes unitários nas suas aplicações sem necessidade de chamadas externas de rede.

---

## 📋 Requisitos

- **PHP**: `^8.3`
- **Extensões**: `ext-openssl`, `ext-json`
- **Implementação HTTP PSR-18/PSR-17** (ex.: `guzzlehttp/guzzle` e `guzzlehttp/psr7`)

---

## 🚀 Instalação

Instale a biblioteca via Composer:

```bash
composer require grupofixit/mpesa
```

---

## ⚙️ Configuração Inicial

```php
use GrupoFixIT\Mpesa\MpesaClient;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Enums\Environment;

$config = new MpesaConfig(
    environment: Environment::Sandbox, // ou Environment::Live
    credentials: new Credentials(
        apiKey: 'sua-api-key',
        publicKey: 'sua-chave-publica-rsa-ou-pem',
        serviceProviderCode: '171717',
        initiatorIdentifier: 'IdentificadorOpcionalParaReversal',
        securityCredential: 'SenhaCifradaOpcionalParaReversal'
    ),
    timeout: 30.0,
);

$mpesa = MpesaClient::create($config);
```

---

## 💡 Exemplos de Uso

### 1. Cobrança a Cliente (C2B Single Stage)

Dispara um pedido de pagamento via **USSD Push** para o telemóvel do cliente (janela de 60 segundos para inserção do PIN).

```php
use GrupoFixIT\Mpesa\Requests\C2BRequest;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;
use GrupoFixIT\Mpesa\Exceptions\OutcomeUnknownException;
use GrupoFixIT\Mpesa\Exceptions\ApiException;

try {
    $request = new C2BRequest(
        transactionReference: Reference::generate(12, 'TRX'), // Máx. 20 caracteres
        customerMsisdn: Msisdn::from('258841234567'), // 84 ou 85 (12 dígitos)
        amount: Money::mzn('150.00'), // Decimal protegido
        thirdPartyReference: Reference::generate(12, 'REF')
    );

    $result = $mpesa->c2b($request);

    if ($result->isSuccess()) {
        echo "Pedido aceite pela Vodacom. Conversation ID: " . $result->conversationId;
        // Agendar verificação via Status Query
    }
} catch (OutcomeUnknownException $e) {
    // REGRA DE OURO: OCORREU TIMEOUT / FALHA DE REDE. NUNCA REENVIE O C2B!
    // Consulte o estado da transação usando o thirdPartyReference retornado:
    $ref = $e->getThirdPartyReference();
    // Agendar job de Status Query
} catch (ApiException $e) {
    echo "Erro retornado pela Vodacom: " . $e->getMessage();
}
```

---

### 2. Consulta de Estado de Transação (Status Query)

Utilizado para confirmar o estado final de qualquer transação (resposta oficial da Vodacom).

```php
use GrupoFixIT\Mpesa\Requests\TransactionStatusRequest;

$request = new TransactionStatusRequest(
    thirdPartyReference: 'REF_ORIGINAL',
    queryReference: 'CONVERSATION_ID_OU_TRANSACTION_ID'
);

$statusResult = $mpesa->transactionStatus($request);

if ($statusResult->isSuccess()) {
    echo "Estado da transação: " . $statusResult->transactionStatus->value; // Completed, Failed, Pending...
    if ($statusResult->transactionStatus->isTerminal()) {
        // Atualizar base de dados com estado final
    }
}
```

---

### 3. Consulta de Nome do Cliente (Customer Name Query)

Permite obter o nome ofuscado do titular antes de efetuar a cobrança para confirmação visual do utilizador.

```php
use GrupoFixIT\Mpesa\Requests\CustomerNameRequest;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;

$request = new CustomerNameRequest(
    customerMsisdn: Msisdn::from('258841234567'),
    thirdPartyReference: Reference::generate()
);

$nameResult = $mpesa->customerName($request);

if ($nameResult->isSuccess()) {
    echo "Nome do Cliente: " . $nameResult->customerName; // Ex: Jo** Sil**
}
```

---

### 4. Pagamento a Cliente (B2C) e Entre Empresas (B2B)

```php
use GrupoFixIT\Mpesa\Requests\B2CRequest;
use GrupoFixIT\Mpesa\Requests\B2BRequest;

// B2C
$b2cResult = $mpesa->b2c(new B2CRequest(
    customerMsisdn: Msisdn::from('258841234567'),
    amount: Money::mzn('250.00'),
    transactionReference: Reference::generate(),
    thirdPartyReference: Reference::generate()
));

// B2B
$b2bResult = $mpesa->b2b(new B2BRequest(
    transactionReference: Reference::generate(),
    amount: Money::mzn('5000.00'),
    thirdPartyReference: Reference::generate(),
    receiverPartyCode: '979797' // Shortcode da empresa destinatária
));
```

---

### 5. Estorno / Reembolso (Reversal)

Suporta estorno total ou parcial de pagamentos concluídos.

```php
use GrupoFixIT\Mpesa\Requests\ReversalRequest;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Reference;

$reversalResult = $mpesa->reversal(new ReversalRequest(
    transactionId: 'ORIGINAL_TRANSACTION_ID',
    thirdPartyReference: Reference::generate(),
    reversalAmount: Money::mzn('50.00') // Opcional para estorno parcial; se omitido, estorna o total
));
```

---

### 6. Processamento de Webhooks (Callback)

Parser rigoroso para callbacks assíncronos enviados pelos servidores da Vodacom.

```php
// No seu controlador de webhook
$rawJson = file_get_contents('php://input');

// 1. Fazer o parse seguro
$callbackPayload = $mpesa->parseCallback($rawJson);

// 2. Gerar a resposta de confirmação no formato oficial exigido pela Vodacom
$acknowledgement = $mpesa->acknowledgeCallback($callbackPayload);

header('Content-Type: application/json');
echo json_encode($acknowledgement->toArray());

// 3. Regra de Ouro: Agendar Status Query para confirmar o estado antes de alterar saldos
```

---

## 🧪 Testes Unitários com `FakeMpesa`

Para testar as aplicações consumidoras sem disparar chamadas de rede à Vodacom:

```php
use GrupoFixIT\Mpesa\Testing\FakeMpesa;

$fake = FakeMpesa::create();

// Simular sucesso no C2B
$fake->fakeC2BSuccess('CONV_123', 'TRX_999', 'REF_001');

$client = $fake->getClient();
$res = $client->c2b($request);

$this->assertTrue($res->isSuccess());
$this->assertSame('CONV_123', $res->conversationId);
```

---

## 🛡️ Segurança

Se descobrir alguma vulnerabilidade de segurança, envie um relatório confidencial para **security@grupofixit.co.mz**.

---

## 📄 Licença

Proprietária. © **Grupo FixIT Limitada**. Todos os direitos reservados.
