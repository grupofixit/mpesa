# Notas Técnicas da API Vodacom M-Pesa Moçambique

> Documento de referência interna para integração e manutenção da SDK `grupofixit/mpesa`.

---

## 1. Ambientes e Hosts

| Ambiente | Host | Origem (`Origin` Header) |
|---|---|---|
| **Sandbox** | `https://api.sandbox.vm.co.mz` | `developer.mpesa.vm.co.mz` |
| **Produção (Live)** | `https://api.vm.co.mz` | `developer.mpesa.vm.co.mz` |

---

## 2. Portas, Métodos e Caminhos por Operação

| Operação | Método | Porta | Caminho |
|---|---|---|---|
| **C2B Single Stage** | `POST` | 18352 | `/ipg/v1x/c2bPayment/singleStage/` |
| **B2C Payment** | `POST` | 18345 | `/ipg/v1x/b2cPayment/` |
| **B2B Payment** | `POST` | 18349 | `/ipg/v1x/b2bPayment/` |
| **Transaction Status Query** | `GET` | 18353 | `/ipg/v1x/queryTransactionStatus/` |
| **Reversal / Refund** | `PUT` | 18354 | `/ipg/v1x/reversal/` |
| **Customer Name Query** | `GET` | 19323 | `/ipg/v1x/queryCustomerName/` |

---

## 3. Especificidades Operacionais

- **Tempo Limite USSD Push**: A janela oficial para o cliente digitar o PIN no telemóvel é de **60 segundos**. Após esse período, a transação expira no M-Pesa.
- **Prefixos MSISDN Válidos**: Apenas números da rede Vodacom Moçambique com prefixos `84` e `85` (formato normalizado `25884xxxxxxx` e `25885xxxxxxx`).
- **Comprimento de Referências**:
  - `TransactionReference`: Entre 1 e 20 caracteres alfanuméricos (`A-Za-z0-9`). Exceder 20 caracteres resulta no erro `INS-17`.
  - `ThirdPartyReference`: Identificador único no sistema terceiro.
- **Formato de Callback de Entrada (Webhook)**:
  - Enviado via POST ao URL configurado no portal M-Pesa.
  - Campos recebidos: `input_OriginalConversationID`, `input_ThirdPartyReference`, `input_TransactionID`, `input_ResultCode`, `input_ResultDesc`.
  - Formato de confirmação exigido pela Vodacom:
    ```json
    {
      "output_OriginalConversationID": "...",
      "output_ResponseDesc": "Successfully Accepted Result",
      "output_ResponseCode": "0",
      "output_ThirdPartyConversationID": "..."
    }
    ```
