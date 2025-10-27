SCHEMA
Attendant
Modelo de atendente do sistema

Properties
id
string
ID único gerado automaticamente

name
string
Nome do atendente

phone
string
Número de telefone

email
string
Endereço de e-mail

department
string
Departamento de atuação

customField01
string
Campo personalizável 01

customField02
string
Campo personalizável 02

owner
string
Responsável pelo cadastro

created
string
Data de criação automática

updated
string
Data de atualização automática

Example
{
  "id": "r1234abcd",
  "name": "João da Silva",
  "phone": "+5511999999999",
  "email": "joao@empresa.com",
  "department": "Suporte Técnico",
  "customField01": "Turno: Manhã",
  "customField02": "Nível: 2",
  "owner": "admin",
  "created": "2025-01-24T13:52:19.000Z",
  "updated": "2025-01-24T13:52:19.000Z"
}