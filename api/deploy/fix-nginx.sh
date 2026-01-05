#!/bin/bash
# ============================================
# Fix Nginx - VsLogs API
# Execute: sudo bash fix-nginx.sh
# ============================================

set -e

echo "🔧 Corrigindo configuração Nginx para VsLogs..."

# Criar snippet de configuração
cat > /etc/nginx/snippets/vslogs.conf << 'SNIPPET'
# VsLogs Dashboard
location = /vslogs {
    root /var/www/vslogs-api/api;
    rewrite ^/vslogs$ /dashboard.html break;
}

location /vslogs/ {
    alias /var/www/vslogs-api/api/;
    index dashboard.html;
    
    # CORS Headers
    add_header 'Access-Control-Allow-Origin' '*' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type' always;

    # VsLogs API endpoints
    location ~ ^/vslogs/api/(.+\.php)$ {
        alias /var/www/vslogs-api/api/api/$1;
        fastcgi_pass unix:/run/php/php-vslogs.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/vslogs-api/api/api/$1;
        include fastcgi_params;
    }
    
    # Block database files
    location ~ \.(db|sqlite)$ {
        deny all;
    }
}
SNIPPET

echo "✅ Configuração criada em /etc/nginx/snippets/vslogs.conf"

# Verificar se já está incluído no site default
if grep -q "include snippets/vslogs.conf" /etc/nginx/sites-available/default 2>/dev/null; then
    echo "✅ Já incluído no site default"
else
    echo ""
    echo "⚠️  Adicione manualmente ao seu site Nginx:"
    echo "   sudo nano /etc/nginx/sites-available/default"
    echo ""
    echo "   Dentro do bloco 'server {' (após listen 443 ssl), adicione:"
    echo "   include snippets/vslogs.conf;"
fi

# Testar e recarregar
nginx -t && systemctl reload nginx

echo ""
echo "🔍 Testando endpoints..."
sleep 1

# Testar localmente
curl -s -o /dev/null -w "Dashboard: %{http_code}\n" http://localhost/vslogs || echo "Dashboard: erro"
curl -s -o /dev/null -w "API: %{http_code}\n" http://localhost/vslogs/api/stats.php || echo "API: erro"

echo ""
echo "📊 URLs:"
echo "   Dashboard: https://191-235-32-212.nip.io/vslogs"
echo "   API: https://191-235-32-212.nip.io/vslogs/api/stats.php"
