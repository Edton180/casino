# SECURITY.md

## Guia de Segurança de Produção

> Criador: **Edton Braz**

- Obrigatório usar HTTPS.
- Definir `APP_ENV=production`.
- Definir `APP_DEBUG=false`.
- Restringir permissões de escrita apenas para `storage/` e `bootstrap/cache`.
- Ativar firewall (UFW/Cloud Firewall).
- Rotacionar senhas e chaves sensíveis.
- Executar backup diário com retenção.
- Monitorar logs de aplicação e servidor.
