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

# Configurar Nginx
cat > /etc/nginx/sites-available/vslogs-api << 'EOF'
server {
    listen 80;
    server_name 191-235-32-212.nip.io 191.235.32.212;
    root /var/www/vslogs-api/api;
    index index.php;

    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type' always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /dashboard {
        try_files /dashboard.html =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
    }

    location ~ \.(db|sqlite)$ { deny all; }
    location ~ /\. { deny all; }
}
EOF

ln -sf /etc/nginx/sites-available/vslogs-api /etc/nginx/sites-enabled/

# Reiniciar serviços
systemctl restart php8.2-fpm
nginx -t && systemctl reload nginx

echo ""
echo "✅ Deploy concluído!"
echo ""
echo "📊 Dashboard: http://191.235.32.212/dashboard"
echo "🔗 API: http://191.235.32.212/api/"
echo ""
echo "Para SSL: sudo certbot --nginx -d 191-235-32-212.nip.io"
