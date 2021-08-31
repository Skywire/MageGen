# MageGen

Symfony console based M2 code generation tool.

## Installation

```
git clone https://github.com/Skywire/MageGen.git magegen
cd magegen
composer install
chmod +x magegen.php
```

## Usage

### Commands

All commands expect magepath as the first argument this is the path to the magento 2 installation.

If omitted the current working directory will be used.

#### Make Module

Create a new Magento module with registration and etc/module.xml files

`./magegen.php make:module <magepath> [<namespace> [<module>]]`

* vendor - The vendor namespace e.g MyCompanyName
* module - The module name, e.g MyModuleName

#### Make Plugin

Create or update a plugin class.

You can update an existing plugin to add new methods.

`./magegen.php make:plugin <magepath> [<subject> [<method> [<class> [<type> [<area>]]]]]`

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

`./magegen.php make:entity <magepath> [<module> [<entity> [<table> [<id>]]]]`

* module - The module name, e.g. MyCompany_MyModule
* entity - The entity model name
* table - The DB table name
* id - The DB table primary key
