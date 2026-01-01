# Audit Module

## Domain events
- AuditLogCreated

## Commands
- log

## Queries
- getAuditHistory
- getEntityHistory

## Actors
- User
- Automation

## Aggregates
- AuditLog




# Authorization Module
## Domain events
- UserAccountCreated
- UserAccountUpdated

## Commands
- createUserAccount
- updateUserAccount

## Queries
- getUserAccount

## Actors
- User
- Automation

## Aggregates
- User



# Graph Module

## Domain events
- NodeInserted
- NodeUpdated
- NodeDeleted
- EdgeInserted
- EdgeUpdated
- EdgeDeleted

## Commands
- insertNode
- updateNode
- deleteNode
- insertEdge
- updateEdge
- deleteEdge

## Queries
- getNode
- getNodes
- getNodeExists
- getEdge
- getEdges
- getEdgeExists

## Actors
- User
- Automation

## Aggregates
- Node
- Edge

# Status Module

## Domain events
- NodeStatusUpdated

## Commands
- updateNodeStatus

## Queries
- getStatuses
- getNodeStatus

## Actors
- User
- Automation

## Aggregates
- Node
- Status
