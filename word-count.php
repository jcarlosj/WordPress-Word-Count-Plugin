<?php 
    /**
     * Plugin Name: Word Count
     * Description: This plugin analyzes the content to determine: number of words, number of characters and estimated reading time of a post.
     * Version: 1.0
     * Author: Juan Carlos Jiménez Gutiérrez
     * Author URI: https://github.com/jcarlosj
     */

    # Agrega un Callback a un Hook 'admin_menu'
    add_action( 'admin_menu', 'addPluginAccessLinkToSettingsMenu' );

    # Valida si la funcion no existe
    if( ! function_exists( 'addPluginAccessLinkToSettingsMenu' )  ) {

        # Define la funcionalidad
        function addPluginAccessLinkToSettingsMenu() {

            # Agrega enlace al submenú del menu de Configuración y vincula con un FrontEnd en el ADMIN.
            add_options_page(
                'Word Count Settings',      # $page_title
                'Word Count',               # $menu_title / Nombre del enlace en el menu
                'manage_options',           # $capability / Permiso que permite ver, editar y guardar opciones para el sitio web
                'word-count-setting-page',  # $menu_slug
                'pageWordCountSettings'     # $function
            );
        }

        # FrontEnd: Despliega pagina de Configuracion del Plugin dentro del ADMIN
        function pageWordCountSettings() {
            ?>
                <h1>Page</h1>
            <?php
        }
    }