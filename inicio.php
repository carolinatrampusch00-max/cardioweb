<?php
function getInicioHtml($user_name) {
    $safeUserName = htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8');

    return <<<HTML
        <div class="monitoring-shell">
            <section class="welcome-card dashboard-welcome">
                <div class="welcome-copy">
                    <div class="welcome-title"><span></span><h2>Olá, {$safeUserName}!</h2></div>
                    <p>Acompanhe seus indicadores, registros e próximos compromissos em um só lugar. Informação clara para apoiar o cuidado com a sua saúde.</p>
                </div>
                <div class="welcome-art" aria-hidden="true"></div>
            </section>

            <section class="stats-grid dashboard-stats" aria-label="Resumo de saúde">
                <button type="button" class="stat-card dashboard-stat" aria-label="Abrir detalhes dos registros de saúde" aria-haspopup="dialog" onclick="openDashboardInsight('records')">
                    <span class="stat-top"><span class="stat-icon"><i class="fas fa-wave-square"></i></span><span class="stat-arrow" aria-hidden="true"><i class="fas fa-chevron-right"></i></span></span>
                    <strong>12</strong><span>Registros de saúde · demo</span>
                </button>
                <button type="button" class="stat-card dashboard-stat" aria-label="Abrir detalhes da frequência cardíaca" aria-haspopup="dialog" onclick="openDashboardInsight('heart-rate')">
                    <span class="stat-top"><span class="stat-icon"><i class="fas fa-heart-pulse"></i></span><span class="stat-arrow" aria-hidden="true"><i class="fas fa-chevron-right"></i></span></span>
                    <strong id="watchSummaryBpm">0</strong><span>Última FC · BPM (demo)</span>
                </button>
                <button type="button" class="stat-card dashboard-stat" aria-label="Abrir próximos compromissos" aria-haspopup="dialog" onclick="openDashboardInsight('appointments')">
                    <span class="stat-top"><span class="stat-icon"><i class="fas fa-calendar-days"></i></span><span class="stat-arrow" aria-hidden="true"><i class="fas fa-chevron-right"></i></span></span>
                    <strong id="appointmentCount">0</strong><span>Próximos compromissos</span>
                </button>
                <button type="button" class="stat-card dashboard-stat" aria-label="Entenda a meta de saúde demonstrativa" aria-haspopup="dialog" onclick="openDashboardInsight('goal')">
                    <span class="stat-top"><span class="stat-icon"><i class="fas fa-trophy"></i></span><span class="stat-arrow" aria-hidden="true"><i class="fas fa-chevron-right"></i></span></span>
                    <strong>85%</strong><span>Meta de saúde · demo</span>
                </button>
            </section>

            <section class="info-card dashboard-panel records-panel">
                <header class="panel-heading">
                    <h3><span><i class="fas fa-wave-square"></i></span>Últimos Registros</h3>
                    <button type="button" class="view-all" onclick="openHealthHistory()"><i class="fas fa-clock-rotate-left"></i> Histórico</button>
                </header>
                    <p class="records-demo-note">Acompanhe seus exames e indicadores de saúde mais recentes.</p>
                    <div class="record-list" id="healthRecordList" aria-live="polite"></div>
            </section>

            <section class="info-card dashboard-panel appointments-panel">
                <header class="panel-heading">
                    <h3><span><i class="fas fa-calendar-days"></i></span>Agenda</h3>
                    <button type="button" class="appointment-add-button" onclick="openAppointmentModal()"><i class="fas fa-plus"></i> Adicionar</button>
                </header>
                <div class="appointment-list" id="appointmentList" aria-live="polite"></div>
            </section>
        </div>
        <div class="appointment-modal" id="healthRecordModal" role="dialog" aria-modal="true" aria-labelledby="healthRecordModalTitle" onclick="if(event.target===this)closeHealthRecordModal()">
            <section class="appointment-modal-card health-record-modal-card">
                <header class="appointment-modal-heading">
                    <h2 id="healthRecordModalTitle">Editar registro</h2>
                    <button type="button" class="appointment-modal-close" aria-label="Fechar" onclick="closeHealthRecordModal()"><i class="fas fa-xmark"></i></button>
                </header>
                <form class="appointment-form" id="healthRecordForm" onsubmit="saveHealthRecord(event)">
                    <input type="hidden" id="healthRecordId">
                    <label class="wide" id="healthRecordValueLabel">Valor<input id="healthRecordInput" maxlength="40" required autocomplete="off"></label>
                    <label class="wide">Observação (opcional)<input id="healthRecordNote" maxlength="120" placeholder="Ex.: medido em repouso"></label>
                    <p class="appointment-reminder-note">Os valores editáveis são salvos na sua conta. As leituras do relógio são exibidas à parte e não podem ser alteradas manualmente.</p>
                    <div class="appointment-form-actions">
                        <button type="button" onclick="closeHealthRecordModal()">Cancelar</button>
                        <button type="submit">Salvar registro</button>
                    </div>
                </form>
            </section>
        </div>
        <div class="appointment-modal" id="healthHistoryModal" role="dialog" aria-modal="true" aria-labelledby="healthHistoryTitle" onclick="if(event.target===this)closeHealthHistory()">
            <section class="appointment-modal-card health-history-modal-card">
                <header class="appointment-modal-heading">
                    <div><h2 id="healthHistoryTitle">Histórico de saúde</h2><p>Registros atuais e alterações salvos na sua conta.</p></div>
                    <button type="button" class="appointment-modal-close" aria-label="Fechar histórico" onclick="closeHealthHistory()"><i class="fas fa-xmark"></i></button>
                </header>
                <div id="healthHistoryContent" class="health-history-content" aria-live="polite">
                    <p class="health-history-loading">Carregando histórico salvo no banco de dados...</p>
                </div>
            </section>
        </div>
        <div class="appointment-modal dashboard-insight-modal" id="dashboardInsightModal" role="dialog" aria-modal="true" aria-labelledby="dashboardInsightTitle" onclick="if(event.target===this)closeDashboardInsight()">
            <section class="appointment-modal-card dashboard-insight-card">
                <header class="appointment-modal-heading">
                    <div class="dashboard-insight-heading-icon" id="dashboardInsightIcon" aria-hidden="true"><i class="fas fa-heart-pulse"></i></div>
                    <div class="dashboard-insight-heading-copy"><h2 id="dashboardInsightTitle">Resumo</h2><p id="dashboardInsightSubtitle"></p></div>
                    <button type="button" class="appointment-modal-close" aria-label="Fechar detalhes" onclick="closeDashboardInsight()"><i class="fas fa-xmark"></i></button>
                </header>
                <div class="dashboard-insight-content" id="dashboardInsightContent"></div>
                <div class="dashboard-insight-actions" id="dashboardInsightActions"></div>
            </section>
        </div>
HTML;
}
