@echo off

rem ######################################
rem # Variables
rem ######################################
set project=%1

rem ######################################
rem # Condiciones de corte
rem ######################################
if "%project%" == "" (
    echo Se necesita pasar como primer parametro el proyecto a procesar
    exit /b 2
)
if "%project%" NEQ "principal" (
    if "%project%" NEQ "rentas" (
        if "%project%" NEQ "rrhh" (
            echo El proyecto debe ser `principal`, `rentas`, o `rrhh`
            exit /b 2
        )
    )
)

rem ######################################
rem # Invocación del comando php-cs
rem ######################################
@php "%~dp0php-cs-fixer.phar" fix --config="%~dp0."%project%"_php_cs"
