<?php
if (!function_exists('getInformacoesHtml')) {
    function getInformacoesHtml(): string
    {
        return <<<'HTML'
            <div class="exam-page info-page">
                <header class="section-hero">
                    <span class="section-kicker"><i class="fas fa-heart-pulse"></i> Cardiologia básica</span>
                    <h2>Conheça melhor o seu coração</h2>
                    <p>Uma introdução simples ao funcionamento do coração, aos principais fatores de risco e aos exames que ajudam profissionais de saúde a avaliá-lo.</p>
                </header>
                <section class="info-page-card">
                    <h3>O que o coração faz?</h3>
                    <p>O coração é um músculo que bombeia sangue continuamente. O sangue leva oxigênio e nutrientes pelo corpo e retorna aos pulmões para receber oxigênio novamente. Na circulação do corpo, as artérias levam o sangue para longe do coração e as veias o trazem de volta.</p>
                    <div class="info-intro-grid">
                        <article class="info-feature"><i class="fas fa-heart-pulse"></i><strong>Batimentos</strong><p>Um sistema elétrico coordena o ritmo do coração. O pulso é a onda que pode ser sentida nas artérias a cada batimento.</p></article>
                        <article class="info-feature"><i class="fas fa-gauge-high"></i><strong>Pressão arterial</strong><p>É a força do sangue contra as paredes das artérias. Ela varia durante o dia e deve ser avaliada e interpretada por um profissional.</p></article>
                        <article class="info-feature"><i class="fas fa-droplet"></i><strong>Colesterol</strong><p>É uma substância presente no sangue. Alguns níveis elevados podem contribuir para o acúmulo de placas nas artérias.</p></article>
                    </div>
                </section>
                <section class="info-page-card">
                    <h3>Exames que você pode ouvir falar</h3>
                    <div class="info-intro-grid">
                        <article class="info-feature"><i class="fas fa-wave-square"></i><strong>Eletrocardiograma</strong><p>Registra a atividade elétrica do coração durante alguns instantes.</p></article>
                        <article class="info-feature"><i class="fas fa-heart"></i><strong>Ecocardiograma</strong><p>Usa ultrassom para produzir imagens do coração em movimento.</p></article>
                        <article class="info-feature"><i class="fas fa-stopwatch"></i><strong>Holter</strong><p>Registra o ritmo cardíaco ao longo de um período com um aparelho portátil.</p></article>
                    </div>
                    <p>O profissional de saúde decide quais exames são adequados e interpreta os resultados considerando o histórico e os sintomas de cada pessoa.</p>
                </section>
                <aside class="support-emergency"><strong>Procure ajuda urgente:</strong> dor ou pressão forte no peito, falta de ar intensa, desmaio ou fraqueza súbita podem exigir atendimento imediato. No Brasil, ligue para o SAMU (192).</aside>
                <section class="info-page-card">
                    <h3>Hábitos que favorecem a saúde cardiovascular</h3>
                    <p>Alimentação variada, atividade física adequada à sua condição, sono suficiente, evitar tabaco e acompanhar a pressão e o colesterol são medidas que podem contribuir para a saúde do coração. Converse com um profissional antes de fazer mudanças importantes ou iniciar exercícios.</p>
                    <p>Este material é educativo: não oferece diagnóstico ou tratamento e não substitui avaliação de um profissional de saúde. <button type="button" class="settings-inline-link info-privacy-link" onclick="openTermsModal()">Leia as informações de uso e privacidade.</button></p>
                </section>
            </div>
HTML;
    }
}
?>
