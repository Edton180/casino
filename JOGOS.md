# 🎮 Jogos

A plataforma tem três tipos de jogos:

| Tipo | Onde roda | Precisa de contrato/chave? |
|---|---|---|
| **Originais** (Mines, Crash, Dice, Plinko, Double) | No seu servidor | Não |
| **Jogos locais** (caça-níqueis e jogos de habilidade em `public/originals`) | No seu servidor | Não |
| **Provedores** (PlayFiver, Fivers, WorldSlot, Slotegrator...) | No servidor do provedor | Sim |

Todos usam a mesma carteira do jogador: saldo, bônus, rollover, VIP, missões e **comissão de afiliados (revshare/CPA)**.

---

## 1. Jogos originais

Desenvolvidos para esta plataforma (código próprio, sem licença de terceiros).

| Jogo | Como funciona | RTP padrão |
|---|---|---|
| **Mines** | Grade 5×5 com 1–24 minas. Cada casa segura aumenta o multiplicador; retire quando quiser. | 97% |
| **Crash** | O multiplicador sobe até "quebrar". Retire antes ou use auto-retirada. | 97% |
| **Dice** | Escolha o alvo (2–98) e role acima ou abaixo. Multiplicador = RTP ÷ chance. | 97% |
| **Plinko** | 12 linhas, risco baixo/médio/alto. Tabela normalizada para o RTP. | 97% |
| **Double** | Roleta de 15 casas: vermelho/preto 2×, branco 14×. | 93,3% (fixo) |

**Segurança e justiça**
- O resultado é calculado **somente no servidor**; o navegador envia só a aposta e as escolhas.
- **Provably fair:** cada rodada mostra o *hash* da semente antes de jogar e revela a semente no fim (HMAC-SHA256), permitindo ao jogador conferir que o resultado não foi alterado.
- Rodadas de Mines/Crash são retomadas se o jogador fechar a página; uma rodada só pode ser paga uma vez.

**Configuração** (opcional, no `.env`):

```env
ORIGINALS_HOUSE_EDGE=0.03   # vantagem da casa (0.03 = RTP 97%)
ORIGINALS_MIN_BET=0.10
ORIGINALS_MAX_BET=1000
ORIGINALS_MAX_WIN=50000     # prêmio máximo por rodada
```

Rodadas ficam na tabela `original_rounds` e no **Histórico de Partidas** do painel (provedor `originals`).

---

## 2. Jogos locais (`public/originals`)

### Caça-níqueis
| Jogo | Situação na instalação nova |
|---|---|
| Jack Frost, Treasures of Aztec | ✅ ativos |
| Fortune Tiger, Fortune OX, Fortune Mouse, Fortune Rabbit, Fortune Panda, Phoenix Rises, Queen of Bounty, Bikini Paradise, Hood vs Wolf, Songkran Party | ⚠️ desativados |

Os desativados têm **imagens corrompidas no próprio repositório** (já foram enviadas danificadas no primeiro commit, abril/2025): em vários deles a folha de símbolos é ilegível e o jogo abre com tela preta ou símbolos faltando. A lógica no servidor funciona — basta substituir as pastas `public/originals/<jogo>/images` por arquivos íntegros e ativar o jogo no painel.

> ⚠️ **Atenção:** esses caça-níqueis reproduzem jogos da **PG Soft** (marcas, arte e nomes). Operá-los sem licença da PG Soft é violação de direitos autorais. Para oferecer esses títulos legalmente, use um provedor/agregador licenciado.

O campo **RTP** desses jogos (painel → Todos os Jogos) é, na prática, a **chance de vitória (%)** por giro. O prêmio médio de uma vitória é de ~13–18× a aposta, então valores altos dão prejuízo à casa. Para um retorno real próximo de 95%, use algo entre **5 e 7**.

### Jogos de habilidade
| Jogo | Meta | Prêmio máximo |
|---|---|---|
| Dino Cash | 5× a aposta | 5× |
| Subway | 5× a aposta | 5× |
| Fruit Ninja | 2× a aposta | 2× |
| Candy (desativado — imagens corrompidas) | 3× a aposta | 3× |

A aposta é debitada no servidor (`/api/skillgames/start`) e o prêmio é pago uma única vez (`/api/skillgames/finish`), limitado à meta do jogo. Aposta de R$ 1 a R$ 25.

---

## 3. Provedores de jogos (APIs de terceiros)

Configure em **Painel → Configurações → Chaves dos Jogos** e informe ao provedor a URL de webhook (callback) correspondente:

| Provedor (distribuição) | Webhook / callback a informar ao provedor |
|---|---|
| PlayFiver (`play_fiver`) | `https://seu-dominio/playfiver/webhook` |
| Fivers (`fivers`) | `https://seu-dominio/gold_api` |
| WorldSlot (`worldslot`), Games2Api (`games2_api`) | `https://seu-dominio/gold_api` |
| Center Gaming PG (`apipg12`) | `https://seu-dominio/gold_api/user_balance` e `/gold_api/game_callback` |
| Center Gaming Pragmatic (`apipragmatic40`) | `https://seu-dominio/callback` |
| System Server (`systemserver`) | `https://seu-dominio/systemserver/webhook` |
| PlayConnect (`playconnect`) | `https://seu-dominio/cron/playconnect` |
| Play Gaming (`playgaming`) | `https://seu-dominio/playgaming` |
| Play IGaming (`playigaming`) | `https://seu-dominio/playigaming_api` |
| Salsa (`salsa`) | `https://seu-dominio/cron/salsa/webhook` |
| Shark Connect / VeniX (`venix`) | `https://seu-dominio/venix_api` |
| KaGaming (`kagaming`) | `https://seu-dominio/kagaming/webhooks` |
| Vibra Gaming (`vibra_gaming`) | `https://seu-dominio/vibra/webhooks` |
| EverGame (`evergame`), Slotegrator | conforme o painel do provedor |

Depois:
1. **Meus Jogos → Todos os Provedores**: confira se o provedor está ativo.
2. **Meus Jogos → Todos os Jogos**: filtre pelo provedor, selecione os jogos e use **Ativar Jogos**.
3. Abra um jogo logado como jogador para testar.

Se as chaves estiverem ausentes ou o provedor estiver fora do ar, o jogador vê "Este jogo está indisponível no momento" (não fica mais preso numa tela de carregamento).

### Sobre escolher provedores
Antes de contratar, confirme que o provedor/agregador **tem licença dos estúdios** cujos jogos distribui (PG Soft, Pragmatic Play etc.). Muitas "APIs de jogos" baratas no mercado brasileiro distribuem cópias não licenciadas — além do risco jurídico, você não tem garantia sobre o RTP real nem sobre a continuidade do serviço. Agregadores licenciados (ex.: Slotegrator, SoftSwiss, EveryMatrix, BetConstruct, Hub88) exigem contrato B2B e entregam documentação própria; a integração Slotegrator já existe no código.

---

## 4. Adicionar um jogo original novo

1. Crie a pasta `public/originals/<codigo>/` com `index.html` e `cover.svg` (use `public/originals/_shared/originals.js` e `originals.css`).
2. Adicione a lógica em `app/Services/Games/OriginalGames.php` e as rotas em `routes/groups/api/games/index.php` (veja `OriginalsController`).
3. Cadastre o jogo no painel (**Todos os Jogos → Criar**) com distribuição **`source`** e código igual ao nome da pasta.
