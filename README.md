# SpeechToText
Web Site using AI resources in Microsoft Azure via REST API
The web site (which allows the upload of a file which will then be processed by an AI system) is coded in PHP 
The site architecture contains the following Serverless components:
1. an web service containing an web site which allows the upload of a voice recording (in English or Romanian) and then stores it using other services
2. the file swill be stored in blob storage
3. information about the files will be stored in an SQL DataBase 
4. The files will be processed using a speech to text AI and the output will be the coresponding  text 


git clone https://github.com/Azure/azure-storage-php.git
cd ./azure-storage-php
Install via Composer
Create a file named composer.json in the root of your project and add the following code to it:
{
  "require": {
    "microsoft/azure-storage-blob": "*"
  }
}
Download composer.phar in your project root.

Open a command prompt and execute this in your project root

php composer.phar install
