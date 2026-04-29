# Changelog

Todas as mudanças notáveis do plugin UQBITZ Hub de Integração Imobiliária serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto segue [Semantic Versioning](https://semver.org/lang/pt-BR/).

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
