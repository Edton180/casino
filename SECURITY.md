# SECURITY.md

## Práticas recomendadas
- Forçar HTTPS + HSTS.
- `APP_DEBUG=false` em produção.
- Não expor `.env`.
- Usar senha forte para super admin.
- Restringir acesso administrativo por IP/VPN quando possível.

## Operação segura
- Backup automático do banco (diário).
- Monitoramento de logs e alertas.
- Atualizações periódicas de dependências.
- Revisão de permissões de pastas sensíveis.
