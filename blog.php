<?php
if (!function_exists('getBlogArticles')) {
    function getBlogArticles()
    {
        $articles = [
            'hipertensao' => [
                'title' => 'Hipertensão arterial: o que ela faz com o corpo',
                'date' => 'Atualizado em 2026 • 8 min',
                'summary' => 'A pressão alta aumenta o esforço do coração e pode danificar artérias, rins, cérebro e olhos quando não é controlada.',
                'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1200&q=80',
                'content' => '<p>A hipertensão arterial é a elevação persistente da pressão do sangue dentro das artérias. Ela geralmente é silenciosa, e por isso é chamada de “assassina silenciosa”: muitas pessoas não sentem sintomas, mas os danos acontecem ao longo do tempo.</p><h3>O que ela faz no corpo</h3><p>Com a pressão elevada, as artérias ficam mais rígidas e menos elásticas. O coração passa a bater com mais força para empurrar o sangue, o que pode levar ao aumento do músculo cardíaco, risco de insuficiência cardíaca, AVC, doença renal e prejuízo visual.</p><h3>Como ela se manifesta</h3><p>Nem sempre há sintomas. Em alguns casos, pode haver dor de cabeça, tontura, palpitações, zumbido, visão turva ou sensação de pressão no peito. Mesmo sem queixas, a pressão elevada pode estar provocando lesões no organismo.</p><h3>Como melhorar a qualidade de vida</h3><p>Controle a pressão com acompanhamento médico, reduza o sal, prefira alimentos naturais, mantenha atividade física orientada, evite álcool em excesso e acompanhe a medição da pressão em casa. O uso correto dos medicamentos também é essencial para evitar complicações.</p><h3>Quando procurar ajuda</h3><p>Procure atendimento rápido se houver dor no peito, falta de ar intensa, fraqueza de um lado do corpo, confusão mental, desmaio ou pressão muito alta com sintomas.</p><div style="margin-top:20px; padding:18px; background:#f8fafc; border-left:4px solid #851e32; border-radius:10px;"><h3 style="margin-top:0; color:#851e32;">Tradução</h3><p>Em outras palavras, a pressão alta não “só eleva a leitura do aparelho”. Ela força o coração e os vasos sanguíneos a trabalhar mais do que deveriam, e isso pode desgastar o corpo aos poucos. Quando a pessoa cuida da alimentação, toma os remédios corretamente e acompanha a pressão, reduz muito o risco de problemas graves.</p></div>'
            ],
            'insuficiencia' => [
                'title' => 'Insuficiência cardíaca: o que acontece com o coração',
                'date' => 'Atualizado em 2026 • 9 min',
                'summary' => 'O coração continua funcionando, mas não consegue suprir a demanda do corpo com a mesma eficiência, e isso pode causar cansaço e inchaço.',
                'image' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895973?auto=format&fit=crop&w=1200&q=80',
                'content' => '<p>Insuficiência cardíaca não significa que o coração parou. Ela significa que ele está trabalhando menos bem para bombear sangue e atender às necessidades do corpo. Isso pode acontecer por hipertensão, doença arterial, alterações das válvulas, arritmias ou problemas prévios do músculo cardíaco.</p><h3>Como a doença afeta o corpo</h3><p>Quando o bombeamento fica menos eficiente, o sangue pode se acumular em alguns setores. Isso leva à sensação de falta de ar, cansaço, edema nas pernas, ganho rápido de peso, dificuldade para caminhar e piora ao deitar.</p><h3>O que acontece no coração</h3><p>O coração tenta compensar com mais esforço. Com o tempo, ele pode aumentar de tamanho, a pressão no interior das câmaras sobe e aumenta a retenção de líquido. Por isso, o paciente pode ter piora progressiva da tolerância a atividades e limitação no dia a dia.</p><h3>Como viver melhor</h3><p>É fundamental seguir a medicação prescrita, controlar o sal, pesar-se diariamente, observar ganho de peso rápido, manter a hidratação adequada conforme orientação médica e não interromper remédios sem discutir com o médico. O acompanhamento regular ajuda a prevenir descompensações.</p><h3>Quando procurar ajuda</h3><p>Se houver falta de ar em repouso, escurecimento da visão, confusão, inchaço importante nas pernas, aumento rápido de peso ou piora repentina, é importante buscar avaliação médica.</p><div style="margin-top:20px; padding:18px; background:#f8fafc; border-left:4px solid #851e32; border-radius:10px;"><h3 style="margin-top:0; color:#851e32;">Tradução</h3><p>O coração continua vivo e trabalhando, mas está com menos capacidade de bombear sangue adequadamente. Como resultado, o corpo guarda líquido e o paciente ganha cansaço e falta de ar. Com tratamento, controle de sal e acompanhamento, muitas pessoas conseguem viver melhor e com menos sintomas.</p></div>'
            ],
            'arritmia' => [
                'title' => 'Arritmias: quando o ritmo do coração muda',
                'date' => 'Atualizado em 2026 • 7 min',
                'summary' => 'A alteração no ritmo pode causar palpitações, tonturas e sensação de “coração acelerado” ou irregular.',
                'image' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1200&q=80',
                'content' => '<p>Arritmias são alterações na formação ou na condução do impulso elétrico que coordena os batimentos do coração. Elas podem ser causadas por ansiedade, excesso de cafeína, alterações do sistema nervoso, doenças da tireoide, uso de certos medicamentos, hipertensão, insuficiência cardíaca ou problemas estruturais do coração.</p><h3>O que acontece</h3><p>Em vez de bater de forma regular, o coração pode acelerar, desacelerar ou ficar irregular. Isso pode reduzir o débito cardíaco, interferir na oxigenação do corpo e, em algumas situações, aumentar o risco de formação de coágulos ou de desmaios.</p><h3>Sinais que não devem ser ignorados</h3><p>Palpitações, sensação de coração “pulando”, tontura, desmaio, falta de ar, dor no peito ou mal-estar intenso precisam de avaliação. O eletrocardiograma, o Holter e outros exames ajudam a entender qual é o tipo da arritmia.</p><h3>Como viver melhor</h3><p>Evite cafeína, energia ou suplementos sem orientação, não use remédios sem indicação, registre os episódios e o momento em que ocorreram, e mantenha acompanhamento com o cardiologista. Em casos mais graves, o tratamento pode incluir medicamentos ou procedimentos específicos.</p><div style="margin-top:20px; padding:18px; background:#f8fafc; border-left:4px solid #851e32; border-radius:10px;"><h3 style="margin-top:0; color:#851e32;">Tradução</h3><p>O sinal elétrico do coração fica desregulado e o ritmo deixa de ser estável. Isso pode fazer o coração bater rápido, irregular ou se sentir estranho. Quando isso acontece com frequência, é preciso investigar para evitar riscos e melhorar o bem-estar.</p></div>'
            ],
            'colesterol' => [
                'title' => 'Colesterol e placas nas artérias',
                'date' => 'Atualizado em 2026 • 6 min',
                'summary' => 'O excesso de gordura no sangue favorece a formação de placas que podem estreitar e bloquear as artérias.',
                'image' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80',
                'content' => '<p>O colesterol é essencial para o corpo, mas quando está em excesso, especialmente o LDL, ele pode se acumular na parede das artérias. Esse processo é chamado de aterosclerose e pode fazer os vasos ficarem mais estreitos e menos maleáveis.</p><h3>Como isso afeta o coração</h3><p>Se as artérias do coração ficam obstruídas, o fluxo de sangue pode diminuir. Isso aumenta o risco de angina, infarto e AVC. A piora não costuma acontecer de uma hora para outra: é um processo gradual que pode ser lento e silencioso.</p><h3>Como melhorar</h3><p>Uma alimentação com menos alimentos ultraprocessados, menos sal e menos gordura saturada ajuda muito. Frutas, legumes, grãos integrais e gorduras de boa qualidade são importantes. A atividade física regular, a perda de peso quando indicada e a adesão à medicação também auxiliam no controle.</p><h3>O papel do médico</h3><p>Nem todo paciente precisa da mesma meta. O objetivo depende do risco cardiovascular individual, do histórico, da idade e de outras condições. O controle do colesterol deve ser acompanhado com exames e orientação individualizada.</p><div style="margin-top:20px; padding:18px; background:#f8fafc; border-left:4px solid #851e32; border-radius:10px;"><h3 style="margin-top:0; color:#851e32;">Tradução</h3><p>O colesterol alto não é apenas um “número do exame”; ele pode formar acúmulo dentro dos vasos e prejudicar o fluxo sanguíneo. Quando a pessoa muda hábitos e segue a orientação médica, o risco de problemas cardíacos tende a cair.</p></div>'
            ],
            'exames' => [
                'title' => 'Como interpretar os exames cardiológicos',
                'date' => 'Atualizado em 2026 • 8 min',
                'summary' => 'Os exames ajudam a entender a estrutura, a função e o ritmo do coração, mas o resultado deve ser visto no contexto do paciente.',
                'image' => 'https://images.unsplash.com/photo-1584515933487-779824d29309?auto=format&fit=crop&w=1200&q=80',
                'content' => '<p>Os exames cardiológicos ajudam a responder perguntas importantes: o coração está batendo em ritmo adequado? Ele está bombeando bem? Há alteração no músculo, nas válvulas ou na circulação? O laudo deve ser lido junto com os sintomas, histórico e exame físico.</p><h3>Exames mais comuns</h3><p>O eletrocardiograma mostra a atividade elétrica do coração. O ecocardiograma avalia estrutura, válvulas e função de bombeamento. O Holter registra o ritmo durante um período maior e ajuda a encontrar alterações intermitentes. Os exames laboratoriais medem colesterol, glicose, função renal e outros fatores que influenciam o coração.</p><h3>O que o médico considera</h3><p>Um exame normal não descarta completamente um problema e um exame alterado nem sempre significa doença grave. O contexto é o que orienta a decisão. Sempre que houver dúvida, o resultado precisa ser discutido com o profissional que acompanha o caso.</p><h3>Como usar essa informação</h3><p>Use o laudo para entender o que foi avaliado, mas não para se assustar sozinho ou buscar diagnóstico sem orientação. O papel do médico é conectar os exames aos sintomas, à rotina e ao risco individual.</p><div style="margin-top:20px; padding:18px; background:#f8fafc; border-left:4px solid #851e32; border-radius:10px;"><h3 style="margin-top:0; color:#851e32;">Tradução</h3><p>Os exames são uma fotografia do coração em determinado momento, não uma sentença isolada. Eles ajudam a ver o que está acontecendo com o ritmo e a função do coração, mas os médicos usam o conjunto de informações para decidir o melhor cuidado.</p></div>'
            ],
            'exercicios' => [
                'title' => 'Exercícios físicos e saúde do coração',
                'date' => '08 abr 2025',
                'summary' => 'Descubra como a prática regular de atividades físicas pode contribuir para a saúde cardiovascular.',
                'category' => 'Bem-estar',
                'image' => 'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=900&q=80',
                'content' => '<p>A atividade física regular pode contribuir para o condicionamento do coração, a circulação e o bem-estar. Caminhadas, dança, bicicleta e outras atividades podem fazer parte da rotina, respeitando as condições e os limites de cada pessoa.</p><h3>Comece com segurança</h3><p>Se você tem uma condição de saúde, sente sintomas durante o esforço ou está há muito tempo sem se exercitar, converse com um profissional de saúde antes de iniciar ou mudar sua rotina. Aumente a duração e a intensidade gradualmente.</p><h3>Faça da atividade um hábito</h3><p>Escolher algo agradável, definir horários realistas e fazer pausas quando necessário pode ajudar a manter a consistência. Interrompa o exercício e procure orientação se surgir dor no peito, falta de ar fora do habitual, tontura ou mal-estar.</p>'
            ],
            'alimentacao' => [
                'title' => 'Alimentação saudável e coração forte',
                'date' => '01 abr 2025',
                'summary' => 'Veja escolhas alimentares que ajudam a compor uma rotina equilibrada para a saúde do coração.',
                'category' => 'Alimentação',
                'image' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=900&q=80',
                'content' => '<p>Uma alimentação variada, com frutas, verduras, legumes, feijões e grãos integrais, pode fazer parte dos cuidados com a saúde cardiovascular. O equilíbrio é construído pelas escolhas do dia a dia, não por um alimento isolado.</p><h3>Pequenas mudanças possíveis</h3><p>Leia os rótulos, reduza o consumo frequente de alimentos ultraprocessados e procure alternativas com menos sal e açúcar. Preferências, necessidades e condições de saúde variam; um profissional pode ajudar a adequar as escolhas à sua rotina.</p><h3>Cuidados individualizados</h3><p>Não faça dietas restritivas nem altere tratamentos por conta própria. Em caso de doença ou uso de medicamentos, peça orientação médica ou nutricional personalizada.</p>'
            ],
            'estresse' => [
                'title' => 'Estresse e coração: entenda a relação',
                'date' => '28 mar 2025',
                'summary' => 'Conheça formas de incluir descanso, apoio e hábitos equilibrados na rotina.',
                'category' => 'Saúde mental',
                'image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=900&q=80',
                'content' => '<p>Períodos de estresse podem influenciar o sono, a disposição e os hábitos de saúde. Cuidar do bem-estar emocional é parte importante de uma rotina saudável, junto ao acompanhamento clínico quando necessário.</p><h3>Reserve momentos de pausa</h3><p>Atividades relaxantes, contato com pessoas de confiança, sono regular e movimento dentro das suas possibilidades podem ajudar no manejo do estresse. Cada pessoa encontra estratégias diferentes.</p><h3>Quando buscar apoio</h3><p>Se o estresse persistir, afetar suas atividades ou vier acompanhado de sintomas físicos preocupantes, converse com um profissional de saúde. Em uma emergência, procure atendimento imediatamente.</p>'
            ],
            'infarto' => [
                'title' => 'Infarto: conheça os sinais e como agir',
                'date' => '25 mar 2025',
                'summary' => 'Saiba reconhecer sintomas que precisam de atendimento de emergência e agir sem demora.',
                'category' => 'Doenças cardíacas',
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=900&q=80',
                'content' => '<p>Dor ou pressão no peito, falta de ar, suor frio, náusea ou desconforto que pode irradiar para braço, costas, pescoço ou mandíbula são sinais que podem indicar uma emergência. Os sintomas variam e nem sempre aparecem da mesma forma em todas as pessoas.</p><h3>O que fazer</h3><p>Se houver suspeita de infarto, procure atendimento de emergência imediatamente. No Brasil, ligue para o SAMU pelo 192. Não espere os sintomas passarem e não dirija se estiver passando mal.</p><h3>Informação importante</h3><p>Este conteúdo é educativo e não substitui avaliação médica. Em caso de dúvida diante de sintomas intensos ou repentinos, prefira buscar ajuda urgente.</p>'
            ],
            'circulacao' => [
                'title' => 'A grande viagem do sangue: como coração e vasos trabalham juntos',
                'date' => 'Fundamentação cardiovascular · 7 min',
                'summary' => 'Entenda o caminho do sangue, o papel das câmaras cardíacas e como artérias, veias e capilares mantêm o corpo abastecido.',
                'category' => 'Como o coração funciona',
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Profissional de saúde analisa uma ilustração do corpo humano',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?auto=format&fit=crop&w=900&q=80', 'alt' => 'Profissional examinando um modelo anatômico', 'caption' => 'A anatomia ajuda a visualizar a circulação.'],
                    ['image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', 'alt' => 'Equipe de saúde em ambiente clínico', 'caption' => 'O sistema circulatório integra coração e vasos.']
                ],
                'source' => 'Fundamentação teórica do TCC: sistema cardiovascular, vasos sanguíneos e circulação.',
                'keyPoints' => ['O coração impulsiona o sangue em circuitos coordenados.', 'Artérias, veias e capilares têm funções diferentes.', 'O fluxo contínuo leva oxigênio e nutrientes aos tecidos.'],
                'content' => '<p>O sistema cardiovascular funciona como uma rede de transporte. O coração atua como uma bomba muscular; os vasos conduzem o sangue; e os tecidos recebem oxigênio e nutrientes necessários para suas atividades.</p><h3>Dois circuitos que se complementam</h3><p>Na circulação pulmonar, o sangue com menos oxigênio sai do lado direito do coração em direção aos pulmões, onde ocorre a troca gasosa. Depois retorna ao lado esquerdo. Na circulação sistêmica, o coração envia sangue oxigenado para os órgãos e tecidos; após entregar parte do oxigênio, ele volta ao coração pelas veias.</p><h3>Artérias, veias e capilares</h3><p>As artérias conduzem o sangue para fora do coração e têm paredes adaptadas à pressão gerada pelos batimentos. Elas se ramificam em arteríolas, que ajudam a regular a distribuição do fluxo. As veias trazem o sangue de volta ao coração. Entre os dois sistemas, os capilares são vasos microscópicos onde ocorrem trocas de gases, nutrientes e resíduos metabólicos.</p><h3>Por que o fluxo é contínuo?</h3><p>O ciclo cardíaco alterna contração e relaxamento. Durante a sístole, o músculo cardíaco contrai e impulsiona o sangue. Na diástole, relaxa e as cavidades se enchem novamente. A coordenação entre essas fases mantém o fluxo circulatório.</p><h3>Uma ideia para guardar</h3><p>O coração não trabalha isoladamente: o funcionamento dos vasos, o volume de sangue e a atividade dos tecidos também participam da circulação. Por isso, a avaliação cardiovascular considera o organismo como um conjunto.</p>'
            ],
            'debitocardiaco' => [
                'title' => 'Cada batida conta: o que é débito cardíaco e hemodinâmica?',
                'date' => 'Fundamentação cardiovascular · 6 min',
                'summary' => 'Uma explicação visual sobre o volume de sangue bombeado por minuto e os fatores que influenciam o fluxo.',
                'category' => 'Fisiologia',
                'image' => 'https://images.unsplash.com/photo-1559757175-0eb30cd8c063?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Modelo de anatomia usado em aula de saúde',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', 'alt' => 'Profissional analisando informações clínicas', 'caption' => 'O fluxo sanguíneo depende de vários fatores.'],
                    ['image' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=900&q=80', 'alt' => 'Profissional de saúde observa um exame', 'caption' => 'A função circulatória é avaliada em contexto clínico.']
                ],
                'source' => 'Fundamentação teórica do TCC: débito cardíaco e fatores hemodinâmicos.',
                'keyPoints' => ['Débito cardíaco é o volume bombeado por minuto.', 'Frequência e volume por batimento influenciam esse resultado.', 'Pressão e resistência dos vasos também afetam o fluxo.'],
                'content' => '<p>Para entender a circulação, imagine o volume de sangue que o coração consegue impulsionar a cada minuto. Esse conceito é chamado de débito cardíaco e depende da frequência cardíaca e do volume ejetado em cada batimento.</p><h3>O que significa hemodinâmica?</h3><p>Hemodinâmica é o estudo do movimento do sangue e das forças envolvidas nesse fluxo. Entre os fatores importantes estão a pressão arterial, a resistência oferecida pelos vasos, o volume sanguíneo e a elasticidade vascular.</p><h3>O coração e os vasos em equilíbrio</h3><p>As arteríolas têm papel importante na resistência ao fluxo e ajudam a distribuir o sangue. Quando os vasos se contraem, o espaço para o sangue circular diminui; quando relaxam, aumenta. O corpo ajusta continuamente esse equilíbrio para atender às necessidades dos órgãos.</p><h3>O corpo adapta o bombeamento</h3><p>O sistema nervoso autônomo participa da regulação: a atividade simpática pode elevar a frequência cardíaca, enquanto a parassimpática tende a reduzi-la. Durante atividade física, o coração pode aumentar o bombeamento para atender à maior demanda dos músculos. Essa adaptação varia com idade, condicionamento e saúde.</p><h3>Por que isso importa?</h3><p>Débito cardíaco e pressão são conceitos relacionados, mas não são a mesma medida. A interpretação depende de avaliação clínica; não é possível deduzir a saúde de alguém apenas por um número isolado ou por uma fórmula.</p>'
            ],
            'aterosclerose' => [
                'title' => 'Quando as artérias estreitam: a história das placas de aterosclerose',
                'date' => 'Fundamentação cardiovascular · 7 min',
                'summary' => 'Veja como placas podem se formar nas artérias e por que o processo pode afetar o fluxo de sangue ao longo do tempo.',
                'category' => 'Artérias e circulação',
                'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Profissional conversa com paciente sobre saúde',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', 'alt' => 'Acompanhamento clínico em consultório', 'caption' => 'A prevenção inclui conhecer os fatores de risco.'],
                    ['image' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=900&q=80', 'alt' => 'Profissional de saúde revisa exames', 'caption' => 'Exames são interpretados junto ao histórico individual.']
                ],
                'source' => 'Fundamentação teórica do TCC: aterosclerose e redução do fluxo sanguíneo.',
                'keyPoints' => ['A aterosclerose envolve acúmulo de placas na parede arterial.', 'O estreitamento pode reduzir a passagem de sangue.', 'Prevenção e acompanhamento consideram diversos fatores de risco.'],
                'content' => '<p>A aterosclerose é um processo em que placas se acumulam na parede das artérias. Com o tempo, esse acúmulo pode deixar o vaso mais estreito e reduzir o fluxo sanguíneo. Quando as artérias que irrigam o coração são afetadas, a chegada de oxigênio ao músculo cardíaco pode ficar comprometida.</p><h3>Um processo gradual</h3><p>As alterações podem se desenvolver durante anos e não necessariamente provocam sinais percebidos no início. A extensão e o impacto dependem de quais vasos estão envolvidos, de quanto o fluxo foi alterado e das condições gerais da pessoa.</p><h3>Como se relaciona ao infarto?</h3><p>Se o fluxo por uma artéria coronária fica gravemente reduzido ou é interrompido, uma região do músculo cardíaco pode deixar de receber oxigênio suficiente. A lesão do tecido cardíaco é uma das características do infarto do miocárdio. Suspeitas de infarto são emergências.</p><h3>O que pode ajudar a reduzir riscos</h3><p>Pressão alta, alterações do colesterol, tabagismo, sedentarismo e outros fatores podem influenciar o risco cardiovascular. O acompanhamento individual permite identificar o que pode ser modificado e quais cuidados fazem sentido para cada pessoa.</p><h3>Sem conclusões por conta própria</h3><p>A presença ou ausência de sintomas não confirma nem exclui aterosclerose. A avaliação pode envolver história clínica, exame físico e exames selecionados por profissionais de saúde.</p>'
            ],
            'prevencao-risco' => [
                'title' => 'Seu coração em perspectiva: fatores de risco que merecem atenção',
                'date' => 'Fundamentação cardiovascular · 6 min',
                'summary' => 'Conheça fatores que podem ser modificados e outros que fazem parte do histórico, sem culpabilização e com foco em cuidado.',
                'category' => 'Prevenção',
                'image' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Refeição variada com vegetais e alimentos frescos',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=900&q=80', 'alt' => 'Pessoa praticando atividade física', 'caption' => 'Movimento adequado às possibilidades pode fazer parte do cuidado.'],
                    ['image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=900&q=80', 'alt' => 'Pessoa relaxando em ambiente tranquilo', 'caption' => 'Sono e bem-estar também integram uma rotina saudável.']
                ],
                'source' => 'Fundamentação teórica do TCC: fatores de risco modificáveis e não modificáveis.',
                'keyPoints' => ['Alguns fatores podem ser reduzidos com apoio e acompanhamento.', 'Idade, genética e histórico familiar não são escolhas pessoais.', 'A prevenção deve respeitar o contexto e as condições de cada pessoa.'],
                'content' => '<p>O risco cardiovascular não é determinado por um único hábito nem representa uma falha pessoal. Ele resulta da combinação de fatores relacionados à saúde, ao histórico familiar e às condições de vida.</p><h3>Fatores que podem ser trabalhados</h3><p>O material de fundamentação destaca obesidade, sedentarismo, consumo excessivo de sal, tabagismo, álcool e estresse entre os fatores modificáveis. Pressão arterial e alterações metabólicas também fazem parte da avaliação clínica. Mudanças sustentáveis, orientação profissional e acesso a cuidados podem apoiar a redução de riscos.</p><h3>Fatores que não dependem de escolhas</h3><p>Idade, genética, sexo e histórico familiar não podem ser modificados. Conhecê-los é útil para conversar com profissionais de saúde e definir uma estratégia de acompanhamento adequada, sem culpa ou estigma.</p><h3>Pequenos passos, apoio real</h3><p>Atividade física apropriada, alimentação equilibrada, parar de fumar com suporte quando necessário e acompanhar a pressão são exemplos de medidas que podem ser discutidas em consulta. Nem todas são indicadas da mesma forma para todas as pessoas.</p><h3>Prevenção é acompanhamento</h3><p>Uma medida isolada não resume seu risco. Converse com um profissional sobre seu histórico e suas dúvidas; juntos, vocês podem escolher metas realistas e acompanhar a evolução com segurança.</p>'
            ],
            'pressao-diagnostico' => [
                'title' => 'Pressão alta sem mistério: da medição ao acompanhamento',
                'date' => 'Fundamentação cardiovascular · 7 min',
                'summary' => 'Entenda pressão sistólica e diastólica, por que uma leitura isolada não basta e como a pressão pode ser monitorada.',
                'category' => 'Pressão arterial',
                'image' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Profissional de saúde realizando uma avaliação clínica',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=900&q=80', 'alt' => 'Profissional explica informações de saúde a uma pessoa', 'caption' => 'Converse sobre medidas e dúvidas com a equipe de saúde.'],
                    ['image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', 'alt' => 'Acompanhamento de saúde em consultório', 'caption' => 'O acompanhamento pode reunir medidas feitas em diferentes momentos.']
                ],
                'source' => 'Fundamentação teórica do TCC: hipertensão arterial, diagnóstico e monitorização.',
                'keyPoints' => ['Sistólica e diastólica correspondem a fases diferentes do ciclo cardíaco.', 'A pressão pode ser acompanhada em consultório e em casa.', 'Um profissional interpreta as medidas e define a conduta.'],
                'content' => '<p>A pressão arterial descreve a força que o sangue exerce contra as paredes das artérias. Ela é expressa por dois valores: a pressão sistólica, associada à contração do coração, e a diastólica, observada durante o relaxamento entre batimentos.</p><h3>Uma medida é apenas um retrato do momento</h3><p>Atividade recente, estresse, postura, repouso, equipamento e técnica podem influenciar a leitura. Por isso, o diagnóstico de hipertensão considera medidas repetidas e a avaliação profissional, não uma leitura casual isolada.</p><h3>Consultório, MAPA e medição residencial</h3><p>Além da aferição no consultório, o profissional pode recomendar monitorização ambulatorial da pressão arterial (MAPA), que registra medidas ao longo de um período, ou acompanhamento residencial com técnica adequada. A escolha depende do caso e da disponibilidade.</p><h3>Como se preparar para conversar sobre pressão</h3><ul><li>Anote quando as medidas foram feitas e as circunstâncias, caso a equipe recomende medições em casa.</li><li>Leve dúvidas e, se solicitado, os registros das medidas para a consulta.</li><li>Não altere medicamentos ou metas de tratamento sem orientação.</li></ul><h3>Por que acompanhar?</h3><p>A hipertensão pode não causar sintomas perceptíveis. O acompanhamento permite reconhecer padrões e conversar sobre os cuidados necessários antes que complicações ocorram.</p>'
            ],
            'insuficiencia-cardiaca' => [
                'title' => 'Cansaço e falta de ar: entendendo a insuficiência cardíaca',
                'date' => 'Fundamentação cardiovascular · 7 min',
                'summary' => 'Insuficiência cardíaca não significa que o coração parou: veja o que essa condição descreve e por que os sintomas precisam de avaliação.',
                'category' => 'Doenças cardíacas',
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=80',
                'imageAlt' => 'Profissional conversa com paciente durante acompanhamento',
                'gallery' => [
                    ['image' => 'https://images.unsplash.com/photo-1584982751601-97dcc096659c?auto=format&fit=crop&w=900&q=80', 'alt' => 'Avaliação clínica com profissional de saúde', 'caption' => 'A avaliação reúne sintomas, histórico e exames.'],
                    ['image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=900&q=80', 'alt' => 'Equipe de saúde revisa informações clínicas', 'caption' => 'O acompanhamento é individualizado.']
                ],
                'source' => 'Fundamentação teórica do TCC: insuficiência cardíaca e avaliação clínica.',
                'keyPoints' => ['A condição descreve dificuldade do coração em atender às necessidades do corpo.', 'Cansaço, falta de ar e inchaço podem exigir avaliação.', 'A investigação e o cuidado são individualizados.'],
                'content' => '<p>Insuficiência cardíaca é uma condição em que o coração não consegue bombear sangue adequadamente para atender às necessidades do organismo. O nome pode assustar, mas não quer dizer que o coração parou: descreve uma alteração na sua função que precisa ser avaliada e acompanhada.</p><h3>O que a pessoa pode perceber?</h3><p>Entre os sinais que podem ocorrer estão falta de ar, cansaço, inchaço e redução da tolerância às atividades. Esses sintomas também podem aparecer em outras condições; sua causa não pode ser determinada apenas por uma leitura ou por este artigo.</p><h3>Como é feita a avaliação?</h3><p>Profissionais consideram sintomas, histórico, exame físico e testes apropriados. Eletrocardiograma, ecocardiograma, exames laboratoriais ou outros recursos podem ser solicitados de acordo com a suspeita clínica.</p><h3>O cuidado não é igual para todos</h3><p>O plano pode incluir acompanhamento, medicamentos e orientações de rotina, conforme a situação individual. Não interrompa nem ajuste medicamentos por conta própria. Se houver piora importante da falta de ar, desmaio, dor no peito ou outro sintoma grave, procure atendimento de urgência.</p><h3>Uma conversa útil na consulta</h3><p>Registre quando os sintomas começaram, o que parece piorá-los e quais medicamentos são usados. Essas informações podem ajudar a equipe de saúde na avaliação.</p>'
            ]
        ];

        $photoIds = [
            'hipertensao' => ['pexels:5327585', 'unsplash:1576091160550-2173dba999ef', 'pexels:7089401', 'unsplash:1576091160399-112ba8d25d1d'],
            'insuficiencia' => ['pexels:8376277', 'unsplash:1516549655169-df83a0774514', 'pexels:7581576', 'unsplash:1579684385127-1ef15d508118'],
            'arritmia' => ['pexels:6823568', 'unsplash:1584515933487-779824d29309', 'pexels:5452201', 'unsplash:1584982751601-97dcc096659c'],
            'colesterol' => ['pexels:8460157', 'unsplash:1490645935967-10de6ba17061', 'pexels:5327656', 'unsplash:1540189549336-e6e99c3679fe'],
            'exames' => ['pexels:3768131', 'unsplash:1559757175-0eb30cd8c063', 'pexels:5207103', 'unsplash:1586773860418-d37222d8fce3'],
            'exercicios' => ['pexels:7659570', 'unsplash:1538805060514-97d9cc17730c', 'pexels:263337', 'unsplash:1517836357463-d25dfeac3438'],
            'alimentacao' => ['pexels:4047077', 'unsplash:1512621776951-a57141f2eefd', 'pexels:4225920', 'unsplash:1547592180-85f173990554'],
            'estresse' => ['pexels:5863396', 'unsplash:1506126613408-eca07ce68773', 'pexels:4269205', 'unsplash:1518611012118-696072aa579a'],
            'infarto' => ['pexels:6129681', 'unsplash:1552674605-db6ffd4facb5', 'pexels:8942527', 'unsplash:1573496359142-b8d87734a5a2'],
            'circulacao' => ['pexels:4031818', 'unsplash:1581056771107-24ca5f033842', 'pexels:5214958', 'unsplash:1583911860205-72f8ac8ddcbe'],
            'debitocardiaco' => ['pexels:6843856', 'unsplash:1607746882042-944635dfe10e', 'pexels:5327647', 'unsplash:1551836022-d5d88e9218df'],
            'aterosclerose' => ['pexels:3825586', 'unsplash:1532938911079-1b06ac7ceec7', 'pexels:5327921', 'unsplash:1612349317150-e413f6a5b16d'],
            'prevencao-risco' => ['pexels:7659560', 'unsplash:1612531386530-97286d97c2d2', 'pexels:7659580', 'unsplash:1628348068343-c6a848d2b6dd'],
            'pressao-diagnostico' => ['pexels:5327658', 'unsplash:1631815588090-d4bfec5b1ccb', 'pexels:5327654', 'unsplash:1622253692010-333f2da6031d'],
            'insuficiencia-cardiaca' => ['pexels:7089020', 'unsplash:1582750433449-648ed127bb54', 'pexels:8460371', 'unsplash:1511174511562-5f7f18b874f8']
        ];
        $photoUrl = static function ($photoId, $width) {
            if (strpos($photoId, 'pexels:') === 0) {
                $id = substr($photoId, 7);
                return 'https://images.pexels.com/photos/' . $id . '/pexels-photo-' . $id . '.jpeg?auto=compress&cs=tinysrgb&w=' . $width;
            }

            $id = substr($photoId, 9);
            return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=crop&w=' . $width . '&q=80';
        };
        $galleryCaptions = [
            'Uma visão complementar para entender o tema.',
            'O contexto individual ajuda a orientar o cuidado.',
            'Informação e acompanhamento fazem parte da prevenção.'
        ];

        $usedPhotoIds = [];
        foreach ($articles as $articleId => &$article) {
            if (!isset($photoIds[$articleId]) || count($photoIds[$articleId]) !== 4) {
                throw new LogicException('Cada artigo deve ter uma capa e três imagens de apoio exclusivas.');
            }

            $photos = $photoIds[$articleId];
            foreach ($photos as $photoId) {
                if (isset($usedPhotoIds[$photoId])) {
                    throw new LogicException('As imagens dos artigos devem ser exclusivas.');
                }
                $usedPhotoIds[$photoId] = true;
            }

            $topic = $article['category'] ?? 'saúde cardiovascular';
            $article['image'] = $photoUrl($photos[0], 1200);
            $article['imageAlt'] = 'Fotografia ilustrativa sobre ' . $topic . ': ' . $article['title'];
            $article['gallery'] = [];

            for ($index = 1; $index < count($photos); $index++) {
                $article['gallery'][] = [
                    'image' => $photoUrl($photos[$index], 1100),
                    'alt' => 'Imagem de apoio sobre ' . $topic . ', relacionada a ' . $article['title'],
                    'caption' => $galleryCaptions[$index - 1]
                ];
            }
        }
        unset($article);

        return $articles;
    }
}

if (!function_exists('getDashboardBlogHtml')) {
    function getDashboardBlogHtml()
    {
        $articles = getBlogArticles();
        $featuredOrder = ['hipertensao', 'circulacao', 'debitocardiaco', 'aterosclerose', 'prevencao-risco', 'pressao-diagnostico', 'insuficiencia-cardiaca', 'colesterol', 'exercicios', 'alimentacao', 'estresse', 'infarto'];
        $featuredArticleId = 'hipertensao';
        $featuredArticle = $articles[$featuredArticleId];
        $cardsHtml = '';

        foreach ($featuredOrder as $articleId) {
            if (!isset($articles[$articleId])) {
                continue;
            }
            $article = $articles[$articleId];
            $category = $article['category'] ?? 'Prevenção';
            $searchText = $article['title'] . ' ' . $category . ' ' . $article['summary'] . ' ' . implode(' ', $article['keyPoints'] ?? []);
            $cardsHtml .= '<button type="button" class="blog-article-card" data-search="' . htmlspecialchars(mb_strtolower($searchText), ENT_QUOTES, 'UTF-8') . '" onclick="openArticle(\'' . htmlspecialchars($articleId, ENT_QUOTES, 'UTF-8') . '\', event)">'
                . '<span class="blog-card-image"><img src="' . htmlspecialchars($article['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($article['imageAlt'] ?? '', ENT_QUOTES, 'UTF-8') . '" loading="lazy" onerror="this.parentElement.classList.add(\'image-unavailable\');this.remove()"><span class="blog-category">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</span></span>'
                . '<span class="blog-card-copy"><strong>' . htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') . '</strong><span class="blog-card-summary">' . htmlspecialchars($article['summary'], ENT_QUOTES, 'UTF-8') . '</span><span class="blog-card-date"><i class="far fa-calendar"></i> ' . htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8') . '<i class="fas fa-chevron-right"></i></span></span>'
                . '</button>';
        }

        foreach ($articles as $articleId => $article) {
            if (in_array($articleId, $featuredOrder, true)) {
                continue;
            }
            $category = $article['category'] ?? 'Saúde';
            $searchText = $article['title'] . ' ' . $category . ' ' . $article['summary'] . ' ' . implode(' ', $article['keyPoints'] ?? []);
            $cardsHtml .= '<button type="button" class="blog-article-card" data-search="' . htmlspecialchars(mb_strtolower($searchText), ENT_QUOTES, 'UTF-8') . '" onclick="openArticle(\'' . htmlspecialchars($articleId, ENT_QUOTES, 'UTF-8') . '\', event)">'
                . '<span class="blog-card-image"><img src="' . htmlspecialchars($article['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($article['imageAlt'] ?? '', ENT_QUOTES, 'UTF-8') . '" loading="lazy" onerror="this.parentElement.classList.add(\'image-unavailable\');this.remove()"><span class="blog-category">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</span></span>'
                . '<span class="blog-card-copy"><strong>' . htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') . '</strong><span class="blog-card-summary">' . htmlspecialchars($article['summary'], ENT_QUOTES, 'UTF-8') . '</span><span class="blog-card-date"><i class="far fa-calendar"></i> ' . htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8') . '<i class="fas fa-chevron-right"></i></span></span>'
                . '</button>';
        }

        return '<div class="blog-editorial">'
            . '<label class="blog-search"><i class="fas fa-magnifying-glass"></i><input id="blogSearchInput" type="search" placeholder="Buscar no blog..." oninput="filterBlogArticles(this.value)" aria-label="Buscar artigos no blog"></label>'
            . '<section class="blog-editorial-hero">'
            . '<div class="blog-editorial-copy"><span class="blog-editorial-kicker">CARDIOWEB · SAÚDE EM FOCO</span><h2>Conheça melhor<br>o seu <em>coração</em></h2><p>Informação simples para entender como o coração funciona e descobrir hábitos que ajudam a cuidar da saúde cardiovascular.</p><span class="blog-editorial-note"><i class="fas fa-heart-pulse"></i> Conteúdo educativo, feito para você.</span></div>'
            . '<div class="blog-heart-stage"><span class="blog-heart-fallback"><i class="fas fa-heart-pulse"></i><small>Modelo anatômico 3D</small></span><model-viewer src="assets/models/stylized-human-heart.glb" alt="Modelo tridimensional anatômico de um coração humano" camera-controls auto-rotate rotation-per-second="18deg" shadow-intensity="1" exposure="1" loading="lazy" reveal="auto" onload="this.parentElement.classList.add(\'model-loaded\')"></model-viewer></div>'
            . '<aside class="blog-editorial-feature"><span class="blog-editorial-feature-icon"><i class="fas fa-book-open"></i></span><span class="blog-editorial-kicker">LEITURA EM DESTAQUE</span><h3>' . htmlspecialchars($featuredArticle['title'], ENT_QUOTES, 'UTF-8') . '</h3><p>' . htmlspecialchars($featuredArticle['summary'], ENT_QUOTES, 'UTF-8') . '</p><button type="button" onclick="openArticle(\'' . htmlspecialchars($featuredArticleId, ENT_QUOTES, 'UTF-8') . '\', event)">Ler artigo <i class="fas fa-arrow-right"></i></button></aside>'
            . '</section>'
            . '<section class="blog-library" aria-labelledby="blogLibraryTitle"><div class="blog-library-heading"><div><span class="blog-editorial-kicker">APRENDA NO SEU RITMO</span><h3 id="blogLibraryTitle">Saúde do coração, sem complicação</h3></div><span class="blog-library-count"><i class="fas fa-heart"></i> ' . count($articles) . ' conteúdos</span></div>'
            . '<div class="blog-articles-grid" id="blogArticlesGrid">' . $cardsHtml . '<p class="blog-empty-state" id="blogEmptyState" hidden>Nenhum artigo corresponde à sua busca.</p></div></section>'
            . '<p class="blog-disclaimer">Os textos têm finalidade educativa e não substituem consulta, diagnóstico ou orientação de um profissional de saúde.</p>'
            . '</div>';
    }
}

if (!function_exists('getBlogHtml')) {
    function getBlogHtml()
    {
        return getDashboardBlogHtml();
    }
}

?>
