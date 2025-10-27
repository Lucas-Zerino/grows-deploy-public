SCHEMA
ChatbotAIFunction
Properties
id
string
ID único da função gerado automaticamente

name
string
required
Nome da função

description
string
required
Descrição da função

active
boolean
Indica se a função está ativa

method
string
required
Método HTTP da requisição

endpoint
string
required
Endpoint da API

headers
string
Cabeçalhos da requisição

body
string
Corpo da requisição

parameters
string
Parâmetros da função

undocumentedParameters
string
Parâmetros não documentados

header_error
boolean
Indica erro de formatação nos cabeçalhos

body_error
boolean
Indica erro de formatação no corpo

owner
string
Proprietário da função

created
string
Data de criação

updated
string
Data de atualização