# ============================================
# INSTRUÇÕES DE DEPLOY - VsLogs API
# ============================================

## 📋 Pré-requisitos no Servidor

Servidor VPS Debian com:
- IP: 191.235.32.212
- Nginx instalado
- Acesso SSH

---

## 🚀 Opção 1: Deploy Manual (Primeira vez)

### 1. Copie o script de setup para o servidor:

```bash
scp api/deploy/setup-server.sh usuario@191.235.32.212:/tmp/
```

### 2. Conecte via SSH e execute:

```bash
ssh usuario@191.235.32.212
sudo bash /tmp/setup-server.sh
```

O script irá:
- Instalar PHP 8.2 + extensões
- Configurar PHP-FPM
- Clonar o repositório
- Configurar Nginx
- Configurar permissões

### 3. (Opcional) Configurar SSL:

```bash
sudo certbot --nginx -d 191-235-32-212.nip.io
```

---

## 🔄 Opção 2: Deploy Automático via GitHub Actions

### 1. Crie uma chave SSH para deploy:

```bash
# No seu computador local
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
```

### 2. Adicione a chave pública ao servidor:

```bash
# Copiar chave pública
cat ~/.ssh/github_deploy.pub

# No servidor, adicione ao authorized_keys
ssh usuario@191.235.32.212
echo "CHAVE_PUBLICA_AQUI" >> ~/.ssh/authorized_keys
```

### 3. Configure os Secrets no GitHub:

Vá em: `Settings > Secrets and variables > Actions > New repository secret`

Adicione:
- `VPS_USER`: seu usuário SSH (ex: root ou deploy)
- `VPS_SSH_KEY`: conteúdo do arquivo `~/.ssh/github_deploy` (chave privada)

### 4. Faça um push para testar:

```bash
git add .
git commit -m "Deploy automático"
git push origin main
```

O GitHub Actions irá automaticamente fazer deploy quando houver alterações na pasta `api/`.

---

## 🔗 Opção 3: Deploy via Webhook

### 1. Configure o webhook no GitHub:

- Vá em `Settings > Webhooks > Add webhook`
- Payload URL: `https://191-235-32-212.nip.io/api/webhook.php`
- Content type: `application/json`
- Secret: defina uma senha segura
- Events: `Just the push event`

### 2. Edite o arquivo `webhook.php` no servidor:

```bash
sudo nano /var/www/vslogs-api/api/webhook.php
# Altere: $secret = 'seu_secret_aqui';
```

### 3. Configure permissões para www-data executar deploy:

```bash
sudo visudo
# Adicione:
www-data ALL=(ALL) NOPASSWD: /var/www/vslogs-api/api/deploy/deploy.sh
```

---

## 📊 URLs Finais

| Recurso | URL |
|---------|-----|
| API (HTTP) | http://191.235.32.212/api/ |
| API (HTTPS) | https://191-235-32-212.nip.io/api/ |
| Dashboard | http://191.235.32.212/dashboard |
| Activity Endpoint | POST /api/activity |
| Stats Endpoint | GET /api/stats |

---

## 🔧 Comandos Úteis no Servidor

```bash
# Ver logs do Nginx
sudo tail -f /var/log/nginx/vslogs-api-error.log

# Ver logs do PHP
sudo tail -f /var/log/php-vslogs-error.log

# Reiniciar serviços
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# Deploy manual
cd /var/www/vslogs-api && sudo bash api/deploy/deploy.sh

# Testar API
curl http://localhost/api/
curl http://localhost/api/stats

# Ver status do banco
ls -la /var/www/vslogs-api/api/data/
```

---

## ⚙️ Configuração da Extensão VS Code

Após o deploy, atualize a configuração da extensão para usar o servidor:

```json
{
  "activityTracker.apiEndpoint": "https://191-235-32-212.nip.io/api/activity"
}
```

Ou via HTTP:
```json
{
  "activityTracker.apiEndpoint": "http://191.235.32.212/api/activity"
}
```
