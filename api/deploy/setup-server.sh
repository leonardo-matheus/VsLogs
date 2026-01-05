#!/bin/bash
# ============================================
# Setup Script para VPS Debian - VsLogs API
# Execute como root: sudo bash setup-server.sh
# ============================================

set -e

echo "=========================================="
echo "  VsLogs API - Setup do Servidor"
echo "=========================================="

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variáveis
APP_DIR="/var/www/vslogs-api"
REPO_URL="https://github.com/leonardo-matheus/VsLogs.git"
DOMAIN="191-235-32-212.nip.io"
PHP_VERSION="8.2"

echo -e "${YELLOW}[1/7] Atualizando sistema...${NC}"
apt update && apt upgrade -y

echo -e "${YELLOW}[2/7] Instalando dependências...${NC}"
apt install -y curl git unzip software-properties-common apt-transport-https ca-certificates

echo -e "${YELLOW}[3/7] Instalando PHP ${PHP_VERSION}...${NC}"
# Adicionar repositório Sury para PHP mais recente
curl -sSL https://packages.sury.org/php/README.txt | bash -x

apt update
apt install -y php${PHP_VERSION}-fpm php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-cli php${PHP_VERSION}-common php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl

# Verificar instalação
php -v
echo -e "${GREEN}PHP instalado com sucesso!${NC}"

echo -e "${YELLOW}[4/7] Configurando PHP-FPM...${NC}"
# Configurar PHP-FPM para melhor performance
cat > /etc/php/${PHP_VERSION}/fpm/pool.d/vslogs.conf << 'PHPFPM'
[vslogs]
user = www-data
group = www-data
listen = /run/php/php-vslogs.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php-vslogs-error.log
php_admin_flag[log_errors] = on
PHPFPM

systemctl restart php${PHP_VERSION}-fpm
systemctl enable php${PHP_VERSION}-fpm

echo -e "${YELLOW}[5/7] Clonando repositório...${NC}"
# Criar diretório e clonar
mkdir -p ${APP_DIR}
if [ -d "${APP_DIR}/.git" ]; then
    cd ${APP_DIR}
    git pull origin main
else
    git clone ${REPO_URL} ${APP_DIR}
fi

# Copiar apenas a pasta api
cd ${APP_DIR}

# Criar diretório para o banco de dados
mkdir -p ${APP_DIR}/api/data
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/api/data

echo -e "${YELLOW}[6/7] Configurando Nginx...${NC}"
cat > /etc/nginx/sites-available/vslogs-api << 'NGINX'
server {
    listen 80;
    server_name 191-235-32-212.nip.io 191.235.32.212;

    # Redirecionar para HTTPS (opcional, descomentar se tiver SSL)
    # return 301 https://$server_name$request_uri;

    root /var/www/vslogs-api/api;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/vslogs-api-access.log;
    error_log /var/log/nginx/vslogs-api-error.log;

    # CORS Headers
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization' always;

    # Handle OPTIONS preflight
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization';
        add_header 'Content-Length' 0;
        add_header 'Content-Type' 'text/plain';
        return 204;
    }

    # API Routes
    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    # Dashboard
    location /dashboard {
        alias /var/www/vslogs-api/api;
        try_files /dashboard.html =404;
    }

    # PHP Processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Deny access to database
    location ~ \.db$ {
        deny all;
    }
}

# HTTPS Server (SSL via nip.io - usando certbot)
server {
    listen 443 ssl http2;
    server_name 191-235-32-212.nip.io;

    # SSL será configurado pelo certbot
    # ssl_certificate /etc/letsencrypt/live/191-235-32-212.nip.io/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/191-235-32-212.nip.io/privkey.pem;

    root /var/www/vslogs-api/api;
    index index.php index.html;

    access_log /var/log/nginx/vslogs-api-ssl-access.log;
    error_log /var/log/nginx/vslogs-api-ssl-error.log;

    # CORS Headers
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization' always;

    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization';
        add_header 'Content-Length' 0;
        add_header 'Content-Type' 'text/plain';
        return 204;
    }

    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    location /dashboard {
        alias /var/www/vslogs-api/api;
        try_files /dashboard.html =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~ \.db$ {
        deny all;
    }
}
NGINX

# Ativar site
ln -sf /etc/nginx/sites-available/vslogs-api /etc/nginx/sites-enabled/

# Testar configuração
nginx -t

# Recarregar Nginx
systemctl reload nginx

echo -e "${YELLOW}[7/7] Configurando SSL com Certbot (opcional)...${NC}"
# Instalar certbot se não existir
if ! command -v certbot &> /dev/null; then
    apt install -y certbot python3-certbot-nginx
fi

echo -e "${GREEN}=========================================="
echo "  Setup Completo!"
echo "==========================================${NC}"
echo ""
echo "API URL: http://191.235.32.212/api/"
echo "Dashboard: http://191.235.32.212/dashboard"
echo ""
echo "Para configurar SSL, execute:"
echo "  sudo certbot --nginx -d 191-235-32-212.nip.io"
echo ""
echo "Para fazer deploy manual:"
echo "  cd ${APP_DIR} && git pull origin main"
echo ""
