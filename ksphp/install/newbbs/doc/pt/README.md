# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
Uma versão aprimorada da portabilidade em PHP do KuzuhaScript (くずはすくりぷと).
A partir de 2024/10/16, funciona apenas com PHP8+.
A última versão para PHP legado (de 4.1.0 a 7.4) pode ser encontrada aqui: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


Este programa é baseado na versão modificada de 2005/04/01 do KuzuhaScriptPHP (くずはすくりぷとPHP).

Este programa foi originalmente traduzido para o inglês por [Anonymous-san do Strange World@Heyuri.net](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555), e diversos desenvolvedores anônimos do Heyuri contribuíram desde então.


* [KuzuhaScriptPHP (espelho)](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [Versão modificada de 2005/04/01](http://qptn.x.fc2.com/up/dauso0073.zip)

## Informações do mantenedor
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Processo de instalação (apenas para referência)
1. Descompacte o arquivo ZIP baixado
2. Abra e configure o conf.php
3. Envie os arquivos para o servidor usando um cliente FTP ou similar (é uma boa ideia criar um diretório dedicado para não misturar com outros arquivos)
4. Configure as permissões conforme descrito no readme.md
5. Abra um navegador, acesse `_setup.php` (uma ferramenta independente incluída no pacote; não faz parte do conf.php nem do install.php) e defina a senha de administrador ali. Ao concluir, a ferramenta renomeia a si mesma para um nome de sua escolha (é sugerido um nome padrão difícil de adivinhar) -- anote o novo nome/URL, pois você precisará dele para alterar a senha depois.
6. (Não é mais necessário -- a senha de administrador agora é gravada diretamente pelo `_setup.php` em seu próprio arquivo, não no conf.php.)
7. Abra seu navegador, acesse bbs.php e veja se consegue postar
8. Acesse pelo navegador a URL onde ficam os arquivos de log (bbs.log, arquivos dentro de log/, etc.) e verifique se consegue vê-los (se conseguir, esconda-os com .htaccess ou similar)

## Solução de problemas
### Atualizando um site existente (migração da senha de administrador)
A partir do RC8, a senha de administrador (ADMINPOST/ADMINKEY) fica fora do conf.php, em um arquivo de nome fixo chamado `local.php`, que não faz parte deste modelo e nunca é sobrescrito pelo install.php. Quando o install.php detecta que o site que você está atualizando já possui um ADMINPOST não vazio em seu conf.php existente, ele exibirá um formulário de migração pedindo sua **senha antiga** (para confirmar que é realmente você) e uma **nova senha** a ser definida. A palavra-chave do modo administrador (ADMINKEY) é transferida automaticamente -- não há um campo separado para ela.

- Se a senha antiga for verificada com sucesso, o `local.php` é gravado com a nova senha e a instalação prossegue normalmente.
- Se a verificação falhar, **toda a instalação é abortada** (nenhum arquivo é instalado, nada é copiado como backup ou sobrescrito), para impedir que outra pessoa sequestre a conta de administrador de um site que não controla.
- Se você realmente esqueceu a senha antiga, abra o conf.php existente no servidor e apague manualmente o valor de `ADMINPOST` (defina-o como uma string vazia). Isso faz com que o install.php trate o site como uma instalação nova, permitindo que você defina uma senha totalmente nova depois pelo `_setup.php`, da mesma forma que em uma instalação nova.

## Configurações de permissão recomendadas
Permissões incorretas podem causar problemas e vazamento de dados (como o endereço IP ou host remoto de um post), então certifique-se de que estejam configuradas corretamente.

```
[Estrutura de arquivos]
|-- bbs.cnt   600 (gravável)      Arquivo de registro da lista de participantes (arquivo de texto vazio)
|-- bbs.log   600 (gravável)      Arquivo de log (arquivo de texto vazio)
|-- conf.php  644 (somente leitura)  Para configuração
|-- bbs.php 644 (somente leitura)    Script principal do fórum
|-- readme.md                     Instruções (este arquivo)
|
|-- vanish.js                     Script para filtragem de palavras
|
|
+-- archive/  700 (gravável)      Diretório de armazenamento de arquivos ZIP
+-- count/    700 (gravável)      Diretório de saída do contador
+-- log/      700 (gravável)      Diretório de armazenamento dos arquivos de log de mensagens (logs brutos)
+-- sub/      755 (somente leitura)  Diretório de armazenamento de submódulos
    |
    |-- bbsadmin.php    644 (somente leitura)  Módulo de administração
    |-- bbslog.php      644 (somente leitura)  Módulo do visualizador de logs
    |-- bbstree.php     644 (somente leitura)  Módulo de visualização em árvore
    |-- phpzip.inc.php  644 (somente leitura)  Biblioteca de criação de arquivos ZIP
```

Se o PHP rodar como módulo do Apache, o bbs.php pode permanecer como somente leitura,
mas se rodar como CGI, o bbs.php precisa ser definido como 755 (executável).

## Notas:
### Lista de significados de bbs.php?m=*

| Parâmetro | Significado |
| --- | --- |
| m=g | Busca no log de mensagens |
| m=ad | Modo administrador |
| m=tree | Visualização em árvore |
| m=p | Postar/recarregar |
| m=c | Configurações |
| m=f | Tela de resposta |
| m=t | Exibição de tópico |
| m=s | Busca por autor |
| m=u | Executar UNDO |

## Histórico
### Versão Cion (しおん)
* 2003/01/21 início do trabalho
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### Não oficial
* 2005/04/01 0.0.8alpha (não oficial) Versão modificada lançada por um voluntário (espelho: http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip)

### Datas desconhecidas (versão Hirugatake/蛭ヶ岳)
* Interface corrigida, mais fácil de usar em smartphones etc.
* Migrado para UTF-8 (＠Links)
* PHPZip atualizado para v1.2
* Diversas outras correções (não registradas)

### Datas desconhecidas
* Pequena mudança no estilo de codificação
* Corrigido bug em respostas
* Removido jcode-LE
* Corrigido problema em que as configurações do usuário não eram refletidas(?)
* Templates deixaram de ser uma preocupação
* Resolvida implementação misteriosa da classe func (incompleta)
* Preparação para suporte ao PHP7.x

### 2018/10/12
* Nome alterado para "KuzuhaScriptPHP+" (くずはすくりぷとPHP+)
* Dados de formulário ausentes invalidados devido a verificação falha
* Pequenas mudanças na interface

### 2018/11/18
* Aplicadas as correções de visualização em árvore de Motoi(gikonekos)
* vanish.js incorporado

### 2019/11/02
* Removida a visualização EZweb (HDML)
* Removida a visualização imode

### 2019/11/02
* Removida a visualização EZweb (HDML)
* Removida a visualização imode

### 2020/02/11
* Aplicadas as correções de bugs de visualização em árvore de Motoi(gikonekos)

### 2020/03/15
* Contadores separados por vírgulas

### 2020/03/29
* Adicionada a função de incorporação de YouTube de Motoi(gikonekos)

### 2021/03/08
* Mudanças de design (caixas de texto, etc.)
* conf.php (expressões e valores padrão alterados)

### 2021/07/03
* Adicionado o 2chtrip(20210625) de Motoi(gikonekos)
* Removido bbs.cgi

### 2021/07/27
* Corrigido um problema em que a senha de administrador vazava ao usar senha de administrador e trip juntos (Motoi(gikonekos))
* Número máximo de caracteres para Nome, E-mail e Título agora pode ser configurado
* Descrição movida de bbs.php para readme.md

### 2022/05/06
* bbs.php movido para index.php
* Pequenas correções na interface

### 20221127
* sub/patTemplate.php: aplicadas as correções de Motoi(gikonekos)

### 20230118
* Corrigido um bug em que a árvore não era exibida corretamente

### 20230520
* Aplicada a prevenção de exposição do caminho de administração de Motoi(gikonekos) (20210923)
* Aplicada a correção de prevenção de vazamento de senha de apelido de Motoi(gikonekos) (20210923)

### Datas desconhecidas (versão Heyuri)
* Totalmente traduzido para o inglês
* Adicionados botões de emoticons (kaomoji)
* Altura da linha alterada para 1
* CSS de quebra de linha do navegador comentado
* Adicionado JavaScript para facilitar que os usuários quebrem linhas
* Comentada a função de incorporação de YouTube de Motoi(gikonekos)
* Implementado JavaScript para incorporação de YouTube
* Adicionado JavaScript de miniaturas de imagem, por padrão configurado para funcionar apenas com o Uploader@Heyuri

### 2024/10/16
* Migrado para PHP8
* index.php renomeado de volta para bbs.php
* Arquivos JS movidos para um diretório separado

### 2025/04/29
* Corrigido redirecionamento que não funcionava após aplicar as configurações do usuário
* Removidas configurações de personalização não utilizadas do conf.php

### 2025/06/07
* Adicionado suporte para endereços IPv6
* Configurações de cabeçalho http alteradas para permitir incorporação de qualquer lugar

### 2025/09/09
* Corrigidos bloqueios de host

### 2025/11/08
* Adicionado CSS de rolagem vertical para linhas longas (somente PC)
* Botões de emoticons transformados em fieldset
* Corrigido bug em que clicar em Configurações do usuário enviava o post se o formulário não estivesse vazio
* O ayashii breaker agora avisa o usuário com um brilho para posts com linhas longas

### 2025/11/09
* Adicionada internacionalização completa para japonês, com o inglês agora opcional
* Software renomeado de volta para KuzuhaScriptPHP+ a partir de KuzuhaScriptPHP+EN

### 2026/03/10
* As chaves de administrador agora podem ser combinadas com um tripcode, implementado por Motoi(gikonekos)

### 2026/03/15
* A forma de entrar no menu de administração foi alterada para um link no topo
* Menu de administrador redesenhado, também alterado para usar sessões por segurança e conveniência

### 2026/06/27
* Adicionados auxiliares de filtragem e seleção em massa à tela de administração de exclusão de posts

### 2026/07/18
* O texto de interface de gikoneko.php / gikonekoadd.php agora é localizado pelo mecanismo padrão `$MSG` (22 novas chaves adicionadas a language/*.txt)
* gikoneko.php: o arquivo de dados de sorte agora é criado automaticamente quando ausente, então `file()` não emite mais um aviso bruto do PHP na saída da página
* gikonekoadd.php: corrigida uma incompatibilidade no caminho do arquivo de dados (`../cgi-bin/...`) que fazia com que palavras recém-ensinadas nunca chegassem ao pool de sorte de gikoneko.php; ambos os scripts agora referenciam o mesmo arquivo de dados no nível raiz
* ayashiibreaker.js atualizado para v0.4.0: as linhas em japonês agora quebram por contagem de caracteres com regras de kinsoku shori (禁則処理), em vez de depender de quebra de linha por palavras delimitadas por espaço

### 2026/07/19
* bbs.php: a exibição principal do fórum (`getdispmessage()`) e o ramo de log atual de `msgsearchlist()` agora transmitem o arquivo de log linha por linha (`Func::fgetline()`) em vez de carregar o arquivo inteiro na memória via `file()`. O pico de memória para uma visualização de página normal não escala mais com `LOGSAVE` / tamanho do arquivo de log (verificado: pico de ~131MB -> ~2MB em um log de 102.300 linhas / ~58MB; um pico de tráfego simulado com 100 requisições simultâneas não causa mais falhas por falta de memória (OOM), ao contrário da implementação anterior)
* bbs.php: removido o código de incorporação de YouTube que estava comentado há muito tempo (substituído por ytthumb.js desde 2024/10/16)
* bbs.php / conf.php: se "Gikoneko-to-issho" é mostrado quando não há posts não lidos agora é configurável via `GIKONEKO_TOISSHO` (1=ativado, 0=desativado, padrão 1); desativá-lo volta para a antiga mensagem simples de "nenhum post não lido"
* install.php: adicionado um fluxo de entrada de texto "adicionar novo destino de instalação" para pastas não encontradas pela varredura de proximidade (com validação contra path traversal e uma etapa de confirmação antes do uso)
* install.php: adicionada alternância de idioma da interface entre japonês/inglês (padrão japonês), incluindo mensagens de log de instalação traduzidas
* install.php: adicionada uma proteção de segurança que rejeita instalações direcionadas à raiz do sistema de arquivos, caminhos excessivamente rasos, ou a própria pasta install/
* install.php: arquivos existentes agora são movidos para backup via `rename()` (atômico) em vez de `copy()`; falhas de backup/renomeação por arquivo agora pulam apenas aquele arquivo em vez de abortar toda a execução; uma falha ao gravar o novo arquivo reverte automaticamente o original que havia sido movido, e as falhas também são registradas em `install/backup/install-errors-YYYY-MM-DD.txt` para revisão posterior

## A fazer:
* (Implementado em 2026-08-01) install.php: uma etapa de "ajuste" do conf.php durante a migração -- campos de checkbox/radio/lista/texto pré-preenchidos a partir da mesclagem automática, destaque de campos obrigatórios, validação do lado do servidor com rollback por arquivo, alternância pessoal opcional (padrão LIGADO), traduções completas em 7 idiomas.
* (Implementado em 2026-08-01) Correções de interface solicitadas após testes reais em qptns.com: (1) todas as antigas checkboxes (chaves booleanas 0/1) agora são exibidas como um grupo de rádio de 2 opções, para consistência com as chaves de rádio de 3 opções existentes e para atender ao feedback de que "a checkbox era confusa"; (2) ZIPDIR, OLDLOGFILEDIR e CNTFILENAME não são mais marcados como obrigatórios na tela de revisão -- os próprios comentários do conf.php documentam que vazio é um estado válido de "recurso desativado" para esses três (verificado lendo o texto do comentário real de cada chave de caminho manual; as outras chaves de caminho manual -- LOGFILENAME, COUNTFILE, GIKONEKO_KOTOBA_FILE, UPLOADDIR, UPLOADIDFILE -- não têm tal comentário e permanecem obrigatórias).
* (Corrigido em 2026-08-01, causa raiz) bug do analisador de limites de entrada de `ksphp_conf_parse_entries()` (veja Bugs conhecidos para o relato original): a etapa de extração de chave em `ksphp_conf_merge()`, `ksphp_conf_build_review()`, `ksphp_conf_apply_review()` e `ksphp_parse_module_array()` agora remove as linhas de comentário *iniciais* de uma entrada (via um novo auxiliar `ksphp_conf_entry_split_lead_comments()`) antes de executar a expressão regular de nome de chave, para que um exemplo comentado contendo seu próprio `'KEY' => ...` não seja mais atribuído erroneamente como a próxima chave real. O texto original do comentário é preservado literalmente no conf.php gravado (apenas a entrada de correspondência de chave interna é afetada, nunca a saída). O fallback defensivo do lado do install.php (forçando esses campos para uma exibição "raw" não editável) é superado por esta correção, mas mantido como uma rede de segurança. Verificado contra o caso relatado de HOSTNAME_POSTDENIED/TMPL_MSG/TMPL_ENVLIST, uma sequência CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC, e uma entrada de array aninhado no estilo HANDLENAMES (verificação de regressão para a detecção de chave externa da qual a correspondência não gulosa depende).
* (Implementado em 2026-08-01) a tela de revisão do conf.php agora mostra o texto de comentário de cada configuração (o conf.php já traz comentários pareados em japonês/inglês por chave) como uma linha de ajuda abaixo do nome da chave na tabela de revisão, via um novo auxiliar `ksphp_conf_entry_comment_text()`. Divisores decorativos de seção (`#---- ... ----`) e notas apenas para tradutores (`## TL note: ...`) são filtrados. Escopo deliberadamente leve: o texto exibido é o comentário original da fonte do conf.php (japonês + inglês), não traduzido para os outros 5 idiomas da interface de instalação -- depende do recurso de tradução do navegador do leitor para isso. A tradução completa por chave para todos os 7 idiomas da interface de instalação (~98 chaves) foi discutida e adiada como uma tarefa futura maior e separada (veja mais abaixo nesta lista).
* Não iniciado: tradução completa em 7 idiomas do texto de ajuda de cada chave do conf.php (atualmente apenas texto fonte em japonês/inglês, veja acima). O escopo é de aproximadamente 98 chaves; exigiria novas chaves `install/language/*.txt` (ex.: `CONF_HELP_<KEY>`) para coreano, português, turco, zh-hans, zh-hant.

  **(Atualização de 2026-08-02) Concluído, incluindo uma correção de bug encontrada no processo.** A lógica de divisão de comentários atribuía incorretamente o texto de ajuda por uma chave de diferença para 6 configurações do conf.php que usam comentários na mesma linha em vez de blocos de comentário à frente (C_A_COLOR/C_A_VISITED/C_A_ACTIVE/C_A_HOVER/C_SUBJ/C_ERROR -- as configurações de cor de link/título/erro); corrigido por meio de novos auxiliares `ksphp_conf_entry_trailing_comment()` / `ksphp_conf_build_help_texts()`. Além disso, foram adicionadas 98 entradas `CONF_HELP_<KEY>` a cada um dos 7 arquivos `install/language/*.txt`, com uma busca `ksphp_conf_help_text()` que recorre à extração de comentário bruto do conf.php (agora corrigida) para qualquer chave ainda sem tradução. Verificado de ponta a ponta via servidor embutido do PHP contra o endpoint real `?ajax=1&action=conf_review` para os 7 idiomas. Ainda não empacotado em um novo zip de lançamento nem refletido no changelog.
* (Implementado em 2026-08-01, JS do lado do cliente) Solicitações de recursos da comunidade levantadas no fórum -- renderização de matemática em LaTeX (estilo `$E=mc^2$`, newbbs/js/latexrender.js, carrega o KaTeX de um CDN apenas quando o leitor individual opta por isso), um controle de "recolher/excluir tópico não lido" (newbbs/js/treehide.js, restaurável a qualquer momento), e um filtro de contagem de linhas para posts longos (newbbs/js/longpostfilter.js, limite ajustável) -- todos os três implementados como configurações pessoais opcionais (por navegador, baseadas em localStorage), padrão DESLIGADO, sem envolvimento do lado do servidor. Um pedido separado para portar o detector de palavras proibidas para WebAssembly visando velocidade permanece adiado: o mantenedor fará um protótipo e benchmark primeiro, e só implementará se realmente ajudar de forma significativa (suspeita que o verdadeiro gargalo é o tamanho da lista de palavras proibidas, não o algoritmo de correspondência).
* (Revisado em 2026-07-25, decisão do mantenedor) Configuração antiga do "módulo móvel": confirmado que a chave de configuração `RESTRICT_MOBILEIP` está morta/não utilizada (nenhum código a referencia em lugar nenhum) -- um resquício de um módulo de saída separado para dispositivos móveis, descontinuado. O suporte móvel atual é apenas via CSS (meta viewport + breakpoints de media query), sem detecção de UA do lado do servidor. Decisão do mantenedor: manter em mente a abordagem de detecção de UA "não-PC" para uma necessidade futura, mas por enquanto apenas corrigir as lacunas concretas de CSS encontradas durante a revisão -- `.msgtree` (visualização de AA/tópico) estava sem `overflow-x: auto` em telas estreitas (presente apenas em larguras de desktop, então linhas longas de AA empurravam a página inteira para o lado em vez de rolar dentro do bloco) e `.postlists` (tabela de lista de posts do administrador) não tinha tratamento de rolagem horizontal alguma. Ambos corrigidos.
* (Implementado em 2026-07-25) `#hashtag` no corpo de um post agora é automaticamente convertido (governado pela configuração `AUTOLINK` existente) em um link de busca de texto completo do getlog (`m=g`), delimitado a uma janela de datas ancorada na própria data do post: o próprio mês do post quando `OLDLOGSAVESW=1` (arquivos de log mensais), ou os 7 dias até e incluindo a data do post quando `OLDLOGSAVESW=0` (arquivos de log diários).
* O formulário não aparece na tela de novo post
* Uso adequado de funções multibyte e jcode
* Ter o JavaScript de miniaturas do uploader para facilmente suportar outras instâncias de softwares tipo Uploader
* (Decidido em 2026-07-18, decisão do mantenedor) O formulário de post da página inicial intencionalmente NÃO mostra a tela de confirmação "Postagem concluída" (apenas posts de resposta/follow-up mostram). Revisado e mantido como está; não é um bug.
* (Decidido em 2026-07-18, decisão do mantenedor) `Cache-Control: no-store` NÃO será adicionado ao bbs.php. O conteúdo de formulário ocasionalmente desatualizado após postar e depois navegar para trás é aceito como uma compensação em favor da navegação "voltar" mais rápida do bfcache.
* (Observado em 2026-07-19; ainda se aplica após a mudança de 2026-07-20 abaixo) `ADMINKEY` (palavra-chave de entrada no modo de post de administrador) é armazenado em texto simples e comparado via comparação simples de strings, diferente de `ADMINPOST`, que é um hash crypt/bcrypt. Preocupação de segurança reconhecida; mantendo o recurso como está por enquanto, mas uma versão futura deveria considerar um esquema de hash/comparação semelhante ao de `ADMINPOST`.
* (Implementado em 2026-07-20) Os segredos de administrador (`ADMINPOST`/`ADMINKEY`) foram movidos completamente para fora do conf.php, para um arquivo de nome fixo (`local.php`) que não faz parte do modelo de distribuição newbbs/ e, portanto, nunca é tocado pelo processo de mesclagem de conf do install.php. Eles são definidos/alterados por meio de uma ferramenta independente (inicialmente chamada `_setup.php`, renomeada pelo operador no primeiro uso) em vez de pelo conf.php ou install.php. Veja doc/admin-secrets-concept-2026-07-19-01.txt para a discussão de design original.

* (Corrigido em 2026-08-01, RC11) os valores de expressão numérica da tela de revisão de conf do install.php (ex.: `4 * 1024 * 1024` de `MAXOLDLOGSIZE`) eram gravados de volta como strings entre aspas (`'1023998976'`) em vez de números simples, causando bugs de comparação string-vs-int em tempo de execução (verificações de tamanho de log disparando incorretamente). Corrigido adicionando uma verificação `$was_numeric_expr` (apenas dígitos/operadores/espaços) junto com a verificação existente de número simples em `ksphp_conf_apply_review()`, para que tais valores sejam gravados sem aspas. Arquivos conf.php existentes criados antes desta correção precisam de uma edição manual única em suas chaves afetadas (alterar o valor entre aspas para um inteiro ou expressão sem aspas).
* (Corrigido em 2026-08-01, RC11) `Func::html_escape()` em bbs.php removia o caractere `$` no início de qualquer linha do corpo do post após a primeira, via `str_replace("\015$", "", $value)` -- um resquício de antes da normalização de CR/LF acima dele, sem nenhuma justificativa de segurança perceptível. Isso quebrava os delimitadores de estilo LaTeX `$...$` (veja latexrender.js acima) na linha 2 em diante de um post. Linha removida.
* (Implementado em 2026-08-01, RC11) a tela de revisão do conf.php agora mostra o comentário do conf.php de cada configuração como texto de ajuda (veja a entrada `ksphp_conf_entry_comment_text()` acima para detalhes).
* (Corrigido em 2026-08-01, RC12) `BBSLINK` era renderizado como uma entrada de texto de linha única na tela de revisão do conf.php, apesar de conter um bloco de HTML/texto de múltiplas linhas como valor. Adicionada uma nova lista `ksphp_conf_longtext_keys()` e um tipo de campo `'longtext'` (lógica de exibição/salvamento idêntica ao tipo `'text'` existente; apenas o widget do formulário difere -- um `<textarea>` em vez de `<input type="text">`) e registrado `BBSLINK` sob ele.
* (Implementado em 2026-08-01, RC13) Todas as alternâncias de recursos JS por navegador (existentes -- kaomoji.js, upthumb.js, imgthumb.js, vidembed.js -- e novas -- longpostfilter.js, latexrender.js, treehide.js) integradas em um novo fieldset "JS設定" (Configurações JS) dentro da página de configurações pessoais ("個人環境設定", m=c). As configurações agora são salvas por meio de um novo cookie independente `ksphp_js` (JSON, expiração de 90 dias) em vez de localStorage, enviado pelo mesmo botão "登録" (Registrar) das outras configurações da página; uma única tabela de definição PHP (`ksphp_js_setting_defs()` em bbs.php) controla o carregamento/salvamento do cookie/renderização do formulário, de modo que adicionar um futuro recurso JS é uma adição de uma linha a essa tabela. As três alternâncias anteriormente independentes no topo da página (treehide/longpostfilter/latexrender) foram removidas; essas três ainda recorrem a qualquer valor existente no localStorage uma vez, para usuários atualizando do RC10-12, antes que o cookie se torne a fonte de verdade daqui para frente. ayashiibreaker.js (quebra de linha) não tem checkbox de ligar/desligar conforme especificado (obrigatório), mas seu parâmetro de comprimento de linha agora é configurável no painel; o máximo configurável é calculado no lado do servidor a partir do próprio `MAXMSGCOL` do conf.php, então nunca pode ser definido além do que o servidor aceitará, e linhas contendo japonês usam aproximadamente 1/3 do valor configurado (margem segura de kinsoku), já que MAXMSGCOL é um limite de bytes (baseado em `strlen()`) enquanto a quebra de linha conta caracteres. Traduções completas em 7 idiomas adicionadas para as 9 novas chaves de template.
* Não iniciado: permitir que o painel de configurações pessoais substitua a configuração de "Gikoneko-to-issho" a nível de conf.php do lado do cliente (o conf.php pode ativá-la, mas o leitor ainda poderia desativá-la para si mesmo). Isso fazia parte do pedido original do painel JS, mas ficou fora do escopo da passagem RC13, que se concentrou nos arquivos JS enumerados acima.

## Bugs conhecidos:
* (Nota de migração, RC13+) Os três recursos JS por navegador adicionados em RC10-RC12 (treehide, longpostfilter, latexrender) originalmente armazenavam seu estado ligado/desligado em localStorage. RC13 moveu essas configurações para um cookie do servidor (`ksphp_js`), e para migração, cada um desses scripts ainda prefere um valor existente no localStorage quando presente. Como resultado, em um navegador onde um desses recursos foi explicitamente desligado de volta em RC10-RC12, ligá-lo no novo painel de Configurações JS parece não ter efeito -- o `0` obsoleto do localStorage prevalece. Corrigir isso é uma ação única por navegador: alternar o recurso para desligado e ligado novamente no painel, ou excluir as chaves antigas (`ksphp_treehide_enabled`, `ksphp_longpost_enabled`, `ksphp_latex_enabled`). Instalações novas e navegadores que nunca passaram por RC10-RC12 não são afetados.
* (Encontrado e corrigido em 2026-08-01, RC13) o link "折りたたむ" (recolher) por post do longpostfilter.js aparecia em todo post, independentemente de ele realmente exceder o limite de contagem de linhas, porque o caminho de renderização não recolhido chamava expand() incondicionalmente com o link visível. Corrigido para que o link de recolher só apareça depois que um post que foi realmente dobrado (ou seja, excedeu o limite) seja manualmente re-expandido.
* (Encontrado e corrigido em 2026-08-01, RC12-02) As notas de entrega do RC11 afirmavam que uma verificação `$was_numeric_expr` havia sido adicionada a `ksphp_conf_apply_review()` para manter valores de configuração de expressão numérica (ex.: `4 * 1024 * 1024` de `MAXOLDLOGSIZE`) sem aspas ao salvar. Essa verificação nunca esteve realmente presente no código lançado (nem em RC11 nem em RC12) -- apenas a verificação preexistente de número simples permanecia. Descoberto por meio de um valor `MAXMSGSIZE` de um site real (`'250*120*128*256*128'`, salvo como uma string entre aspas por uma execução de instalação anterior) causando um aviso do PHP de valor não numérico na comparação de comprimento de conteúdo de `procForm()`, o que por sua vez corrompeu a resposta HTTP e apareceu no navegador como `ERR_CONTENT_DECODING_FAILED`. A verificação agora está de fato implementada. Limitação importante: isso apenas previne o bug em futuras instalações/migrações -- NÃO repara automaticamente um valor de conf.php que já foi salvo como uma string entre aspas (o formulário de revisão simplesmente reenviaria o valor entre aspas existente sem alterações). Qualquer chave de expressão numérica já corrompida precisa de uma edição manual do conf.php, ou o valor redigitado e reenviado pelo formulário de revisão.
* Grande número de ocorrências de \&nbsp; ao pesquisar logs
* (Encontrado em 2026-08-01 durante testes de revisão de conf do install.php; corrigido em 2026-08-01, causa raiz -- veja A fazer) o scanner de limites de entrada de `ksphp_conf_parse_entries()` atribuía incorretamente uma chave quando ela era imediatamente precedida por um exemplo comentado contendo uma string parecida com `'KEY' =>` entre aspas (ex.: HOSTNAME_POSTDENIED após TMPL_MSG/TMPL_ENVLIST comentados, CHECKCOUNT/MINPOSTSEC/MAXPOSTSEC após seus próprios exemplos comentados). Corrigido removendo as linhas de comentário iniciais antes da correspondência de nome de chave em todos os quatro pontos de chamada (`ksphp_conf_merge()`, `ksphp_conf_build_review()`, `ksphp_conf_apply_review()`, `ksphp_parse_module_array()`); o fallback defensivo "raw" do lado do install.php para tais entradas permanece em vigor como uma rede de segurança, mas não deve mais ser acionado na prática.
* (Confirmado em produção em qptns.com em 2026-08-01; corrigido em 2026-08-01 -- veja A fazer) a tela de revisão de conf do install.php marcava erroneamente ZIPDIR como campo obrigatório; um ZIPDIR vazio é na verdade uma configuração válida significando "não criar um log zip". Corrigido, junto com o mesmo problema em OLDLOGFILEDIR e CNTFILENAME (mesmo padrão de "vazio = recurso desativado", confirmado por seus próprios comentários no conf.php).
* (Implementado em 2026-08-01) o aviso de auto-criação "o arquivo não existia, então foi criado" para o arquivo de rotação de log antigo diário/mensal agora só aparece em uma configuração genuinamente pela primeira vez (nenhum outro arquivo de log antigo com a mesma extensão ainda presente em OLDLOGFILEDIR). A rotação de rotina -- um novo arquivo datado aparecendo em cada limite de dia/mês enquanto arquivos anteriores já existem -- não aciona mais o aviso. Deliberadamente com escopo leve: uma verificação de listagem de diretório (existe algum outro arquivo de log antigo) em vez de um cálculo de calendário/data de "este é o primeiro post do período".
* (Encontrado e corrigido em 2026-08-01, RC14-02, via testes em produção em qptns.com/test/) o alvo de quebra de linha do ayashiibreaker.js ainda estava apertado demais após a correção inicial do RC14 (configuredLen-2 para linhas em japonês): elevado para configuredLen-12 tanto para linhas em japonês quanto em ASCII após verificação em produção contra uma linha de teste longa específica. Separadamente, o embrulho por limite de palavra em ASCII (inglês) tinha seu próprio bug: verificava o limite de estouro apenas depois de já ter se comprometido a anexar uma palavra, e nunca dividia uma única palavra mais longa que o comprimento alvo, então tokens longos (ex.: "ayashiibreaker.js", "word-boundary") podiam deixar uma linha sem corte independentemente da margem. Reescrito como um acumulador de linhas que força a divisão de palavras longas demais e verifica o comprimento candidato antes de anexar, de modo que as linhas em ASCII agora respeitam garantidamente o limite configurado.
