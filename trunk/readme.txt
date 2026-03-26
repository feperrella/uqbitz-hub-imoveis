=== Portal Imóveis – Feed XML (OpenNavent) ===
Contributors: feperrella
Tags: imoveis, xml, feed, real-estate, opennavent
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 3.2.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gera feed XML (OpenNavent) para integração com portais imobiliários: ImovelWeb, Wimoveis e Casa Mineira.

== Description ==

Plugin WordPress que gera automaticamente um feed XML no formato OpenNavent para sincronizar imóveis cadastrados no WordPress com os principais portais imobiliários do Brasil.

**Portais suportados:**

* ImovelWeb
* Wimoveis
* Casa Mineira

**Funcionalidades principais:**

* Feed XML automático em `/wp-json/portalimoveis/v1/feed`
* Custom Post Type "Imóvel" com campos ACF completos
* Taxonomias: Tipo do Imóvel, Finalidade, Cidade e Bairro
* Validação de campos obrigatórios — imóveis incompletos são excluídos do feed
* Painel administrativo com status do feed, instruções e mapeamento técnico
* Mapeamento automático de amenidades e infraestrutura para IDs Navent
* Suporte a vídeos YouTube (extração automática do código)
* Suporte a plantas baixas com título personalizado
* Preenchimento automático de endereço via CEP (ViaCEP)
* Tipo e subtipo de propriedade com mapeamento completo para a API Navent

**Campos obrigatórios validados:**

* Título (mín. 5 caracteres)
* Descrição (mín. 50 caracteres)
* Preço (venda ou locação)
* Galeria de fotos (mín. 5 imagens)
* Tipo e Finalidade
* Endereço completo (CEP, Rua, Bairro, Cidade, Estado)
* Área privativa (m²)
* IPTU
* Idade do imóvel
* Condomínio (obrigatório para apartamentos e casas de condomínio)

== Installation ==

1. Faça upload da pasta `portal-imoveis` para `/wp-content/plugins/`
2. Ative o plugin no painel do WordPress
3. Acesse **Portal Imóveis → Configurações** e preencha:
   * Código da Imobiliária (fornecido pelo portal)
   * E-mail, nome e telefone de contato
4. Cadastre imóveis preenchendo todos os campos obrigatórios
5. Acesse **Portal Imóveis → Visão Geral** para verificar o status do feed
6. Copie a URL do feed e cadastre no portal desejado

**Configuração no portal (ImovelWeb):**

1. Acesse o painel do ImovelWeb
2. Vá em **Integração de Anúncios → XML**
3. Cole a URL do feed
4. Em "Nome do Integrador", coloque **UQBITZ**
5. Salve

== Frequently Asked Questions ==

= Quais são os requisitos? =

* WordPress 6.0 ou superior
* PHP 8.0 ou superior
* Plugin Advanced Custom Fields PRO instalado e ativo

= Onde encontro a URL do feed? =

Acesse **Portal Imóveis → Visão Geral** no painel do WordPress. A URL é exibida no topo da página, no formato: `https://seusite.com.br/wp-json/portalimoveis/v1/feed`

= Por que um imóvel não aparece no feed? =

Imóveis com campos obrigatórios incompletos são automaticamente excluídos. Acesse **Portal Imóveis → Visão Geral** para ver a lista de pendências de cada imóvel.

= Posso usar em mais de um site? =

Sim. Cada instalação do plugin gera seu próprio feed independente. Basta instalar, configurar o código da imobiliária e cadastrar os imóveis.

= Como funciona o preenchimento automático pelo CEP? =

Ao digitar o CEP na edição de um imóvel, o plugin consulta a API ViaCEP e preenche automaticamente Rua, Bairro, Cidade e Estado.

= Preciso de chave de API do portal? =

Não para o feed XML. O feed é uma URL pública que o portal consome. Você só precisa do código da imobiliária fornecido pelo portal.

== Screenshots ==

1. Painel Visão Geral — status do feed com lista de imóveis e pendências
2. Configurações — código da imobiliária e dados de contato
3. Mapeamento — tabela técnica de campos ACF para XML
4. Edição de imóvel — campos ACF organizados

== Changelog ==

= 3.1.0 =
* Validação expandida: IPTU, Idade, Condomínio (condicional), endereço completo obrigatórios
* Layout otimizado: infraestrutura, galeria, plantas e vídeo em largura total
* Infraestrutura com layout horizontal (items lado a lado)
* Menu "Portal Imóveis" reposicionado abaixo do CPT Imóveis
* Autor atualizado para distribuição

= 3.0.0 =
* Rebrand: plugin renomeado para "Portal Imóveis"
* Namespace REST API alterado para `portalimoveis/v1`
* Prefixo genérico `ptim_` para distribuição
* Painel administrativo com 3 páginas: Visão Geral, Configurações, Mapeamento
* Validação de campos obrigatórios no feed XML

= 2.8.0 =
* Campo Vídeo YouTube com extração automática de código
* Campo Plantas (galeria de plantas baixas)
* Instruções detalhadas nos campos ACF

= 2.7.0 =
* IPTU, Condomínio e Idade incluídos no XML
* Mapeamento de amenidades para IDs Navent (AREA_PRIVATIVA)
* Mapeamento de infraestrutura para IDs Navent (AREAS_COMUNS)
* Campo Complemento no endereço

= 2.5.0 =
* Migração de características: IDs numéricos convertidos para labels em português
* 82 mapeamentos de características Navent
* Atualização das choices ACF para amenidades (30) e infraestrutura (55)

= 2.4.0 =
* CPT e taxonomias registrados via código do plugin (não mais via ACF)
* Taxonomia Tipo com hierarquia completa (5 tipos, 40 subtipos)

= 2.1.0 =
* Mapeamento completo tipo/subtipo para API Navent (40 slugs)

= 2.0.0 =
* Reescrita completa como plugin single-file
* Feed XML via REST API
* Suporte a venda e locação

== Upgrade Notice ==

= 3.1.0 =
Validação expandida e layout otimizado. Verifique se todos os imóveis têm IPTU, Idade e endereço completo preenchidos.

= 3.0.0 =
URL do feed alterada para `/wp-json/portalimoveis/v1/feed`. Atualize a URL no portal.
