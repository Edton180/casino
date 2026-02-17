# AUTO_INSTALLER_GUIDE.md

## Auto Installer v8.0.0

> Criador: **Edton Braz**

Script: `scripts/auto-install.sh`

### Modos
- `vps`
- `docker`
- `cpanel`
- `localhost`
- `virtualbox`
- `diagnose`

### Exemplo não interativo

```bash
sudo bash scripts/auto-install.sh --mode vps --domain casino.com --email admin@casino.com --ssl --non-interactive
```

### O que o modo VPS executa
- Instala pacotes base de produção.
- Clona o projeto em `/opt/casino`.
- Configura dependências PHP/Node.
- Roda build, migrações e storage link.
- Cria lock de instalação.
- Configura cron do Laravel.
