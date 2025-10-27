const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(cors());
app.use(bodyParser.json({ limit: '10mb' }));
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static('public'));

// Armazenar webhooks recebidos
let webhooks = [];

// Função para salvar webhook
function saveWebhook(endpoint, data, headers) {
    const webhook = {
        id: Date.now(),
        timestamp: new Date().toISOString(),
        endpoint: endpoint,
        method: 'POST',
        headers: headers,
        body: data,
        ip: headers['x-forwarded-for'] || headers['x-real-ip'] || 'unknown'
    };
    
    webhooks.unshift(webhook); // Adicionar no início
    
    // Manter apenas os últimos 100 webhooks
    if (webhooks.length > 100) {
        webhooks = webhooks.slice(0, 100);
    }
    
    console.log(`📨 Webhook recebido em ${endpoint}:`, JSON.stringify(data, null, 2));
    
    return webhook;
}

// Rota principal - página de teste
app.get('/', (req, res) => {
    const html = `
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GrowHub Webhook Tester</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                margin: 0;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                overflow: hidden;
            }
            .header {
                background: #2c3e50;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0;
                font-size: 2.5em;
            }
            .header p {
                margin: 10px 0 0 0;
                opacity: 0.8;
            }
            .content {
                padding: 20px;
            }
            .section {
                margin-bottom: 30px;
                padding: 20px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                background: #f9f9f9;
            }
            .section h2 {
                margin-top: 0;
                color: #2c3e50;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
            }
            .endpoint {
                background: #2c3e50;
                color: white;
                padding: 10px;
                border-radius: 5px;
                font-family: monospace;
                margin: 10px 0;
                word-break: break-all;
            }
            .webhook-list {
                max-height: 400px;
                overflow-y: auto;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .webhook-item {
                border-bottom: 1px solid #eee;
                padding: 15px;
                cursor: pointer;
                transition: background 0.2s;
            }
            .webhook-item:hover {
                background: #f0f0f0;
            }
            .webhook-item:last-child {
                border-bottom: none;
            }
            .webhook-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .webhook-time {
                color: #666;
                font-size: 0.9em;
            }
            .webhook-endpoint {
                background: #3498db;
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 0.8em;
            }
            .webhook-preview {
                background: #f8f8f8;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 0.9em;
                white-space: pre-wrap;
                max-height: 100px;
                overflow: hidden;
            }
            .clear-btn {
                background: #e74c3c;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1em;
            }
            .clear-btn:hover {
                background: #c0392b;
            }
            .status {
                background: #27ae60;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 0.8em;
                margin-left: 10px;
            }
            .no-webhooks {
                text-align: center;
                color: #666;
                padding: 40px;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚀 GrowHub Webhook Tester</h1>
                <p>Servidor para testar webhooks do sistema GrowHub</p>
            </div>
            <div class="content">
                <div class="section">
                    <h2>📡 Endpoints Disponíveis</h2>
                    <p>Use estes endpoints para configurar seus webhooks:</p>
                    <div class="endpoint">POST http://localhost:${PORT}/webhook/1</div>
                    <div class="endpoint">POST http://localhost:${PORT}/webhook/2</div>
                    <div class="endpoint">POST http://localhost:${PORT}/webhook/3</div>
                    <div class="endpoint">POST http://localhost:${PORT}/webhook/4</div>
                    <div class="endpoint">POST http://localhost:${PORT}/webhook/5</div>
                    <p><strong>Dica:</strong> Você pode usar qualquer número após /webhook/ (ex: /webhook/instancia1, /webhook/vendas, etc.)</p>
                </div>
                
                <div class="section">
                    <h2>📨 Webhooks Recebidos <span class="status">${webhooks.length} webhooks</span></h2>
                    <button class="clear-btn" onclick="clearWebhooks()">🗑️ Limpar Lista</button>
                    <div class="webhook-list" id="webhookList">
                        ${webhooks.length === 0 ? 
                            '<div class="no-webhooks">Nenhum webhook recebido ainda. Configure seus webhooks e comece a enviar mensagens!</div>' :
                            webhooks.map(w => `
                                <div class="webhook-item" onclick="showWebhookDetails(${w.id})">
                                    <div class="webhook-header">
                                        <span class="webhook-endpoint">${w.endpoint}</span>
                                        <span class="webhook-time">${new Date(w.timestamp).toLocaleString('pt-BR')}</span>
                                    </div>
                                    <div class="webhook-preview">${JSON.stringify(w.body, null, 2).substring(0, 200)}${JSON.stringify(w.body, null, 2).length > 200 ? '...' : ''}</div>
                                </div>
                            `).join('')
                        }
                    </div>
                </div>
            </div>
        </div>

        <script>
            function clearWebhooks() {
                if (confirm('Tem certeza que deseja limpar todos os webhooks?')) {
                    fetch('/clear', { method: 'POST' })
                        .then(() => location.reload());
                }
            }

            function showWebhookDetails(id) {
                const webhook = webhooks.find(w => w.id === id);
                if (webhook) {
                    alert('Webhook Details:\\n\\n' + JSON.stringify(webhook, null, 2));
                }
            }

            // Auto-refresh a cada 5 segundos
            setInterval(() => {
                fetch('/webhooks')
                    .then(res => res.json())
                    .then(data => {
                        if (data.length !== ${webhooks.length}) {
                            location.reload();
                        }
                    });
            }, 5000);

            // Dados dos webhooks para o JavaScript
            const webhooks = ${JSON.stringify(webhooks)};
        </script>
    </body>
    </html>
    `;
    
    res.send(html);
});

// Rota para receber webhooks (qualquer número)
app.post('/webhook/:id', (req, res) => {
    const endpoint = `/webhook/${req.params.id}`;
    const webhook = saveWebhook(endpoint, req.body, req.headers);
    
    res.json({
        success: true,
        message: 'Webhook recebido com sucesso!',
        webhook_id: webhook.id,
        timestamp: webhook.timestamp
    });
});

// Rota para listar webhooks (API)
app.get('/webhooks', (req, res) => {
    res.json(webhooks);
});

// Rota para limpar webhooks
app.post('/clear', (req, res) => {
    webhooks = [];
    res.json({ success: true, message: 'Webhooks limpos com sucesso!' });
});

// Rota para obter detalhes de um webhook específico
app.get('/webhook/:id', (req, res) => {
    const webhook = webhooks.find(w => w.id == req.params.id);
    if (webhook) {
        res.json(webhook);
    } else {
        res.status(404).json({ error: 'Webhook não encontrado' });
    }
});

// Rota de status
app.get('/status', (req, res) => {
    res.json({
        status: 'online',
        uptime: process.uptime(),
        webhooks_received: webhooks.length,
        timestamp: new Date().toISOString()
    });
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`🚀 Servidor Webhook Tester rodando em http://localhost:${PORT}`);
    console.log(`📡 Endpoints disponíveis:`);
    console.log(`   POST http://localhost:${PORT}/webhook/1`);
    console.log(`   POST http://localhost:${PORT}/webhook/2`);
    console.log(`   POST http://localhost:${PORT}/webhook/3`);
    console.log(`   POST http://localhost:${PORT}/webhook/4`);
    console.log(`   POST http://localhost:${PORT}/webhook/5`);
    console.log(`   (ou qualquer número após /webhook/)`);
    console.log(`📊 Dashboard: http://localhost:${PORT}`);
});

// Tratamento de erros
process.on('uncaughtException', (err) => {
    console.error('❌ Erro não tratado:', err);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Promise rejeitada:', reason);
});
