# 🚀 Activity Tracker API & Dashboard

Sistema completo de monitoramento de atividade de desenvolvedores com API PHP 8 e Dashboard em tempo real.

![Dashboard Preview](screenshots/dashboard.png)

## ✨ Funcionalidades

- 📊 **Dashboard em Tempo Real** - Visualize sua atividade de codificação ao vivo
- ⏱️ **Tempo Ativo/AFK** - Monitora tempo produtivo vs tempo ausente
- 📝 **Linhas Digitadas** - Conta linhas de código por projeto
- 🎨 **Linguagens** - Estatísticas de uso de linguagens de programação
- 📁 **Projetos** - Métricas por workspace/projeto
- 📈 **Histórico** - Relatórios diários, semanais e mensais
- ⏰ **Atividade por Hora** - Gráfico de produtividade ao longo do dia

## 🛠️ Tecnologias

- **Backend:** PHP 8.3
- **Banco de Dados:** SQLite
- **Frontend:** HTML5, CSS3, JavaScript
- **Gráficos:** Chart.js

## 📦 Instalação

### Requisitos
- PHP 8.0 ou superior
- Extensões: `pdo_sqlite`, `sqlite3`

### Passo a Passo

1. **Clone o repositório**
```bash
git clone https://github.com/seu-usuario/activity-tracker.git
cd activity-tracker/api
```

2. **Inicie o servidor**
```bash
php -S localhost:8000 router.php
```

3. **Acesse o dashboard**
```
http://localhost:8000/dashboard.html
```

## 📡 API Endpoints

### POST `/api/activity.php`
Registra atividade do desenvolvedor.

**Request Body:**
```json
{
  "user_id": "user_123",
  "session_id": "session_abc",
  "active_time": 3600,
  "afk_time": 300,
  "is_active": true,
  "workspace": "meu-projeto",
  "lines_typed": 150,
  "languages": {"typescript": 80, "javascript": 20},
  "hourly_activity": {"09": 50, "10": 80, "11": 45}
}
```

### GET `/api/stats.php`
Retorna estatísticas.

**Parâmetros:**
| Parâmetro | Valores | Descrição |
|-----------|---------|-----------|
| `period` | `today`, `week`, `month`, `realtime` | Período das estatísticas |
| `user_id` | string | Filtrar por usuário (opcional) |

**Exemplo:**
```bash
curl http://localhost:8000/api/stats.php?period=today
```

**Response:**
```json
{
  "success": true,
  "period": "today",
  "data": {
    "summary": {
      "total_active_time": 7200,
      "total_afk_time": 600,
      "total_lines_typed": 450
    },
    "hourly": [...],
    "languages": {"typescript": 60, "python": 25, "css": 15},
    "projects": [...]
  }
}
```

## 🗂️ Estrutura do Projeto

```
api/
├── api/
│   ├── activity.php    # Endpoint de atividade
│   └── stats.php       # Endpoint de estatísticas
├── data/
│   └── activity.db     # Banco SQLite (auto-gerado)
├── database.php        # Conexão e schema
├── router.php          # Roteador do servidor
├── dashboard.html      # Interface web
└── README.md
```

## 🎨 Screenshots

### Dashboard Principal
![Dashboard](screenshots/dashboard.png)

### Gráfico de Linguagens
![Languages](screenshots/languages.png)

### Atividade por Hora
![Hourly](screenshots/hourly.png)

## 🔧 Configuração

O banco de dados SQLite é criado automaticamente em `data/activity.db`.

Para resetar os dados:
```bash
rm data/activity.db
```

## 🤝 Integração

Este backend foi projetado para funcionar com a extensão **VS Code Activity Tracker**:
- [VS Code Activity Tracker Extension](../vscode-activity-tracker/)

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

---

Desenvolvido com ❤️ para desenvolvedores que querem entender sua produtividade.
