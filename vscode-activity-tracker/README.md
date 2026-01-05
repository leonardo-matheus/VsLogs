# VS Code Activity Tracker

<p align="center">
  <img src="images/icon.png" alt="Activity Tracker Logo" width="128" height="128">
</p>

<p align="center">
  <strong>📊 Monitore seu tempo de codificação diretamente no VS Code</strong>
</p>

<p align="center">
  <a href="https://marketplace.visualstudio.com/items?itemName=activity-tracker.vscode-activity-tracker">
    <img src="https://img.shields.io/visual-studio-marketplace/v/activity-tracker.vscode-activity-tracker?style=flat-square&logo=visual-studio-code" alt="Version">
  </a>
  <a href="https://marketplace.visualstudio.com/items?itemName=activity-tracker.vscode-activity-tracker">
    <img src="https://img.shields.io/visual-studio-marketplace/i/activity-tracker.vscode-activity-tracker?style=flat-square" alt="Installs">
  </a>
  <a href="https://marketplace.visualstudio.com/items?itemName=activity-tracker.vscode-activity-tracker">
    <img src="https://img.shields.io/visual-studio-marketplace/r/activity-tracker.vscode-activity-tracker?style=flat-square" alt="Rating">
  </a>
</p>

---

## ✨ Funcionalidades

- ⏱️ **Tempo Ativo** - Monitora quanto tempo você está codificando
- 😴 **Detecção de AFK** - Identifica quando você está ausente (5+ min sem atividade)
- 📝 **Linhas Digitadas** - Conta quantas linhas você escreveu
- 🎨 **Linguagens** - Rastreia quais linguagens você mais usa
- 📊 **Dashboard** - Visualize suas métricas em tempo real
- 🔄 **Sincronização** - Envio automático para API/Dashboard
- 💾 **Persistência** - Dados salvos entre sessões

## 📸 Screenshots

### Status Bar
![Status Bar](images/statusbar.png)

A extensão mostra na barra de status:
- 💻 quando você está ativo
- 😴 quando está AFK
- Tempo total de codificação do dia

### Dashboard
![Dashboard](images/dashboard.png)

## 🚀 Instalação

### Via Marketplace
1. Abra o VS Code
2. Pressione `Ctrl+Shift+X`
3. Pesquise "Activity Tracker"
4. Clique em **Install**

### Via VSIX
```bash
code --install-extension vscode-activity-tracker.vsix
```

## ⚙️ Configuração

Abra as configurações (`Ctrl+,`) e pesquise "Activity Tracker":

| Configuração | Padrão | Descrição |
|--------------|--------|-----------|
| `activityTracker.apiEndpoint` | `http://localhost:8000/api` | URL da API |
| `activityTracker.afkTimeout` | `300` | Segundos para considerar AFK |
| `activityTracker.syncInterval` | `30` | Intervalo de sincronização (segundos) |

### Exemplo de settings.json
```json
{
  "activityTracker.apiEndpoint": "http://localhost:8000/api",
  "activityTracker.afkTimeout": 300,
  "activityTracker.syncInterval": 30
}
```

## 🎮 Comandos

| Comando | Descrição |
|---------|-----------|
| `Activity Tracker: Show Status` | Mostra tempo ativo e AFK |
| `Activity Tracker: Open Dashboard` | Abre o dashboard no navegador |

Acesse via `Ctrl+Shift+P` e digite "Activity Tracker"

## 📊 Dashboard

Para visualizar suas métricas em um dashboard completo:

1. **Inicie a API:**
```bash
cd api
php -S localhost:8000 router.php
```

2. **Acesse o dashboard:**
```
http://localhost:8000/dashboard.html
```

Ou use o comando `Activity Tracker: Open Dashboard`

## 🔧 Como Funciona

1. **Monitoramento** - A extensão detecta atividade no editor:
   - Digitação
   - Seleção de texto
   - Troca de arquivos
   - Uso do terminal

2. **Detecção de AFK** - Após 5 minutos sem atividade, marca como ausente

3. **Sincronização** - A cada 30 segundos, envia dados para a API

4. **Persistência** - Dados são salvos localmente e resetam a cada dia

## 📈 Métricas Coletadas

- **Tempo ativo** - Segundos codificando
- **Tempo AFK** - Segundos ausente
- **Linhas digitadas** - Quantidade de linhas modificadas
- **Linguagens** - Contagem de uso por linguagem
- **Atividade por hora** - Distribuição ao longo do dia
- **Workspace** - Projeto atual

## 🔒 Privacidade

- ✅ Dados ficam no **seu servidor local**
- ✅ Nenhum dado é enviado para terceiros
- ✅ Você controla totalmente seus dados
- ✅ Código 100% open source

## 🐛 Problemas Conhecidos

- A contagem de linhas pode variar dependendo do tipo de edição
- Auto-save muito frequente pode gerar mais linhas que o esperado

## 📝 Changelog

### 1.0.0
- 🎉 Lançamento inicial
- ⏱️ Monitoramento de tempo ativo/AFK
- 📝 Contagem de linhas digitadas
- 🎨 Rastreamento de linguagens
- 📊 Integração com dashboard

## 🤝 Contribuindo

1. Fork o repositório
2. Crie uma branch (`git checkout -b feature/nova-feature`)
3. Commit suas mudanças (`git commit -m 'Add nova feature'`)
4. Push para a branch (`git push origin feature/nova-feature`)
5. Abra um Pull Request

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

---

<p align="center">
  Feito com ❤️ para desenvolvedores
</p>

<p align="center">
  <a href="https://github.com/seu-usuario/activity-tracker/issues">Reportar Bug</a>
  ·
  <a href="https://github.com/seu-usuario/activity-tracker/issues">Solicitar Feature</a>
</p>
