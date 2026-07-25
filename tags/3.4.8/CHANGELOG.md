# Changelog

Todas as mudanças notáveis do plugin UQBITZ Hub de Integração Imobiliária serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [3.4.8] - 2026-07-25

### Corrigido
- **Galerias do ACF Pro voltavam vazias no feed e no editor**, fazendo cada imóvel ser exportado sem o bloco obrigatório `<multimidia>`. Como o OpenNavent exige de 5 a 50 imagens por imóvel, o portal rejeitava todos e, a cada sincronização, **finalizava todos os anúncios** (imóvel ausente do feed = anúncio dado baixa). No admin, o campo galeria também aparecia sem imagens
- **Causa raiz:** os filtros de fallback da galeria nativa (`acf/load_value`/`acf/format_value` para `galeria_de_imagens` e `plantas`) eram registrados no carregamento do plugin (`plugins_loaded`), **antes** do ACF Pro registrar o tipo de campo `gallery`. Naquele instante `uqbhi_has_acf_pro_gallery()` retornava `false`, os filtros eram anexados e permaneciam ativos mesmo com o ACF Pro presente — corrompendo a leitura da galeria (os IDs dos anexos viravam arrays no `load_value` e o `format_value` do próprio ACF Pro devolvia galeria vazia)
- **Correção:** o registro dos filtros de fallback foi adiado para `acf/init`, quando os tipos de campo do ACF já estão registrados e a checagem acerta. Além disso, o feed e a validação passaram a ler as galerias por `uqbhi_get_gallery_items()`, que cai para o meta bruto (`get_post_meta` + normalização) sempre que o ACF retorna vazio — blindando o feed contra qualquer estado do ACF Pro
- Nenhuma imagem foi perdida: os IDs dos anexos estavam íntegros no post meta o tempo todo

## [3.4.7] - 2026-07-22

### Alterado
- Compatibilidade declarada com o WordPress atualizada para 7.0.2 (`Tested up to`). Nenhuma mudança funcional

## [3.4.6] - 2026-07-22

### Corrigido
- **Erro "o imóvel não tem uma cidade válida / localização válida":** o estado era fixo como `São Paulo` na tag `<localidade>`, ignorando o campo ACF `estado`. Imóveis de outros estados eram enviados com a localização errada. Agora o estado real é usado, com normalização de UF (`PR`) para nome por extenso (`Paraná`)
- `<localidade>` não é mais montada com `array_filter`, que deslocava as posições `Bairro,Cidade,Estado,País` quando uma parte estava vazia; vírgulas dentro de bairro/cidade também são removidas por deslocarem os níveis. Sem cidade ou estado válidos a tag é omitida em vez de enviada incorreta
- Preço zero deixou de ser enviado no XML: a v3.4.4 passou a aceitar zero em todos os campos numéricos, mas o OpenNavent não permite `<quantidade>0</quantidade>` no Brasil. Zero continua válido em vagas, banheiros, IPTU, idade e condomínio
- Preço com máscara (`4.350.000,00`) virava `4` no `intval()` do feed — um imóvel de milhões era anunciado por alguns reais. Agora falha a validação com mensagem explicando o formato esperado, em vez de publicar o valor errado
- **`<precos>` agora respeita `uqbhi_finalidade`:** só emite `VENTA`/`ALQUILER` quando a finalidade autoriza a operação. Antes, um imóvel de venda com `rent_price` preenchido publicava um anúncio de locação não intencional, consumindo crédito da imobiliária. Imóveis sem finalidade mapeável mantêm o comportamento anterior, guiado por preço — mas agora só com preço válido
- **`uqbhi_get_tipo()` não cai mais num default silencioso:** ele usava o primeiro termo em ordem alfabética e, se aquele não resolvesse os IDs OpenNavent, caía num Apartamento (2/1) fixo, sem log — exportando o imóvel na categoria errada. Agora percorre todos os termos atribuídos e usa o primeiro que resolve
- A regra de condomínio obrigatório nunca disparava para Casa de Condomínio: a validação procurava o slug `casa-de-condominio`, o seed cria `casa-condominio`
- `<titulo>` passava por `esc_html()` dentro do CDATA, publicando entidades escapadas (`&amp;`) no portal
- `codigoReferencia` tratava a referência `0` como vazia e caía no ID do post

### Blindagem do `dataModificacao`

O portal recusa anúncios com _"o imóvel foi modificado em outro processo ou manualmente… a data do XML é anterior à mudança"_. **A causa raiz não está confirmada** — ela depende de dados que só o suporte do portal e o servidor do cliente têm. Esta versão elimina todos os caminhos pelos quais o plugin poderia contribuir para o problema:

- O feed era servido com `Cache-Control: public, max-age=3600`. Se qualquer camada de cache (CDN, LiteSpeed, Nginx, Varnish) entregasse ao portal um XML antigo, o `dataModificacao` chegaria anterior às alterações já registradas. Agora o feed envia `no-cache`/`no-store`, define `DONOTCACHEPAGE` **antes** de descartar os buffers de saída (o LiteSpeed decide o header dele no callback do buffer) e dispara `litespeed_control_set_nocache`
- `dataModificacao` era gerado com `round()`, que devolve float: com `precision` abaixo de 13 no php.ini o timestamp saía em notação científica (`1.7847E+12`). Agora é formatado com `sprintf( '%.0f', … )`, imune tanto ao `precision` quanto ao overflow de inteiro em builds 32-bit
- O valor emitido é monotônico: nunca retrocede em relação ao último enviado, mesmo que a margem de segurança seja desligada
- Canário no gerador: se o `dataModificacao` sair com qualquer caractere não numérico, o feed grava no `error_log`

### Adicionado
- Configuração **Forçar atualização**: faz o feed prevalecer sobre edições feitas direto no painel do portal, adicionando margem de segurança ao `dataModificacao`. Ajustável pelo filtro `uqbhi_data_modificacao_margem`
- Validação de estado inválido e de título com menos de 10 caracteres (mínimo exigido pelo OpenNavent; antes o plugin aceitava 5)
- A URL alternativa do feed (`/feed-imovelweb/`, que sempre existiu) agora aparece na tela do plugin, com aviso para cadastrar apenas uma das duas no portal
- Rota REST do feed responde a `HEAD` além de `GET` — o portal valida o Content-Type antes de baixar
- Validações novas: "Preço preenchido não corresponde à finalidade selecionada", "Preço em formato inválido" e "Tipo sem mapeamento OpenNavent" (imóvel fica fora do feed em vez de sair classificado errado)
- Painel administrativo lista os termos de tipo sem mapeamento OpenNavent, com contagem e instrução de como resolver
- Funções novas: `uqbhi_map_finalidade_term()`, `uqbhi_get_operacoes()`, `uqbhi_get_operacoes_publicaveis()`, `uqbhi_resolve_tipo_ids()`, `uqbhi_get_tipo_term()`, `uqbhi_build_localidade()`, `uqbhi_normalize_estado()`, `uqbhi_has_price()`, `uqbhi_data_modificacao()`

## [3.4.5] - 2026-04-30

### Adicionado
- Integração com Elementor Dynamic Tags: galeria de imagens e plantas do imóvel ficam disponíveis no seletor de variáveis do Elementor sob o grupo "Hub Imóveis", permitindo bind direto em widgets sem depender do ACF Pro
- Novo módulo `includes/elementor-dynamic-tags.php` + classe `UQBHI_Elementor_Dynamic_Tag_Galeria_Imovel` em `includes/elementor-dynamic-tags/gallery-imovel-tag.php`
- Filtros `acf/format_value/name=galeria_de_imagens` e `acf/format_value/name=plantas` no fallback nativo: integrações de terceiros que leem os campos via ACF passam a receber o mesmo formato (array de objetos de anexo) que o ACF Pro entrega

## [3.4.4] - 2026-04-29

### Corrigido
- Valores zero (ex.: 0 vagas, 0 anos de idade, 0 banheiros) eram tratados como vazios no feed XML e na validação devido ao uso de `empty()`. Novo helper `uqbhi_has_value()` distingue zero significativo de `null`/string em branco/array vazio
- `uqbhi_validate_imovel`: sell_price/rent_price, metreage, iptu, idade e condominium aceitam zero como valor preenchido
- `uqbhi_render_imovel`: características numéricas (rooms, suits, bathroom, metreage, parking, iptu, condominium, idade) são emitidas no XML mesmo quando o valor é zero

## [3.4.3] - 2026-04-18

### Corrigido
- Galeria de imagens e galeria de plantas não apareciam no editor quando apenas ACF free estava instalado (o field type `gallery` é exclusivo do Pro). Agora o plugin detecta automaticamente a ausência do Pro e registra metaboxes nativos equivalentes

### Alterado
- Seed de `uqbhi_finalidade` alinhado ao enum oficial OpenNavent: apenas `Venda` (VENTA) e `Aluguel` (ALQUILER); removidos `Temporada` e `Repasse` (não existem no padrão da API)
- `uqbhi_get_tipo` refatorado: lê `idTipo`/`idSubTipo` direto do term meta com herança automática do ancestral mais próximo quando o termo selecionado não tem meta (termos customizados criados pelo usuário)
- `uqbhi_get_operacao` refatorado: lê `uqbhi_opennavent` do term meta, com fallback por nome (pt/es/en) para termos customizados
- Removido mapeamento hardcoded de slugs → IDs em `helpers.php` (≈200 linhas); fonte única da verdade agora é o term meta

### Adicionado
- Term meta nos termos semeados de `uqbhi_tipo`: `uqbhi_id_tipo` e `uqbhi_id_subtipo` gravam o ID OpenNavent direto no termo
- Term meta nos termos semeados de `uqbhi_finalidade`: `uqbhi_opennavent` guarda o código da operação (`VENTA` / `ALQUILER`)
- Traduções em espanhol (`uqbhi_name_es`) e inglês (`uqbhi_name_en`) como term meta em todos os termos semeados
- Função `uqbhi_seed_term_tree` agora persiste meta em criação e atualização (idempotente)
- Migração única `uqbhi_migrate_legacy_tipo_meta` backfilla meta em termos customizados pré-3.4.3 via match parcial por slug (preserva o comportamento anterior em instalações existentes)
- Novo módulo `includes/gallery-fallback.php`: quando ACF Pro não está disponível, registra metaboxes nativos com `wp.media` e jQuery UI Sortable para `galeria_de_imagens` e `plantas`. Armazena IDs de anexos em post meta com as mesmas chaves que o ACF Pro usa — feed e validação funcionam sem alteração. Inclui remoção individual, limpeza em lote e reordenação por arrastar

## [3.4.2] - 2026-04-18

### Adicionado
- Seed automático dos termos oficiais de `uqbhi_tipo` e `uqbhi_finalidade` na ativação
- Reexecução idempotente do seed no `admin_init` quando a versão muda

## [3.4.1] - 2026-04-08

### Alterado
- Formatação de todo o código conforme WordPress Coding Standards (PHPCS)
- Indentação com tabs, docblocks PHPDoc em todas as funções, estilo de chaves padronizado
- Nenhuma alteração funcional

## [3.4.0] - 2026-04-07

### Alterado
- Refatoração estrutural: arquivo único (1881 linhas) separado em 6 arquivos com responsabilidades únicas (SOLID/KISS)
- Novo diretório `includes/` com: `cpt.php`, `helpers.php`, `feed.php`, `acf-fields.php`, `admin.php`
- Arquivo principal reduzido a bootstrap (~50 linhas): constantes, includes e activation hooks
- Admin (páginas, CEP auto-fill, settings) carregado apenas no painel (`is_admin()`)

### Adicionado
- Campo ACF `complemento` registrado via código (antes existia apenas como meta field sem registro)

### Corrigido
- Campo `Infraestrutura` no feed XML usava letra maiúscula (`get_field('Infraestrutura')`) — corrigido para minúscula (`infraestrutura`), compatível com o registro ACF

## [3.3.0] - 2026-04-07

### Alterado
- Prefixo uniforme `uqbhi_` para todas as funções, constantes, options, CPT e taxonomias
- CPT: `imovel` → `uqbhi_imovel`
- Taxonomias: `tipo` → `uqbhi_tipo`, `finalidade` → `uqbhi_finalidade`, `cidade-e-bairro` → `uqbhi_cidadebairro`
- REST namespace: `portalimoveis/v1` → `uqbhi/v1`
- Feed URL usa `rest_url()` ao invés de `home_url('/wp-json/...')`
- Admin page slugs prefixados: `uqbhi-portal`, `uqbhi-settings`, `uqbhi-mapping`
- ACF field/group keys prefixados com `uqbhi`

## [3.2.0] - 2026-03-26

### Corrigido
- Escaping de output HTML em todas as páginas admin (esc_html, esc_attr, esc_url)
- Sanitização de inputs via callback em register_setting() (sanitize_text_field, sanitize_email)
- htmlspecialchars() substituído por esc_html() (padrão WordPress)
- readme.txt incluído na pasta do plugin (resolves "readme.txt does not exist")

### Alterado
- Plugin compatível com WordPress Plugin Check (PHPCS WordPress Coding Standards)

## [3.1.0] - 2026-03-26

### Adicionado
- Validação de IPTU como campo obrigatório
- Validação de Idade do imóvel como campo obrigatório
- Validação de Condomínio (obrigatório para apartamentos e casas de condomínio)
- Validação de endereço completo: CEP, Rua, Bairro, Cidade e Estado

### Alterado
- Layout da Infraestrutura para horizontal (items lado a lado, igual Amenidades)
- Galeria, Plantas e Vídeo YouTube agora em largura total
- Menu "Hub Imóveis" reposicionado logo abaixo do CPT Imóveis
- Autor atualizado para "Fernando Perrella (UQBITZ)"

## [3.0.0] - 2026-03-25

### Adicionado
- Painel administrativo com 3 páginas: Visão Geral, Configurações, Mapeamento
- Validação de campos obrigatórios (imóveis incompletos excluídos do feed)
- Instruções de integração com portais (ImovelWeb, Wimoveis, Casa Mineira)
- readme.txt no formato WordPress.org

### Alterado
- Plugin renomeado de "Imóveis Amaro" para "UQBITZ Hub de Integração Imobiliária"
- Namespace REST API: `imoveisamaro/v1` → `portalimoveis/v1`
- Prefixo de funções: `iamaro_` → `ptim_`
- Feed URL: `/wp-json/portalimoveis/v1/feed`

### Removido
- Todas as referências específicas ao cliente original

## [2.8.0] - 2026-03-25

### Adicionado
- Campo Vídeo YouTube com extração automática de código (suporta watch, youtu.be, embed, shorts)
- Campo Plantas (galeria de plantas baixas) com título personalizado
- Instruções detalhadas nos campos ACF para orientar preenchimento
- Recomendação de 22+ fotos na galeria

### Alterado
- Campo de vídeo migrado de upload (file) para URL (text)

## [2.7.0] - 2026-03-24

### Adicionado
- IPTU (CFT400) no bloco `<caracteristicas>` do XML
- Condomínio (CFT6) no bloco `<caracteristicas>` do XML
- Idade do imóvel (CFT5) no bloco `<caracteristicas>` do XML
- Mapeamento de amenidades ACF → IDs Navent AREA_PRIVATIVA (20xxx)
- Mapeamento de infraestrutura ACF → IDs Navent AREAS_COMUNS (10xxx)
- Campo Complemento (bloco, unidade, andar) no endereço

## [2.5.0] - 2026-03-23

### Adicionado
- 82 mapeamentos de características Navent (IDs numéricos → labels PT-BR)
- Script de migração para converter dados existentes

### Alterado
- Choices ACF atualizadas: amenidades (30 opções), infraestrutura (55 opções)

## [2.4.0] - 2026-03-23

### Adicionado
- CPT `imovel` registrado via código do plugin
- 3 taxonomias registradas via código: `tipo`, `finalidade`, `cidade-e-bairro`
- Hierarquia completa de tipos: 5 tipos pai, 40 subtipos

### Alterado
- Registros ACF de CPT/taxonomias desativados (migrados para código)

## [2.1.0] - 2026-03-23

### Adicionado
- Mapeamento completo tipo/subtipo → API Navent (40 slugs)
- Função `ptim_get_tipo()` com match exato + fallback parcial

## [2.0.0] - 2026-03-19

### Adicionado
- Reescrita completa como plugin single-file
- Feed XML via WordPress REST API (`/wp-json/portalimoveis/v1/feed`)
- Suporte a operações de Venda e Locação
- Formato OpenNavent com CDATA, timestamps, localidade
- Extração de CEP do campo de localização
