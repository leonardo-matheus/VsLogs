#!/bin/bash
# ============================================
# Deploy Script - VsLogs API
# Execute: bash deploy.sh
# ============================================

set -e

APP_DIR="/var/www/vslogs-api"
BRANCH="main"

echo "🚀 Iniciando deploy da VsLogs API..."

cd ${APP_DIR}

# Salvar alterações locais se houver
git stash

# Baixar últimas alterações
echo "📥 Baixando atualizações do GitHub..."
git fetch origin
git checkout ${BRANCH}
git pull origin ${BRANCH}

# Restaurar permissões
echo "🔐 Configurando permissões..."
chown -R www-data:www-data ${APP_DIR}
chmod -R 755 ${APP_DIR}
chmod -R 775 ${APP_DIR}/api/data

# Reiniciar PHP-FPM
echo "🔄 Reiniciando PHP-FPM..."
systemctl restart php8.2-fpm

# Limpar cache do OPcache (se habilitado)
if command -v php &> /dev/null; then
    php -r "if(function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true
fi

echo "✅ Deploy concluído com sucesso!"
echo "📊 Dashboard: http://191.235.32.212/dashboard"
