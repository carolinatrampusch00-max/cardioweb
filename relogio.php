<?php
if (!function_exists('getRelogioHtml')) {
    function getRelogioHtml(): string
    {
        return <<<'HTML'
            <div class="watch-page">
                <header class="watch-hero">
                    <div>
                        <span class="watch-kicker"><i class="fas fa-heart-pulse"></i> Demonstração acadêmica</span>
                        <h2>Relógio cardíaco</h2>
                        <p>Explore uma demonstração de relógio cardíaco com conexão simulada. Nenhum sensor Bluetooth real é acessado.</p>
                    </div>
                    <div class="watch-clock-card" aria-label="Horário local">
                        <span><i class="far fa-clock"></i> HORÁRIO LOCAL</span>
                        <time id="watchClock" aria-live="off">--:--:--</time>
                        <small id="watchDate">Carregando data…</small>
                    </div>
                </header>
                <aside class="watch-demo-note"><i class="fas fa-circle-info"></i><span><strong>Modo de demonstração:</strong> a simulação não é uma medição clínica e não deve ser usada para decisões de saúde. O último valor demonstrativo também aparece em Últimos Registros.</span></aside>
                <section class="watch-appointment-alert" id="watchAppointmentAlert" aria-live="polite" hidden></section>
                <section class="watch-layout" aria-label="Monitoramento cardíaco demonstrativo">
                    <article class="watch-monitor-card">
                        <header class="watch-card-heading">
                            <div><span class="watch-card-icon"><i class="fas fa-heart-pulse"></i></span><div><h3>Frequência cardíaca</h3><p id="watchSource">Conecte para iniciar a demonstração</p></div></div>
                            <span class="watch-status" id="watchStatus"><i></i><span>Em espera</span></span>
                        </header>
                        <div class="watch-reading">
                            <div class="watch-heart" id="watchHeart" aria-hidden="true"><i class="fas fa-heart-pulse"></i></div>
                            <div class="watch-bpm"><strong id="watchBpm">0</strong><span>BPM</span></div>
                            <p id="watchContext">Sem conexão demonstrativa. Os batimentos permanecem zerados.</p>
                        </div>
                        <div class="watch-measurements" aria-label="Outras medições demonstrativas do relógio">
                            <article class="watch-measurement"><span><i class="fas fa-wave-square"></i> VFC</span><strong id="watchHrvValue">—</strong><small>Variabilidade · ms</small></article>
                            <article class="watch-measurement"><span><i class="fas fa-lungs"></i> SpO₂</span><strong id="watchSpo2Value">—</strong><small>Oxigenação · %</small></article>
                            <article class="watch-measurement"><span><i class="fas fa-heart-pulse"></i> ECG</span><strong id="watchEcgValue">—</strong><div class="watch-ecg-trace" role="img" aria-label="Traçado eletrocardiográfico apenas ilustrativo; não é uma leitura real"><svg viewBox="0 0 180 28" preserveAspectRatio="none" aria-hidden="true"><path d="M0 16h43l8-1 5-4 5 8 7-15 8 17 7-9 5 4h42l8-1 5-4 5 8 7-15 8 17 7-9 5 4h18"/></svg></div><small>Traçado ilustrativo, sem análise clínica</small></article>
                        </div>
                        <div class="watch-chart-wrap">
                            <div class="watch-chart-label"><strong>Leituras desta sessão</strong><span>Atualização ao vivo</span></div>
                            <canvas id="watchChart" role="img" aria-label="Gráfico demonstrativo das leituras de frequência cardíaca"></canvas>
                        </div>
                        <div class="watch-controls">
                            <button type="button" class="watch-button watch-button-secondary" id="watchPauseButton" onclick="pauseHeartMonitor()" disabled><i class="fas fa-pause"></i> Pausar</button>
                            <button type="button" class="watch-button watch-button-outline" id="watchConnectButton" onclick="connectHeartSensor()"><i class="fab fa-bluetooth-b"></i> Conectar</button>
                        </div>
                    </article>
                    <aside class="watch-side-column">
                        <section class="watch-scenarios">
                            <span class="watch-kicker">CENÁRIOS DE TESTE</span>
                            <h3>Explore a simulação</h3>
                            <p>Selecione um cenário para visualizar como o painel se atualiza. Os números são ilustrativos.</p>
                            <div class="watch-scenario-list">
                                <button type="button" onclick="setHeartScenario('repouso')"><i class="fas fa-moon"></i><span><strong>Repouso</strong><small>Exemplo de valor em repouso</small></span><i class="fas fa-chevron-right"></i></button>
                                <button type="button" onclick="setHeartScenario('rotina')"><i class="fas fa-person-walking"></i><span><strong>Rotina leve</strong><small>Exemplo de atividade cotidiana</small></span><i class="fas fa-chevron-right"></i></button>
                                <button type="button" onclick="setHeartScenario('atividade')"><i class="fas fa-person-running"></i><span><strong>Atividade</strong><small>Exemplo durante movimento</small></span><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </section>
                        <section class="watch-project-note">
                            <span class="watch-project-icon"><i class="fas fa-graduation-cap"></i></span>
                            <h3>Sobre esta demonstração</h3>
                            <p>O dispositivo e as leituras são simulados e aparecem também em Últimos Registros. O ECG é apenas ilustrativo: não detecta arritmias nem interpreta exames. Nenhuma conexão Bluetooth real é solicitada.</p>
                            <span><i class="fas fa-shield-heart"></i> Demonstração acadêmica, não clínica.</span>
                        </section>
                    </aside>
                </section>
                <section class="info-card dashboard-panel appointments-panel watch-agenda" id="watchAgenda" aria-labelledby="watchAgendaTitle">
                    <header class="panel-heading">
                        <h3 id="watchAgendaTitle"><span><i class="fas fa-calendar-days"></i></span>Agenda</h3>
                        <div class="watch-agenda-actions">
                            <span class="watch-agenda-count"><strong id="appointmentCount">0</strong> próximos</span>
                            <button type="button" class="appointment-add-button" onclick="openAppointmentModal()"><i class="fas fa-plus"></i> Adicionar</button>
                        </div>
                    </header>
                    <p class="watch-agenda-note">Esta agenda serve como lembrete. Para editar ou excluir um compromisso, acesse a Agenda no Monitoramento.</p>
                    <div class="appointment-list" id="appointmentList" aria-live="polite"></div>
                </section>
            </div>
            <div class="modal" id="watchConnectionModal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="watchConnectionTitle" onclick="if(event.target === this) closeWatchConnectionModal()">
                <section class="modal-content watch-connection-modal">
                    <header class="modal-header">
                        <h2 id="watchConnectionTitle"><i class="fab fa-bluetooth-b"></i> Conectar dispositivo</h2>
                        <button type="button" class="modal-close" aria-label="Fechar conexão" onclick="closeWatchConnectionModal()">&times;</button>
                    </header>
                    <div class="modal-body">
                        <p class="watch-connection-description">Esta é uma simulação acadêmica. Escolha o dispositivo fictício para iniciar as leituras aleatórias; nenhum aparelho real será procurado.</p>
                        <div class="watch-demo-device">
                            <span class="watch-demo-device-icon"><i class="fas fa-heart-pulse"></i></span>
                            <span><strong id="watchDemoDeviceName">CardioWeb Pulse</strong><small id="watchDemoDeviceStatus">Disponível para demonstração</small></span>
                            <i class="fas fa-signal"></i>
                        </div>
                        <div class="watch-connection-actions">
                            <button type="button" class="watch-button watch-button-outline" onclick="closeWatchConnectionModal()">Cancelar</button>
                            <button type="button" class="watch-button watch-button-primary" id="watchDemoPairButton" onclick="pairDemoHeartSensor()"><i class="fab fa-bluetooth-b"></i> Conectar demonstração</button>
                        </div>
                    </div>
                </section>
            </div>
HTML;
    }
}
?>
