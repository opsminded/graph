# Planejamento das iterações do usuário

Eu quero planejar as iterações que o usuário terá no meu sistema Web.
Eu fiz o backend, porém, tenho dúvidas em como organizar a interface do usuário.
Quais features disponibilizar, quais botões apresentar. Não estou preocupado exatamente com o estilo visual. Mas sim a questão de funcionalidades. Preciso priorizar aquilo que é mais essencial e disto expandir as possibilidades.

O sistema é uma ferramenta de observabilidade de TI.
Em essência apresenta utilizando grafos, as dependências entre o Negócio, suas Jornadas e os ativos de TI.
O grafo apresentado nunca focará em fluxo, ou o caminho percorrido por mensagens.
A ideia é saber o que impacta o que ou qual a extensão que uma mudança em um dos nós pode provocar até o negócio.

## Objetivos imediatos
Reduzir tempo para identificar o impacto de um nó no negócio por meio de agregação de informações úteis sobre os nós e facilitar o discovery, entender mais facilmente o impacto de mudanças e saber onde olhar de ponto a ponto a jornada do cliente.

## Usuários:
A ideia deste sistema é ser altamente colaborativo.

- Se o usuário não estiver logado, ele poderá navegar à vontade como anônimo.
- O usuário logado poderá contribuir.
- Algumas ações são dedicadas aos administradores, como por exemplo, promover um usuário consumidor a contribuidor.
- A princípios os nós serão introduzidos automaticamente com dados vindas de um CMDB.
- A principal forma de contribuição dos usuários é mapear os negócios, jornadas e as dependências de TI.

### Status dos itens:
O sistema possui um mecanismo para armazenar o último estado conhecido do nó para apresentação dinâmica na tela.
Itens saldáveis ficam verdes e os com problema vermelhos.

### Backend
Segue abaixo os detalhes sobre o backend para que fique mais claro como este projeto está sendo construido:

Os dados são armazenados em um banco de dados relacional.
Por restrições do ambiente um banco de dados orientado a grafo não é possível no momento.

O sistema utiliza uma camada de abstração de software para oferecer métodos simples para iteragir com o grafo.

Existe uma classe Database que cuida da conexão e consultas ao banco de dados.
Uma classe Service que adiciona lógicas de negócio como verificar a permissão do usuário para realizar determinada tarefa.
Uma classe HTTPController para interface com o protocolo HTTP e o Serviço.

***Observe abaixo as respectivas interfaces***:

```
interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): bool;
    public function updateUser(string $id, string $group): bool;

    public function getCategories(): array;
    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool;
    
    public function getTypes(): array;
    public function insertType(string $id, string $name): bool;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?array;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, string $label, array $data = []): bool;
    public function updateEdge(string $id, string $source, string $target, string $label, array $data = []): bool;
    public function deleteEdge(string $id): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?array;
    public function updateNodeStatus(string $id, string $status): bool;

    public function getLogs(int $limit): array;
    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool;
}

interface ServiceInterface
{
    public function getUser(string $id): ?ModelUser;
    public function insertUser(ModelUser $user): bool;
    public function updateUser(ModelUser $user): bool;

    public function getCategories(): array;
    public function insertCategory(ModelCategory $category): bool;
    
    public function getTypes(): array;
    public function insertType(ModelType $type): bool;

    public function getGraph(): ModelGraph;

    public function getNode(string $id): ?ModelNode;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?ModelNode;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(ModelNode $node): bool;
    public function updateNode(ModelNode $node): bool;
    public function deleteNode(ModelNode $node): bool;

    public function getEdge(string $source, string $target): ?ModelEdge;
    public function getEdges(): array;
    public function insertEdge(ModelEdge $edge): bool;
    public function updateEdge(ModelEdge $edge): bool;
    public function deleteEdge(ModelEdge $edge): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?ModelStatus;
    public function updateNodeStatus(ModelStatus $status): bool;

    public function getLogs(int $limit): array;
}

interface HTTPControllerInterface
{
    public function getUser(HTTPRequest $req): HTTPResponseInterface;
    public function insertUser(HTTPRequest $req): HTTPResponseInterface;
    public function updateUser(HTTPRequest $req): HTTPResponseInterface;

    public function getGraph(HTTPRequest $req): HTTPResponseInterface;

    public function getNode(HTTPRequest $req): HTTPResponseInterface;
    public function getNodes(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeParentOf(HTTPRequest $req): HTTPResponseInterface;
    public function getDependentNodesOf(HTTPRequest $req): HTTPResponseInterface;
    public function insertNode(HTTPRequest $req): HTTPResponseInterface;
    public function updateNode(HTTPRequest $req): HTTPResponseInterface;
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface;

    public function getEdge(HTTPRequest $req): HTTPResponseInterface;
    public function getEdges(HTTPRequest $req): HTTPResponseInterface;
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface;
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface;
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface;

    public function getStatus(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface;
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface;

    public function getLogs(HTTPRequest $req): HTTPResponseInterface;
}
```

## Autenticação
Na tela haverá um botão de login caso usuário não estiver logado.
Se o usuário estiver logado, ele verá seu nome no lugar do botão login e uma opção para sair;

## Tela principal
- canvas do grafo
- barra de busca
- fit

## Painel lateral do Nó (ao selecionar)
Seções: cabeçalho com status, métricas-chave, dependentes, pai, últimos logs, runbooks, contatos.

Botões e endpoints:
Investigar
Ver dependentes
Ver pai
Abrir logs

------------------------------------------------
Tela: Edição de Nó / Aresta (SRE)
Elementos: formulário de node/edge, salvar, deletar, cancelar.
Botões → Endpoints:
Salvar nó → HTTPController::insertNode / updateNode → Service::insertNode / updateNode → Database::insertNode / updateNode + insertLog.
Deletar nó → HTTPController::deleteNode → Service::deleteNode → Database::deleteNode + insertLog.
Salvar aresta → HTTPController::insertEdge / updateEdge → Service::insertEdge / updateEdge → Database::insertEdge / updateEdge.
Deletar aresta → HTTPController::deleteEdge → Service::deleteEdge → Database::deleteEdge.

-------------------------------------------------
Tela: Painel de Status e Alertas (Gestores / Negócio)
Elementos: resumo por categoria/tipo, contadores verde/vermelho, timeline.
Botões: Refresh, Filtrar por categoria/tipo/status → HTTPController::getStatus / getNodeStatus → Service::getStatus / getNodeStatus.

-------------------------------------------------

Tela: Administração (Categorias, Tipos, Usuários)
Ações: listar, criar, editar.
Endpoints:
Categorias → getCategories / insertCategory (Controller → Service → Database).
Tipos → getTypes / insertType.
Usuários → getUser / insertUser / updateUser.

-------------------------------------------------

Observabilidade e Auditoria
Logs: botão Ver histórico em cada entidade → getLogs com limit e filtros.
Inserção de log: todas ações mutantes devem chamar Database::insertLog via Service.

-------------------------------------------------

