<?php
if (!function_exists('getExamesHtml')) {
    function getExamesHtml(): string
    {
        return <<<'HTML'
            <div class="exam-page">
                <header class="section-hero">
                    <span class="section-kicker"><i class="fas fa-flask"></i> Saúde cardiovascular</span>
                    <h2>Exames do coração</h2>
                    <p>Conheça alguns exames comuns usados na avaliação cardiovascular. A indicação e a leitura dos resultados devem ser feitas por um profissional de saúde.</p>
                </header>
                <section class="info-page-card">
                    <h3>Exames mais conhecidos</h3>
                    <div class="info-intro-grid">
                        <article class="info-feature"><i class="fas fa-wave-square"></i><strong>Eletrocardiograma (ECG)</strong><p>Registra a atividade elétrica do coração e pode ajudar a identificar alterações do ritmo.</p></article>
                        <article class="info-feature"><i class="fas fa-heart"></i><strong>Ecocardiograma</strong><p>Exame de ultrassom que mostra imagens das estruturas e do movimento do coração.</p></article>
                        <article class="info-feature"><i class="fas fa-stopwatch"></i><strong>Holter</strong><p>Um monitor portátil registra os batimentos durante as atividades do dia a dia.</p></article>
                        <article class="info-feature"><i class="fas fa-person-running"></i><strong>Teste ergométrico</strong><p>Acompanha o coração durante esforço físico controlado, quando indicado.</p></article>
                        <article class="info-feature"><i class="fas fa-vial"></i><strong>Exames de sangue</strong><p>Podem medir fatores como colesterol e glicose, importantes na avaliação do risco cardiovascular.</p></article>
                        <article class="info-feature"><i class="fas fa-gauge-high"></i><strong>Medição da pressão</strong><p>Verifica a pressão arterial, que pode variar e precisa ser interpretada no contexto clínico.</p></article>
                    </div>
                </section>
                <aside class="support-emergency"><strong>Importante:</strong> esta página é educativa e não registra exames pessoais. Não interprete resultados por conta própria; leve-os ao profissional que acompanha você.</aside>
            </div>
HTML;
    }
}
?>
