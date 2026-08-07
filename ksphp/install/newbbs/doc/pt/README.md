# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
Uma versão melhorada do port PHP do KuzuhaScript (くずはすくりぷと).
A partir de 2024/10/16, funciona apenas com PHP8+.
Última versão com suporte ao PHP legado (4.1.0–7.4): [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)

Este programa é baseado na versão modificada de 2005/04/01 do KuzuhaScriptPHP (くずはすくりぷとPHP).

Este programa foi originalmente traduzido para o inglês por [Anonymous-san no Strange World@Heyuri.net](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) e vários desenvolvedores anônimos do Heyuri contribuíram desde então.

* [KuzuhaScriptPHP (espelho)](http://qptn.x.fc2.com/up/dauso0059.zip)
* [Versão modificada de 2005/04/01](http://qptn.x.fc2.com/up/dauso0073.zip)

## Informações dos mantenedores
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Instalação
1. Descompacte o arquivo ZIP baixado
2. Abra e configure o conf.php
3. Faça upload dos arquivos para o servidor usando um cliente FTP (recomenda-se criar um diretório dedicado)
4. Configure as permissões conforme descrito abaixo
5. Acesse `_setup.php` no navegador (uma ferramenta independente incluída no pacote; não faz parte do conf.php ou install.php) e defina a senha do administrador. Ao concluir, a ferramenta se renomeia automaticamente — anote o novo nome/URL.
6. *(Não é mais necessário — a senha do administrador é gravada pelo `_setup.php` em seu próprio arquivo, não no conf.php.)*
7. Acesse bbs.php no navegador e verifique se é possível postar
8. Acesse as URLs dos arquivos de log (bbs.log, log/, etc.) no navegador e verifique se não estão acessíveis publicamente (se estiverem, restrinja com .htaccess etc.)

## Solução de problemas
### Atualizando um site existente (migração de senha do administrador)
A partir do RC8, a senha do administrador (ADMINPOST/ADMINKEY) fica fora do conf.php, em um arquivo de nome fixo `local.php` que nunca é sobrescrito pelo install.php. Quando o install.php detecta um ADMINPOST não vazio no conf.php existente, exibe um formulário de migração solicitando sua **senha antiga** (verificação) e uma **nova senha**. O ADMINKEY é herdado automaticamente.

- Se a senha antiga for verificada com sucesso, `local.php` é gravado e a instalação prossegue.
- Se a verificação falhar, **toda a instalação é cancelada** (nenhum arquivo é instalado).
- Se você realmente esqueceu a senha antiga, edite manualmente o `ADMINPOST` no conf.php do servidor para uma string vazia. O install.php tratará como nova instalação e você poderá definir uma nova senha via `_setup.php`.

## Permissões recomendadas
Permissões incorretas podem causar problemas e vazamentos de dados (endereços IP, hosts remotos, etc.).

```
[Estrutura de arquivos]
|-- bbs.cnt   600 (gravável)    Arquivo de registro de participantes (arquivo de texto vazio)
|-- bbs.log   600 (gravável)    Arquivo de log (arquivo de texto vazio)
|-- conf.php  644 (somente leitura)  Configuração
|-- bbs.php   644 (somente leitura)  Script principal do quadro
|-- readme.md                   Este arquivo
|-- vanish.js                   Script de filtragem de palavras
|
+-- archive/  700 (gravável)    Armazenamento de arquivos ZIP
+-- count/    700 (gravável)    Saída do contador
+-- log/      700 (gravável)    Armazenamento de logs brutos
+-- sub/      755 (somente leitura)  Submódulos
    |-- bbsadmin.php    644     Módulo de administração
    |-- bbslog.php      644     Módulo visualizador de log
    |-- bbstree.php     644     Módulo de visualização em árvore
    |-- phpzip.inc.php  644     Biblioteca de criação de ZIP
```

Se o PHP rodar como módulo Apache, bbs.php pode ser somente leitura (644). Se rodar como CGI, defina bbs.php como 755 (executável).

## Referência
### Significados dos parâmetros bbs.php?m=*

| Parâmetro | Significado |
| --- | --- |
| m=g | Pesquisa no log de mensagens |
| m=ad | Modo administrador |
| m=tree | Visualização em árvore |
| m=p | Postar / recarregar |
| m=c | Configurações pessoais |
| m=f | Tela de resposta |
| m=t | Exibição de thread |
| m=s | Pesquisar por usuário |
| m=u | Executar UNDO |

## Histórico
(Para versões anteriores ao RC8, consulte o README em inglês)

### RC8 (2026/07/20)
* Senhas de administrador (ADMINPOST/ADMINKEY) movidas para `local.php` fora do conf.php; gerenciadas pela ferramenta independente `_setup.php`
* install.php: formulário de migração de senha quando há ADMINPOST existente na atualização

### RC9 (2026/07/25)
* Correções de exibição móvel; correção de variável indefinida na criação de ZIP
* `#hashtag` no corpo do post agora é convertido automaticamente em link de busca getlog com intervalo de datas

### RC10 (2026/08/01)
* install.php: tela de ajuste/revisão do conf.php (mesclagem automática, 7 idiomas, rollback por arquivo)
* 3 recursos JS opcionais: renderização LaTeX (latexrender.js), colapso de threads não lidos (treehide.js), filtro de linhas longas (longpostfilter.js)

### RC11 (2026/08/01)
* install.php: correção raiz do parser de entradas do conf.php
* bbs.php: corrigido bug que quebrava delimitadores LaTeX `$...$` a partir da segunda linha

### RC12 (2026/08/01)
* BBSLINK agora renderiza como textarea na tela de revisão
* Valores de configuração em formato de expressão numérica não são mais salvos como strings

### RC13 (2026/08/01)
* Todos os toggles JS por navegador integrados ao fieldset "JS設定" na página de configurações pessoais (m=c); localStorage migrado para cookie

### RC14 (2026/08/01)
* 3 correções de bugs do RC13: margem do fieldset de kaomoji, link de colapso do longpostfilter, largura alvo do ayashiibreaker
* ayashiibreaker: quebra de linha por palavra ASCII reescrita; palavras muito longas agora são sempre divididas

### RC15 (2026/08/02)
* Revisão completa com PHPStan Level 5; 3 bugs corrigidos
* Tela de revisão do conf.php: texto de ajuda de cada configuração exibido; 98 chaves CONF_HELP_* traduzidas para 7 idiomas
* doc/ reorganizado em subdiretórios por idioma

### RC16 (2026/08/03)
* Seletor de idioma adicionado ao main_upper
* Painel de configurações pessoais: configuração `GIKONEKO_TOISSHO` do conf.php pode ser sobrescrita no lado do cliente
* gikoneko.php / gikonekoadd.php localizados

### RC17 (2026/08/03)
* Visualização em árvore: linhas de citação reescritas destacadas em dourado; toggle de ordem de classificação (mais novo/mais antigo, salvo no navegador)
* LaTeX: corrigido bug onde tokens no formato `$variavel` eram reconhecidos como delimitadores de fórmula
* install.php: suporte a 7 idiomas expandido para UI do instalador e entradas CONF_HELP_*

### RC18 (2026/08/07)
* bbs.php: correção de consistência de idioma no log — linha de referência, tag de auto-resposta e nomes de dia da semana agora são sempre gravados no log no idioma padrão configurado (LANGUAGE_FILE), independentemente do idioma selecionado pelo visitante (TDefault() / getdatestr_default() adicionados)
* bbs.php: dependência de mbstring removida em tripuse(); apenas iconv() (sem mudança funcional no qptns.com)
* install.php: opção manter/alterar para senha do administrador no upgrade; todos os 7 idiomas
* install.php: ignora arquivos inalterados durante upgrade (comparação antes do backup)

Para detalhes completos de qualquer versão, consulte `doc/changelog-2026-07-16-01.txt`.

## A fazer
* **JS de miniatura do uploader** — tornar upthumb.js facilmente configurável para instâncias de software Uploader além do Uploader@Heyuri
* **Formulário não aparece na tela de novo post** — intermitente; condições de reprodução desconhecidas

## Bugs conhecidos
* Grande número de entidades `&nbsp;` aparecem ao pesquisar logs
