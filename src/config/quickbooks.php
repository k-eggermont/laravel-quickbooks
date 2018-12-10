<?php

return [

    // Throw Exception on Error
    "throw_exception_on_error" => true,

    // Log Location
    "log_location" => "/tmp/logs_quickbooks",

    // Enable log
    "enable_log" => true,

    // Custom minor version => null = auto
    "minor_version" => null,

    // Auto pulling data from Quickbooks
    "autoPullData" => [
        "enable" => true,
        "items" => ["CreditMemo", "Invoice", "PurchaseOrder","Bill"]
    ],

    // Oauth configuration, to get all informations, use the playground => https://developer.intuit.com/v2/ui#/playground
    "Oauth" => [
        "auth_mode" => "oauth2",
        "ClientID" => "",
        "ClientSecret" => "",
        "RealmID" => "",
        "AuthorizationCode" => "",
        "refreshTokenKey" => "",
        "accessTokenKey" => "",
        "baseUrl" => "Development",
        "scope" => "com.intuit.quickbooks.accounting"
    ],

    // VAT CONVERSION
    "VAT" => [
        "expense" => [
            "10" => "10 % TVA FR",
            "0" => "Pas de TVA FR (Achats)",
            "5.5" => "5,5 % TVA FR",
            "20" => "20 % TVA FR"
        ],
        "income" => [
            "10" => "10 % TVA FR",
            "0" => "Pas de TVA FR (Ventes)",
            "5.5" => "5,5 % TVA FR",
            "20" => "20 % TVA FR"
        ]
    ],

    "pdf" => [
        // Enable download pdf for Invoice
        "download" => true,

        // You can use all disks on filesystem (ex: public, local, s3 ..)
        "disk" => "public",

        // Naming rules (auto => auto naming with QB, md5 => hashed name)
        "filename" => "auto",

        // Folder
        "folder" => "invoices_qb/"
    ],




];