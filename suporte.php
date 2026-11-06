<?php
if (!function_exists('getSuporteHtml')) {
    function getSuporteHtml(): string
    {
        return <<<'HTML'
            <div class="exam-page support-page">
                <header class="section-hero">
                    <span class="section-kicker"><i class="fas fa-headset"></i> Central de ajuda</span>
                    <h2>Como podemos ajudar?</h2>
                    <p>Encontre respostas para dúvidas sobre sua conta, os conteúdos sobre exames cardiovasculares e os recursos disponíveis no CardioWeb.</p>
                </header>
                <section class="support-contact-grid" aria-label="Canais de suporte">
                    <article class="support-contact">
                        <span class="support-contact-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                        <div class="support-contact-copy">
                            <h3>Fale com o suporte</h3>
                            <p>Envie sua dúvida por e-mail. Não inclua informações médicas pessoais.</p>
                            <a class="support-contact-action" href="mailto:suporte@cardioweb.com?subject=Ajuda%20CardioWeb">Enviar e-mail <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        </div>
                    </article>
                    <article class="support-contact">
                        <span class="support-contact-icon" aria-hidden="true"><i class="fas fa-user-gear"></i></span>
                        <div class="support-contact-copy">
                            <h3>Conta e configurações</h3>
                            <p>Altere seu nome, e-mail e senha nas configurações da conta.</p>
                            <button class="support-contact-action" type="button" onclick="openSettingsModal('account')">Abrir configurações <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </article>
                    <article class="support-contact">
                        <span class="support-contact-icon" aria-hidden="true"><i class="fas fa-notes-medical"></i></span>
                        <div class="support-contact-copy">
                            <h3>Exames cardiovasculares</h3>
                            <p>Veja explicações simples sobre exames comuns do coração.</p>
                            <button class="support-contact-action" type="button" onclick="changePage('exames')">Conhecer os exames <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        </div>
                    </article>
                </section>
                <section class="support-page-card">
                    <h3><i class="fas fa-circle-question"></i> Perguntas frequentes</h3>
                    <p>Respostas rápidas sobre as principais funções do sistema.</p>
                    <div class="faq-list">
                        <details><summary>Como altero meu nome ou e-mail?</summary><p>Abra o menu do perfil no canto superior, escolha Minha conta, edite os campos e salve. A atualização é registrada no cadastro da conta.</p></details>
                        <details><summary>Como troco minha senha?</summary><p>Em Configurações &gt; Conta, informe a senha atual, digite a nova senha com pelo menos 8 caracteres e confirme a alteração.</p></details>
                        <details><summary>Onde encontro informações sobre exames cardiovasculares?</summary><p>Acesse Exames para ler explicações educativas sobre exames comuns. A indicação e a interpretação dos resultados devem ser feitas por um profissional de saúde.</p></details>
                        <details><summary>Onde encontro meus artigos de saúde?</summary><p>Abra Blog para buscar por assunto e consultar os conteúdos educativos disponíveis.</p></details>
                        <details><summary>O assistente analisa exames ou faz diagnósticos?</summary><p>O assistente usa inteligência artificial para conversar sobre dúvidas e sintomas gerais, mas não diagnostica, prescreve nem substitui profissionais. Você pode anexar imagens comuns; evite enviar exames, documentos ou dados que identifiquem você. Mensagens e imagens são enviadas a um serviço externo de IA para gerar respostas.</p></details>
                        <details><summary>Esqueci minha senha ou encontrei um problema técnico. O que faço?</summary><p>Use o link de suporte por e-mail acima e descreva o problema. Não envie sua senha ou informações clínicas na mensagem.</p></details>
                    </div>
                </section>
                <aside class="support-emergency"><strong>Precisa de atendimento médico urgente?</strong> O suporte do site não é um serviço de emergência. Em caso de sintomas graves ou repentinos, ligue para o SAMU (192) ou procure um pronto atendimento.</aside>
            </div>
HTML;
    }
}
?>
