<?php
function getChatSidebarHtml() {
    return <<<HTML
    <div class="chat-sidebar">
        <div class="chat-header"><h3><i class="fas fa-comment-dots"></i> Bate-papo</h3><p>💬 Converse com nossa equipe</p></div>
        <div class="chat-messages" id="chatMessages"><div class="message bot"><div class="message-avatar"><i class="fas fa-robot"></i></div><div class="message-bubble">Olá! Bem-vindo ao CardioWeb. Como posso ajudar? 💙</div></div></div>
        <div class="chat-input-area"><input type="text" class="chat-input" id="chatInput" placeholder="Digite sua mensagem..." onkeypress="if(event.key === 'Enter') sendMessage()"><button class="chat-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button></div>
    </div>
HTML;
}

function getChatScript() {
    return <<<JS
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value.trim();
        if (!msg) return;
        addMessage(msg, 'user');
        input.value = '';
        setTimeout(() => {
            const response = getBotResponse(msg);
            addMessage(response, 'bot');
        }, 500);
    }
    function addMessage(text, sender) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${sender}`;
        const avatar = sender === 'user' ? '<div class="message-avatar"><i class="fas fa-user"></i></div>' : '<div class="message-avatar"><i class="fas fa-robot"></i></div>';
        div.innerHTML = avatar + `<div class="message-bubble">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    function getBotResponse(msg) {
        const m = msg.toLowerCase();
        if (m.includes('olá') || m.includes('oi')) return 'Olá! Como posso ajudar? 💙';
        if (m.includes('pressão')) return 'A pressão ideal é abaixo de 120/80 mmHg. Mantenha uma alimentação saudável!';
        if (m.includes('consulta')) return 'Para agendar uma consulta, acesse o menu "Consultas" ou ligue para (11) 4002-8922.';
        if (m.includes('exame')) return 'Seus exames ficam disponíveis na seção "Exames" após liberação médica.';
        return 'Entendi! Para mais informações, leia nossos artigos no blog ou acesse o suporte. 💙';
    }
JS;
}
