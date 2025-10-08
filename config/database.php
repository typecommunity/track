<?php
/**
 * UTMTrack - Configuração do Banco de Dados
 * Este arquivo será substituído durante a instalação
 */

return [
    'host' => 'localhost',
    'database' => 'ataw_utm',
    'username' => 'ataw_utm',
    'password' => 'tIKrpvyNHInmIJlt',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];