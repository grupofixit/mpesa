# Arquitetura e Diretrizes de Segurança - SDK `grupofixit/mpesa`

> Grupo FixIT Limitada. Documento confidencial de especificações de segurança.

---

## 1. Princípios Fundamentais

1. **Nunca Coletar o PIN**: O sistema nunca solicita, manipula ou armazena o PIN do cliente. O PIN é inserido unicamente pelo cliente no seu aparelho via USSD Push da operadora Vodacom.
2. **TLS Obrigatório e Imutável**: Todas as conexões com as APIs da Vodacom exigem verificação de certificados TLS (`verify: true`). Não existem opções ou métodos na SDK para desativar a validação TLS.
3. **Redação Automática de Segredos**: Segredos como `apiKey`, `securityCredential`, cabeçalhos `Authorization: Bearer ...` e tokens são automaticamente mascarados via `Redactor::redactArray()` e `Redactor::redactString()`.
4. **Proteção de Dados Pessoais (MSISDN)**: Números de telefone são automaticamente ofuscados em logs e rastreios (ex.: `25884****567`).

---

## 2. Tratamento de Ambiguidades e Quedas de Rede

Operações financeiras que movem fundos (`C2B`, `B2C`, `B2B`, `Reversal`) obedecem a uma política estrita:

- **Zero Retries Automáticos**: Em caso de timeout de rede, erro 502/504 ou desconexão durante o envio de um pedido de cobrança ou estorno, a SDK **NUNCA** reenvia a requisição.
- **OutcomeUnknownException**: A SDK lança uma `OutcomeUnknownException` contendo o `thirdPartyReference`. A aplicação consumidora deve tratar a exceção marcando a transação como `outcome_unconfirmed` e agendando consultas via **Transaction Status Query**.
- **Idempotência**: Todos os pedidos possuem `thirdPartyReference` único gerado pela aplicação chamadora.

---

## 3. Autenticação e Chaves Criptográficas

- A autenticação é realizada via token gerado dinamicamente: a `apiKey` é cifrada com a chave pública RSA da Vodacom (padding `OPENSSL_PKCS1_PADDING`) e codificada em Base64.
- O token pode ser mantido em cache temporário (PSR-16) com TTL configurado (por defeito 55 minutos).
- Respostas `401 Unauthorized` da API invalidam imediatamente o token em cache via `TokenProviderInterface::invalidateToken()`.
