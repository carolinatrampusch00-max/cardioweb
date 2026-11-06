<?php
if (!function_exists('getConsultasHtml')) {
    function getConsultasHtml()
    {
        $html = '<div class="info-card">';
        $html .= '<h3><i class="fas fa-calendar-check"></i> Minhas Consultas</h3>';
        $html .= '<p style="color:#64748b; margin-bottom:16px;">Aqui você pode acompanhar seus próximos atendimentos e solicitar uma nova consulta com facilidade.</p>';
        $html .= '<div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px;">';
        $html .= '<button onclick="openConsultaModal()" class="contact-btn"><i class="fas fa-plus"></i> Solicitar consulta</button>';
        $html .= '<span style="background:#fff7ed; color:#c2410c; padding:8px 12px; border-radius:999px; font-size:13px; font-weight:600;">Atendimento rápido e seguro</span>';
        $html .= '</div>';
        $html .= '<div style="display:grid; gap:12px;">';
        $html .= '<div style="padding:16px; border:1px solid #f0f0f0; border-radius:12px; background:#fdf8f9;"><div style="font-weight:700; color:#1e2a3a;">Próxima consulta</div><div style="margin-top:6px; color:#475569;">15/04/2024 • 10:00 • Dr. Carlos • Cardiologia</div></div>';
        $html .= '<div style="padding:16px; border:1px solid #f0f0f0; border-radius:12px; background:#ffffff;"><div style="font-weight:700; color:#1e2a3a;">Consulta anterior</div><div style="margin-top:6px; color:#475569;">08/03/2024 • 14:30 • Dra. Aline • Arritmologia</div></div>';
        $html .= '<div style="padding:16px; border:1px solid #f0f0f0; border-radius:12px; background:#f8fafc;"><div style="font-weight:700; color:#1e2a3a;">Como funciona</div><div style="margin-top:6px; color:#475569;">Escolha um médico, informe a data e o horário desejado. Depois de enviado, o pedido ficará aguardando confirmação.</div></div>';
        $html .= '</div></div>';
        return $html;
    }
}

?>
