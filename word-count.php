<?php 
    /**
     * Plugin Name: Word Count
     * Description: This plugin analyzes the content to determine: number of words, number of characters and estimated reading time of a post.
     * Version: 1.0
     * Author: Juan Carlos Jiménez Gutiérrez
     * Author URI: https://github.com/jcarlosj
     */

    # Valida si la funcion no existe
    if( ! class_exists( 'WordCountAndTime_Plugin' )  ) {

        class WordCountAndTime_Plugin {

            function __construct() {
                # Agrega un Callback a un Hook 'admin_menu'
                add_action( 'admin_menu', [ $this, 'addPluginAccessLinkToSettingsMenu' ] );
            }

            # Define la funcionalidad
            function addPluginAccessLinkToSettingsMenu() {

                # Agrega enlace al submenú del menu de Configuración y vincula con un FrontEnd en el ADMIN.
                add_options_page(
                    'Word Count Settings',      # $page_title / Se despliega en la pesta
                    'Word Count',               # $menu_title / Nombre del enlace en el menu
                    'manage_options',           # $capability / Permiso que permite ver, editar y guardar opciones para el sitio web
                    'word-count-setting-page',  # $menu_slug
                    [ $this, 'pageWordCountSettings' ]     # $function
                );
            }

            # FrontEnd: Despliega pagina de Configuracion del Plugin dentro del ADMIN
            function pageWordCountSettings() {
                ?>
                    <h1>Page</h1>
                <?php
            }
        }

        $wordCountAndTimePlugin = new WordCountAndTime_Plugin();

    }


    

    