// 1. Estrutura HTML do Chat injetada direto no Body
const chatHTML = `
<div class="chat-widget">
    <!-- Janela do Chat -->
    <div class="chat-window" id="chat-window">
        <div class="chat-header">
            <span>Assistente IA (Demo)</span>
            <button class="close-btn" id="close-chat">&times;</button>
        </div>
        
        <div class="chat-messages" id="chat-messages">
            <div class="msg msg-bot">
                <strong>IA:</strong> Olá! Sou o assistente virtual da HP Terraplenagem. Na versão completa, eu analiso os dados reais do banco. Aqui na demonstração, sinta-se à vontade para testar a interface!
            </div>
        </div>
        
        <form class="chat-input" id="chat-form">
            <input type="text" id="chat-input-field" placeholder="Digite sua mensagem..." autocomplete="off" required>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <!-- Botão Flutuante -->
    <button class="chat-btn" id="open-chat">💬</button>
</div>
`;

// Adiciona o chat no final da página
document.body.insertAdjacentHTML('beforeend', chatHTML);

// 2. Lógica de Abrir e Fechar
const chatWindow = document.getElementById('chat-window');
const btnOpen = document.getElementById('open-chat');
const btnClose = document.getElementById('close-chat');

btnOpen.addEventListener('click', () => {
    chatWindow.classList.add('open');
    btnOpen.style.display = 'none'; // Esconde o botão quando o chat abre
});

btnClose.addEventListener('click', () => {
    chatWindow.classList.remove('open');
    btnOpen.style.display = 'flex'; // Mostra o botão novamente
});

// 3. Lógica de Envio de Mensagem e Resposta Simulada (Mock do Gemini)
const chatForm = document.getElementById('chat-form');
const chatMessages = document.getElementById('chat-messages');
const chatInputField = document.getElementById('chat-input-field');

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const textoUsuario = chatInputField.value.trim();
    if (!textoUsuario) return;

    // Adiciona a mensagem do usuário na tela
    adicionarMensagem('user', textoUsuario);
    chatInputField.value = '';
    
    // Mostra indicador de digitando
    const idDigitando = adicionarMensagem('bot', '<em>Digitando...</em>');

    // Simula o tempo de processamento da IA (1.5 segundos)
    setTimeout(() => {
        // Remove a mensagem de "Digitando..."
        const msgDigitando = document.getElementById(idDigitando);
        if(msgDigitando) msgDigitando.remove();

        // Respostas simuladas baseadas em palavras-chave
        const textoLower = textoUsuario.toLowerCase();
        let respostaIA = "";

        if (textoLower.includes("hora") || textoLower.includes("produção")) {
            respostaIA = "<strong>IA:</strong> No sistema completo, eu cruzo as horas trabalhadas para gerar relatórios detalhados. Você pode ver uma simulação disso no 'Dashboard de Horas'.";
        } 
        else if (textoLower.includes("gasto") || textoLower.includes("despesa") || textoLower.includes("dinheiro")) {
            respostaIA = "<strong>IA:</strong> Para lançar gastos, vá até o 'Controle de Gastos'. Lá eu também possuo um recurso de Preenchimento Rápido que lê seus textos e preenche o formulário automaticamente!";
        } 
        else if (textoLower.includes("óleo") || textoLower.includes("manutenção") || textoLower.includes("revisão")) {
            respostaIA = "<strong>IA:</strong> A manutenção preventiva é essencial! O painel alerta automaticamente quando uma máquina atinge o limite do horímetro para a próxima troca de óleo.";
        } 
        else {
            respostaIA = "<strong>IA:</strong> Como esta é uma versão de portfólio estática, minhas respostas são pré-programadas. Mas no projeto original com PHP e MySQL, eu analiso os dados reais usando a API do Gemini!";
        }

        // Adiciona a resposta da IA
        adicionarMensagem('bot', respostaIA);
        
    }, 1500);
});

// Função auxiliar para injetar as mensagens e rolar a tela para baixo
function adicionarMensagem(remetente, texto) {
    const div = document.createElement('div');
    div.className = `msg msg-${remetente}`;
    const idUnico = 'msg-' + Date.now();
    div.id = idUnico;
    div.innerHTML = texto;
    
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight; // Rola para o final
    
    return idUnico;
}