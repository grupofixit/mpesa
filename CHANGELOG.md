# Changelog

Todas as alterações notáveis na SDK `grupofixit/mpesa` serão documentadas neste ficheiro.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e este projeto segue [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-10-26

### Adicionado
- Estrutura base da SDK `grupofixit/mpesa` compatível com PHP 8.3+.
- Suporte a PSR-18 (HTTP Client), PSR-17 (HTTP Factory), PSR-7 (HTTP Message), PSR-3 (Logger) e PSR-16 (Simple Cache).
- Implementação estrita de Enums: `Environment`, `Operation`, `TransactionStatus`, `ResponseCode`.
- Value Objects imutáveis com validação rígida: `Money`, `Msisdn`, `Reference`, `Redactor`.
- Autenticação RSA com encriptação de API Key via `TokenGenerator` e cache via `TokenProvider`.
- Camada de transporte seguro PSR-18 com TLS sempre verificado e sem opção de desativação.
- Suporte completo a operações M-Pesa Moçambique:
  - C2B Single Stage
  - B2C Payment
  - B2B Payment
  - Query Transaction Status
  - Query Customer Name
  - Reversal (total e parcial)
- Exceção `OutcomeUnknownException` para cenários ambíguos ou timeouts em movimentações financeiras.
- Parser e gerador de respostas para Callbacks da Vodacom.
- Utilitários de testes e simulação: `FakeMpesa` e `FakeTransport`.
