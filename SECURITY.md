# Política de Segurança - Grupo FixIT Mpesa SDK

## Divulgação Responsável
A segurança dos dados e das movimentações financeiras é a prioridade máxima do Grupo FixIT.
Se descobrir qualquer vulnerabilidade de segurança nesta SDK ou nos serviços associados, por favor envie um relatório confidencial para a equipa de segurança do Grupo FixIT:

- **Email**: `security@grupofixit.co.mz`

Não abra issues públicas para problemas de segurança.

## Princípios de Segurança da SDK
1. **Nunca coletar nem guardar o PIN do cliente**: O PIN é digitado exclusivamente no telemóvel do cliente através de USSD Push da Vodacom.
2. **TLS Obrigatório**: A verificação de certificados SSL/TLS é inegociável e obrigatória. Não existe opção para desativar a verificação de certificados.
3. **Redação de Segredos**: A API Key, a Security Credential e tokens são estritamente redigidos de logs, exceções e rastreios.
4. **Idempotência**: Nenhuma transação financeira é reenviada cegamente. Em caso de timeout ou ambiguidade de rede, lança-se `OutcomeUnknownException` para resolução obrigatória via Status Query.
