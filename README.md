# MageGen

Symfony console based M2 code generation tool.

## Installation

### Clone the repository

```
git clone https://github.com/Skywire/MageGen.git magegen
cd magegen
composer install
chmod +x magegen.php
```

### Download the phar

```
curl -o magegen.phar -L https://github.com/Skywire/MageGen/releases/latest/download/magegen.phar
chmod +x magegen.phar
sudo mv magegen.phar /usr/local/bin/magegen    
```

## Usage

Run from inside a Magento 2 project directory.

`magegen --help`

### Commands

#### Make Module

Create a new Magento module with registration and etc/module.xml files

`magegen make:module [<namespace> [<module>]]`

* vendor - The vendor namespace e.g MyCompanyName
* module - The module name, e.g MyModuleName

#### Make Plugin

Create or update a plugin class.

You can update an existing plugin to add new methods.

`magegen make:plugin [<subject> [<method> [<class> [<type> [<area>]]]]]`

* subject - The plugin subject class or interface, e.g \Magento\Checkout\Api\PaymentInformationManagementInterface
* method - The plugin subject method e.g. savePaymentInformationAndPlaceOrder
* class - The fully qualified class name for your plugin class, e.g.
  \MyCompany\MyModule\Plugin\Model\PaymentInformationPlugin
* type - The plugin type, before, around, after.
* area - The plugin area, global, frontend, adminhtml, etc.

#### Make Entity

Create or update a CRUD entity model, with API interface, resource model and collection

When updating a model you can add new properties, this will add the getters and setters to the interface and the model
class.

`magegen make:entity [<module> [<entity> [<table> [<id>]]]]`

* module - The module name, e.g. MyCompany_MyModule
* entity - The entity model name
* table - The DB table name
* id - The DB table primary key

#### Make Repository

Creates a repository and search result model with interfaces.

`magegen make:repository [<module> [<entity> [<table> [<id>]]]]`

* module - The module name, e.g. MyCompany_MyModule
* entity - The entity model name

#### Make Schema

Create or update db_schema.xml.

Will create entity table with primary key constraint, does not overwrite existing tables

`magegen make:schema [[<module> [<entity>]]]`

* module - The module name, e.g. MyCompany_MyModule
* entity - The entity model name
