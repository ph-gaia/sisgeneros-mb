# SisGêneros
> Sistema de Gerenciamento de Gêneros Licitados e Não Licitados do **CeIMBe**

#### Dependências
- MySQL 5.7+ (para versões >= 3.0.0)
- Docker
  - Docker-compose

#### Instalação
A instalação do sistema pode ser feita seguindo os seguintes passos:
> ATENÇÃO: Os passos para instalação descritos nesta documentação, assumem que a aplicação rodará em uma máquina Linux (preferencialmente Ubuntu 16.04 LTS) e que todas a dependências já foram instaladas e configuradas.

1. Clonar ou Baixar a ultima versão deste projeto diretamente na `Home` de usuário
```bash
$ cd ~/
```
Caso você tenha optado por baixar o arquivo zipado da ultima versão, descompacte o mesmo e entre no diretório criado por este processo.
```bash
$ cd ~/sisgeneros-mb-master
```
2. Após executar o passo anterior, será necessário alterar os valores que correspondem a sua OM no arquivo `~/sisgeneros-mb-master/App/Config/Configurations.php`:
```php
// código omitido
    const DOMAIN = 'www.ceimbe.mb';
    const ADMIN_CONTACT = 'E-mail: bruno.monteirodg@gmail.com';
// código omitido
```
A constante `DOMAIN` deve ser alterada para o domínio da sua OM. Quanto a constante `ADMIN_CONTACT`, deve ser alterada para o e-mail do Administrador do sistema.

Também será necessário alterar as informações contidas no arquivo `~/sisgeneros-mb-master/htr.json`. Abra o arquivo `htr.json` e altere os valores de acordo com sua necessidade.

3. Configuração das credenciais de acesso ao Banco de Dados MySQL
Da versão `3.x` em diante, o SGDB usado é o `MySQL`, e para que o sistema possa usá-lo, é necessário alterar algumas entradas no arquivo `App/Config/DatabaseConfig.php`, de acordo com as suas credenciais de acesso.
Os valores que devem ser alterados são `servidor`, `usuario` e `senha`. Por exemplo, considerando que temos o seguinte cenário:
- sevidor: servermydb.om.mb
- usuario: adminbanco
- senha: \$%uPhB#$Sys%*9i3iHTR

então o arquivo `App/Config/DatabaseConfig.php` ficaria da seguinte forma:

```php
// código omitido
    public $db = [
        'servidor' => 'servermydb.om.mb',
        'banco' => 'sisgeneros',
        'usuario' => 'adminbanco',
        'senha' => '$%uPhB#$Sys%*9i3iHTR',
        'opcoes' => [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"],
        // Altere este campo apenas se for usar a Base de Dados Sqlite
        'sqlite' => null
    ];
// código omitido
```

4. Com as constantes alteradas e o arquivo salvo (e fechado), agora será necessário executar o arquivo `docker-compose.yml`:
```bash
$ docker-compose up -d --build
```
5. Após realizar a execução do `docker-compose.yml`

Após realizar todas as configurações descritas acima, já é possível acessar o sistema no browser. O endereço deve parecer com [www.suaom.mb/app/sisgeneros](http://www.suaom.mb/app/sisgeneros).
Por padrão o sistema tem uma conta com nível `ADMINISTRADOR` que pode ser acessada para dar início as edições dentro do sistema. Para acessar o sistema basta usar as seguintes credenciais:
```
usuário: administrador
senha: administrador
```
No primeiro acesso de todo usuário é necessário fornecer uma outra senha.

Agora seu servidor já está configurado e a aplicação já pode ser acessada.
