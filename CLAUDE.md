# Projeto: Grupo FixIT Payments - SDK grupofixit/mpesa
Proprietário: Grupo FixIT Limitada. Código proprietário (privado). Todos os créditos: Grupo FixIT.

## Fonte de verdade
- O plano em `docs/PLANO.md` é a especificação. Segue-o. Se algo não estiver claro ou o contradisser, PARA e pergunta; não inventes.
- A documentação oficial da Vodacom M-Pesa (https://developer.mpesa.vm.co.mz/) manda sobre qualquer suposição. Não copies código de SDKs de terceiros.
- Onde a documentação for ambígua, marca `TODO(VODACOM-DOC): <pergunta>` e trata o caso de forma conservadora (desconhecido → verificar por Status Query).

## Regras inegociáveis
1. Nunca pedir, receber ou guardar o PIN do cliente.
2. TLS sempre verificado. Nunca `verify=false`.
3. Operações que movem dinheiro (C2B, B2C, B2B, Reversal) NUNCA têm retry automático. Em timeout/ambiguidade: `OutcomeUnknownException` e Status Query.
4. Nenhum pagamento passa a estado final sem confirmação por Transaction Status Query. Callbacks e respostas síncronas são apenas sinais.
5. Valores monetários vêm do backend da app, nunca de URLs/queries do cliente. Usar decimal, nunca float.
6. Segredos nunca em logs, respostas, commits ou fixtures; redigir sempre (`Redactor`).
7. Tudo é armazenado e auditável (pedidos, respostas brutas mascaradas, eventos, webhooks).
8. Idempotência obrigatória em tudo o que cria pagamentos.
9. Sandbox e live nunca se misturam (`environment` em tudo).

## Qualidade
- PHP 8.3, `declare(strict_types=1)`, classes `readonly`, enums, tipos estritos.
- PHPStan nível máximo, Pint, testes (PHPUnit). Cobertura ≥ 90 % em domínio.
- Dependências mínimas; `composer.lock` versionado; `composer audit` limpo.
- Commits pequenos e convencionais; uma tarefa por vez com ID no título (ex.: `S-01`).

## Forma de trabalhar
- Antes de codar cada tarefa: resume o que vais fazer em 3 a 6 linhas e lista os ficheiros que vais criar/alterar.
- Escreve os testes primeiro (ou em conjunto) e corre-os. Mostra o resultado.
- No fim de cada tarefa: critérios de aceitação cumpridos? (lista), o que ficou por fazer, riscos/dúvidas.
- Responde em português (PT-MZ/PT); identificadores de código em inglês.
