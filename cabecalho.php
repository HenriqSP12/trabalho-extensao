<?php
// ==========================================================================
// Início comum de todas as páginas: <head> e abertura do <body>.
// Uso (no topo da página, depois do processamento):
//     $titulo = 'Painel';
//     require 'cabecalho.php';
// ==========================================================================
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <meta name="theme-color" content="#f59e0b">
    <title><?= e($titulo) ?> - HP Terraplenagem</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="media/favicon.ico" type="image/x-icon">
</head>
<body>
