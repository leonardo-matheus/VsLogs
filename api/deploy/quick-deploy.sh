#!/bin/bash
# ============================================
# Quick Deploy - Execute direto no servidor
# curl -sSL https://raw.githubusercontent.com/leonardo-matheus/VsLogs/main/api/deploy/quick-deploy.sh | sudo bash
# ============================================

set -e

echo "🚀 VsLogs API - Quick Deploy"
echo "============================="

# Atualizar sistema
apt update

# Instalar PHP se não existir
if ! command -v php &> /dev/null; then
    echo "📦 Instalando PHP..."
    apt install -y curl git
    curl -sSL https://packages.sury.org/php/README.txt | bash -x
    apt update
    apt install -y php8.2-fpm php8.2-sqlite3 php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl
fi

# Clonar/Atualizar repo
APP_DIR="/var/www/vslogs-api"
if [ ! -d "$APP_DIR" ]; then
    git clone https://github.com/leonardo-matheus/VsLogs.git $APP_DIR
else
    cd $APP_DIR && git pull origin main
fi

# Permissões
mkdir -p $APP_DIR/api/data
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/api/data

# Configurar PHP-FPM pool
cat > /etc/php/8.2/fpm/pool.d/vslogs.conf << 'EOF'
[vslogs]
user = www-data
group = www-data
listen = /run/php/php-vslogs.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
EOF

# Configurar Nginx - Adiciona apenas as rotas /vslogs/ ao servidor existente
cat > /etc/nginx/sites-available/vslogs << 'EOF'
# VsLogs API - Adicionar ao bloco server existente ou incluir este arquivo
# Include no nginx.conf ou no site principal: include /etc/nginx/sites-available/vslogs;

# Para usar como include no server block existente:
# Adicione: include /etc/nginx/sites-available/vslogs;

# Ou copie este conteúdo dentro do seu server block:

# VsLogs Dashboard
location /vslogs {
    alias /var/www/vslogs-api/api;
    try_files /dashboard.html =404;
}

# VsLogs API
location /vslogs/api {
    alias /var/www/vslogs-api/api/api;
    
    # CORS
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type' always;
    
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type';
        return 204;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
}

# Bloquear acesso ao banco de dados
location ~ /vslogs/.*\.(db|sqlite)$ { 
    deny all; 
}
EOF

# Criar arquivo de configuração separado para incluir no site existente
cat > /etc/nginx/snippets/vslogs.conf << 'SNIPPET'
# VsLogs Dashboard
location /vslogs {
    alias /var/www/vslogs-api/api;
    try_files /dashboard.html =404;
}

# VsLogs API
location /vslogs/api {
    alias /var/www/vslogs-api/api/api;
    
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type' always;
    
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'Content-Type';
        return 204;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
    }
}

location ~ /vslogs/.*\.(db|sqlite)$ { deny all; }
SNIPPET

echo ""
echo "📝 Configuração Nginx criada em:"
echo "   /etc/nginx/snippets/vslogs.conf"
echo ""
echo "⚠️  Adicione ao seu site existente:"
echo "   sudo nano /etc/nginx/sites-available/default"
echo "   Dentro do bloco 'server {', adicione:"
echo "   include snippets/vslogs.conf;"
echo ""

# Reiniciar serviços
systemctl restart php8.2-fpm
nginx -t && systemctl reload nginx

echo ""
echo "✅ Deploy concluído!"
echo ""
echo "📊 Dashboard: https://191-235-32-212.nip.io/vslogs"
echo "🔗 API: https://191-235-32-212.nip.io/vslogs/api/"
echo ""
echo "⚠️  IMPORTANTE: Adicione ao seu site Nginx existente:"
echo "   sudo nano /etc/nginx/sites-available/default"
echo "   Dentro do bloco 'server {', adicione a linha:"
echo "   include snippets/vslogs.conf;"
echo ""
echo "   Depois recarregue: sudo nginx -t && sudo systemctl reload nginx"
