# Translationsuite plugin for Craft CMS 3.x

The one and only static translation plugin you'll ever need.

![Logo](src/web/assets/dist/img/Translationsuite-icon.svg)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require moshimoshi/translationsuite

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Translationsuite.

## Translationsuite Overview

Translation Suite is a plugin meant to help you with static translations. It allows you to enter translations using
the traditional PHP files, but it also allows you to manage these translations using the UI. This way developers can
provide translations during development while allowing the customer to overwrite these changes.

Currently, preparations are being made to allow you to export your translations (files, db, combined) to csv or excel.
This way you can easily share translations with 3rd parties for translations. The import function is also on its 
way to allow users to import the translations.

## Configuring Translationsuite

-Insert text here-

## Using Translationsuite

-Insert text here-

## Translationsuite Roadmap

Some things to do, and ideas for potential features:

* Export to csv, excel
* Import from csv, excel
* Exports to PHP file to overwrite static translations, handy when you need to sync translations between environments.
* GraphQL Support
* Variable to inject translation in the window object.
* Translate another message based on an existing message using third parties like Google translate, yandex, etc.

Brought to you by [Moshimoshi](moshimoshi.be)
