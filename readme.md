# Pacote Core - TourFácil

Pacote contendo Models e Services para integrar com os canais de venda

## Configuração do canal

Adicione no `.env` o trecho abaixo e altere com os dados de acesso do canal

```text
# Identificação do canal
CANAL_VENDA_ID=
```
## Changelog

Lista de mudanças, melhorias e correções de bugs.

### *v1.3.0 (31 de Maio de 2022)*
- Adicionado integração com o Olivas.

### *v1.2.8 (26 de Maio de 2022)*
- Adicionado sistema de logs e de envio de e-mails de alerta caso a integração da venda interna falhe.

### *v1.2.7 (09 de Maio de 2022)*
- Guzzle agora ignora certificado SSL.

### *v1.2.6 (09 de Maio de 2022)*
- Adicionado API, para que o Admin mande e-mails pelo Ecommerce.

### *v1.2.5 (27 de Abril de 2022)*
- Adicionado FLAG na reserva para ver se esta ou não finalizada.

### *v1.2.4 (23 de Abril de 2022)*
- Configurações de PIX movidas para .ENV (Ou seja, removida deste projeto)
- PixService recebe função para retornar se PIX este ou não ativo

### *v1.2.3 (16 de Abril de 2022)*
- APIPix verificação para evitar erros
- Configurações da API de PIX movido para o arquivo de config site.php

### *v1.2.2 (16 de Abril de 2022)*
- Adicionado PixService para fazer todas as validações e operacionais do novo sistema de PIX

### *v1.2.1 (12 de Abril de 2022)*
- Adicionado pequeno sistema de conferencia de reservas

### *v1.2.0 (06 de Abril de 2022)*
- Adicionado sistema de cupons de desconto.

### *v1.0.1 (24 de Março de 2022)*
- Adicionado FinalizacaoService, que serve para garantir que os fornecedores só recebam o e-mail depois que a reserva esteja finalizada.

### *v1.0.0 (21 de Março de 2022)*
- Adicionado sistema de geração de links para as vendedoras montarem o carrinho dos clientes
- Adicionado sistema de vendas internos, para que o operacional possa adicionar reservas
