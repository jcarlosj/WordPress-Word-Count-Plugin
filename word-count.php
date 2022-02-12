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
                add_action( 'admin_init', [ $this, 'settings' ] );
            }

            function settings() {

                # Agregue nueva sección a página de configuración.
                add_settings_section(
                    'wcp_settings_section',     # $id           / ID único como nombre de la seccion
                    null,                       # $title        / Título de la sección (En este caso no lo requerimos)
                    null,                       # $callback     / Callback a ejecutar (En este caso no lo requerimos)
                    'wcp-settings-page'         # $page         / Nombre de la página a la que se asociará la sección
                );

                # Agrega nuevo campo a una sección de página de configuración.
                add_settings_field(
                    'wcp_location',                     # $id           / ID único como nombre del campo
                    'Display location',                 # $title        / Label para el campo
                    [ $this, 'locationField_html' ],    # $callback     / Callback a ejecutar
                    'wcp-settings-page',                # $page         / Nombre de la página a la que se asociará el campo
                    'wcp_settings_section'              # $section      / Nombre de la sección a la que se asociará el campo
                    # $args     / Argumentos adicionales utilizados al generar el campo ('label_for', 'class').
                );

                # Agrega nuevo campo asociandolo un grupo y registrar datos en la tabla wp_options
                register_setting(
                    'wcp-main-section',                 # $option_group / ID único como nombre del grupo
                    'wcp_location',                     # $option_name  / ID único como nombre del campo
                    [                                   # $args         / Describe configuracion para los datos, asignar valores predeterminados
                        'sanitize_callback' => [ $this, 'locationField_sanitize' ],
                        'default' => '0'
                    ] 
                );

                ### Agrega los demás campos para el formulario para con configuración del plugin (sin comentarios)

                # wcp_headline
                add_settings_field( 'wcp_headline', 'Headline text', [ $this, 'headlineField_html' ], 'wcp-settings-page', 'wcp_settings_section' );
                register_setting( 'wcp-main-section', 'wcp_headline', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => 'Post Statistics'
                ] );
                
                # wcp_wordcount
                add_settings_field( 'wcp_wordcount', 'Word count', [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_wordcount' ] );
                register_setting( 'wcp-main-section', 'wcp_wordcount', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '1'
                ] );

                # wcp_charactercount
                add_settings_field( 'wcp_charactercount', 'Character count', [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_charactercount' ] );
                register_setting( 'wcp-main-section', 'wcp_charactercount', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '1'
                ] );

                # wcp_readtime
                add_settings_field( 'wcp_readtime', 'Read time', [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_readtime' ] );
                register_setting( 'wcp-main-section', 'wcp_readtime', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '1'
                ] );

            }

            # Verifica que el valor del campo sea permitido para el registro en la BD
            function locationField_sanitize( $input ) {
                # $input    / Valor que se esta tratando de guardar en la base de datos
                # add_settings_error    / Registra un error de configuración para que se muestre al usuario.
                
                # Validamos que este campo solo tiene dos valores validos: '0' o '1'
                if( $input != '0' && $input != '1' ) {
                    add_settings_error(
                        'wcp_location',                                     # $setting  /  Slug de la configuración a la que se aplica este error (nombre del campo)
                        'wcp_location_error',                               # $code     / Slug-name para identificar el error. Se utiliza como parte del atributo 'id' en la salida HTML.
                        'Display location must be either beginning or end'  # $message  / mensaje con formato que se mostrará al usuario (se mostrará dentro de las etiquetas con estilo <div> y <p>).
                        # $type     / Tipo de mensaje, controla la clase HTML. Los valores posibles incluyen  'error', 'success', 'warning', 'info'. Valor por defecto: 'error'
                    );   

                    return get_option( 'wcp_location' );        # Retorna el valor actual en la base de datos
                }

                return $input;      # Retorma el valor recibido
            }

            # FrontEnd (Admin): Despliega todos los campos de tipo 'checkbox' en página de configuración
            function checkboxField_html( $args ) {
                # checked   / Compara los dos primeros argumentos y, si son idénticos, marca como marcado.
                ?>
                    <input 
                        type="checkbox"
                        name="<?php echo $args[ 'name_field' ]; ?>" 
                        value="1" 
                        <?php checked( get_option( $args[ 'name_field' ] ), '1' ); ?> 
                    />
                <?php
            }

            # FrontEnd (Admin): Despliega campo 'wcp_headline' en página de configuración
            function headlineField_html() {
                # esc_attr  / Escapando para atributos HTML.
                ?>
                    <input type="text" name="wcp_headline" value="<?php echo esc_attr( get_option( 'wcp_headline' ) ); ?>" />
                <?php
            }

            # FrontEnd (Admin): Despliega campo 'wcp_location' en página de configuración
            function locationField_html() {
                # get_option    / Recupera un valor de opción basado en un nombre de opción
                # selected      / Compara los dos primeros argumentos y, si son idénticos, los marca como seleccionados.
                ?>
                    <select name="wcp_location">
                        <option value="0" <?php selected( get_option( 'wcp_location' ), '0' ); ?>>Beginning of post</option>
                        <option value="1" <?php selected( get_option( 'wcp_location' ), '1' ); ?>>End of post</option>
                    </select>
                <?php
            }

            # FrontEnd (Admin): Agrega enlace de acceso a la página desde el menu de configuración de WP
            function addPluginAccessLinkToSettingsMenu() {

                # Agrega enlace al submenú del menu de Configuración y vincula con un FrontEnd en el ADMIN.
                add_options_page(
                    'Word Count Settings',          # $page_title   / Título de la página que se despliega en la pestaña del navegador
                    'Word Count',                   # $menu_title   / Nombre del item de menú que se desplegará en el FrontEnd (Admin)
                    'manage_options',               # $capability   / Permiso que permite ver, editar y guardar opciones para el sitio web (Admin)
                    'wcp-settings-page',            # $menu_slug    / Nombre del Slug de página (URL: Admin)
                    [ $this, 'settingsPage_html' ]  # $function     / Callback a ejecutar
                );
            }

            # FrontEnd (Admin): Despliega pagina de Configuracion del Plugin dentro del ADMIN
            function settingsPage_html() {
                ?>
                    <div class="wrap">
                        <h1>Word Count Settings</h1>
                        <form action="options.php" method="POST">
                            <?php
                                settings_fields( 'wcp-main-section' );               # WP agrega campos de salida nonce, action y option_page para una página de configuración (permite además salvar los cambios del campo)
                                do_settings_sections( 'wcp-settings-page' );  # WP mostrará automáticamente cualquier seccion y los campos creados para esta pagina
                                submit_button();
                            ?>
                        </form>
                    </div>

                <?php
            }
        }

        $wordCountAndTimePlugin = new WordCountAndTime_Plugin();

    }


    

    