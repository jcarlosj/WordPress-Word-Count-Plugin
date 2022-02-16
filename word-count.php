<?php 
    /**
     * Plugin Name: Word Count
     * Description: This plugin analyzes the content to determine: number of words, number of characters and estimated reading time of a post.
     * Version: 1.0
     * Author: Juan Carlos Jiménez Gutiérrez
     * Author URI: https://github.com/jcarlosj
     * Text Domain: wcpdomain
     * Domain Path: /languages
     */

    # Valida si la funcion no existe
    if( ! class_exists( 'WordCountAndTime_Plugin' )  ) {

        class WordCountAndTime_Plugin {

            private $words_per_minute = 255;

            function __construct() {
                # Agrega un Callback a un Hook 'admin_menu'
                add_action( 'admin_menu', [ $this, 'addPluginAccessLinkToSettingsMenu' ] );
                add_action( 'admin_init', [ $this, 'settings' ] );
                add_filter( 'the_content', [ $this, 'addContentWrapper' ] );
                add_action( 'init', [ $this, 'languages' ] );
            }

            function get_reading_time( $wordCounter ) {
                # El adulto promedio lee entre 200 y 225 palabras por minuto
                return round( $wordCounter / $this -> words_per_minute );
            }

            # Agrega soporte internacionalización
            function languages() {
                # dirname   / Devuelve la ruta de un directorio padre
                # __FILE__  / Constante mágica de PHP. Ruta completa y nombre del fichero con enlaces simbólicos resueltos.
                # load_plugin_textdomain    / Carga las cadenas traducidas de un complemento.
                # plugin_basename   / Obtiene el nombre base de un complemento.
                load_plugin_textdomain( 
                    'wcpdomain',                                            # $domain   / ID único para recuperar cadenas traducidas
                    false,                                                  # $deprecated   / (Optional) Obsoleto. Utilice el parámetro $plugin_rel_path en su lugar. Valor predeterminado: false
                    dirname( plugin_basename( __FILE__ ) ). '/languages'    # $plugin_rel_path  / (Opcional) Ruta relativa a WP_PLUGIN_DIR donde reside el archivo .mo. Valor predeterminado: false
                );
            }

            # Agrega un envoltorio al contenido si se requiere
            function addContentWrapper( $content ) {
                # get_option    / Usa el segundo parametro como valor por defecto en caso de no recuperar un valor de la BD

                # Verifica que las opciones del plugin estén habilitadas para desplegar el contador de palabras
                if( is_main_query() AND is_single() AND 
                    ( 
                        get_option( 'wcp_wordcount', '1' ) OR 
                        get_option( 'wcp_charactercount', '1' ) OR 
                        get_option( 'wcp_readtimecount', '1' ) 
                    )
                ) {
                    return $this -> wordCount_html( $content );
                }

                return $content;
            }

            # Despliega contador de palabras junto con el contenido de la entrada
            function wordCount_html( $content ) {

                # Verifica que las opciones de "conteo de palabras" y "tiempo de lectura" esten activados, para realizar el conteo de las palabras
                if( get_option( 'wcp_wordcount', '1' ) OR get_option( 'wcp_readtime', '1' ) ) {
                    # strip_tags    / Retira las etiquetas HTML y PHP de un string
                    $wordCounter = str_word_count( strip_tags( $content ) );
                }

                $html = '';
                
                # __    / Recupera la traducción de $text.   
                # Traduce cadena que viene de la tabla xx_options
                $template_html = "<h3>" .get_option( 'wcp_headline', esc_html_x( 'Post Statistics', 'DB: table _options', 'wcpdomain' ) ). "</h3><p>";

                # Verifica que la opcion de contador de palabras este habilitada para agregarla a la vista
                if( get_option( 'wcp_wordcount', '1' ) ) {
                    ### PLURALIZATION
                    # sprintf   / Devuelve un string formateado
                    # _nx       / Traduce y recupera la forma singular o plural en función del número proporcionado, con contexto gettext.
                    $template_html .= sprintf( 
                        _nx( 
                            'This post has %s word', 
                            'This post has %s words', 
                            $wordCounter, 
                            'time measurement', 
                            'wcpdomain' 
                        ), 
                        $wordCounter
                    ) .'<br />';
                }

                # Verifica que la opcion de contador de caracteres este habilitada para agregarla a la vista
                if( get_option( 'wcp_charactercount', '1' ) ) {
                    # wp_strip_all_tags     / Elimina correctamente todas las etiquetas HTML, incluido el script y el estilo.
                    $characterCounter = strlen( wp_strip_all_tags( $content ) );

                    ### PLURALIZATION
                    # sprintf   / Devuelve un string formateado
                    $template_html .= sprintf( 
                        _nx( 
                            'This post has %s character', 
                            'This post has %s characters', 
                            $characterCounter, 
                            'time measurement', 
                            'wcpdomain' 
                        ), 
                        $characterCounter
                    ) .'<br />';
                }

                # Verifica que la opcion de tiempo estimado este habilitada para agregarla a la vista
                if( get_option( 'wcp_readtimecount', '1' ) ) {
                    
                    # Verifica que la cantidad de caracteres sea > a 0 para que pueda desplegarse el tiempo estimado
                    if( strlen( wp_strip_all_tags( $content ) ) > 0 ) {

                        $time = $this -> get_reading_time( $wordCounter );

                        $template_less_than_minute .= sprintf( 
                            _nx(
                                'This post will take %s minute to read.',
                                'This post will take less than 1 minute to read.',
                                $time,
                                'read_time_less_than_minute',
                                'wcpdomain'
                            ),
                            $time
                        ). '<br />';
                     
                        $template_more_than_minute .= sprintf( 
                            _nx(
                                'This post will take %s minute to read.',
                                'This post will take about %s minutes to read.',
                                $time,
                                'read_time_more_than_minute',
                                'wcpdomain'
                            ),
                            $time
                        ). '<br />';
                        
                        if( $this -> less_than_a_minute_to_read_minute( $wordCounter ) ) {
                            $template_html .= $template_less_than_minute;
                        }
                        else {
                            $template_html .= $template_more_than_minute;
                        }
                    }
                
                }

                if( get_option( 'wcp_location', '0' ) == '0' ) {
                    return $template_html .$content;
                } 

                return $content .$template_html;
            }

            # Verifica si toma menos de un minuto leer la publicacion
            function less_than_a_minute_to_read_minute( $wordCounter ) {
                return $this -> words_per_minute >= $wordCounter && 0 == $time;
            }

            # Configuracion de los campos del formulario de la pagina de configuración del plugin
            function settings() {

                # Agregue nueva sección a página de configuración.
                add_settings_section(
                    'wcp_settings_section',     # $id           / ID único como nombre de la seccion
                    null,                       # $title        / Título de la sección (En este caso no lo requerimos)
                    null,                       # $callback     / Callback a ejecutar (En este caso no lo requerimos)
                    'wcp-settings-page'         # $page         / Nombre de la página a la que se asociará la sección
                );

                # Agrega nuevo campo a una sección de página de configuración.
                # esc_html_e    / Muestre el texto traducido que se ha escapado para un uso seguro en la salida HTML.
                add_settings_field(
                    'wcp_location',                     # $id           / ID único como nombre del campo
                    esc_html__( 'Display location', 'wcpdomain' ),                 # $title        / Label para el campo
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
                add_settings_field( 'wcp_headline', esc_html__( 'Headline text', 'wcpdomain' ) , [ $this, 'headlineField_html' ], 'wcp-settings-page', 'wcp_settings_section' );
                register_setting( 'wcp-main-section', 'wcp_headline', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => 'Post Statistics'
                ] );
                
                # wcp_wordcount
                add_settings_field( 'wcp_wordcount', esc_html__( 'Word Count', 'wcpdomain' ), [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_wordcount' ] );
                register_setting( 'wcp-main-section', 'wcp_wordcount', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '1'
                ] );

                # wcp_charactercount
                add_settings_field( 'wcp_charactercount', esc_html__( 'Character count', 'wcpdomain' ), [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_charactercount' ] );
                register_setting( 'wcp-main-section', 'wcp_charactercount', [
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '1'
                ] );

                # wcp_readtime
                add_settings_field( 'wcp_readtime', esc_html__( 'Read time', 'wcpdomain' ), [ $this, 'checkboxField_html' ], 'wcp-settings-page', 'wcp_settings_section', [ 'name_field' => 'wcp_readtime' ] );
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
                    <input type="text" name="wcp_headline" value="<?php echo esc_attr( get_option( 'wcp_headline' ), 'wcpdomain' ); ?>" />
                <?php
            }

            # FrontEnd (Admin): Despliega campo 'wcp_location' en página de configuración
            function locationField_html() {
                # get_option    / Recupera un valor de opción basado en un nombre de opción
                # selected      / Compara los dos primeros argumentos y, si son idénticos, los marca como seleccionados.
                ?>
                    <select name="wcp_location">
                        <option value="0" <?php selected( get_option( 'wcp_location' ), '0' ); ?>>
                            <?php _e( 'Beginning of post', 'wcpdomain' ); ?>
                        </option>
                        <option value="1" <?php selected( get_option( 'wcp_location' ), '1' ); ?>>
                            <?php _e( 'End of post', 'wcpdomain' ); ?>
                        </option>
                    </select>
                <?php
            }

            # FrontEnd (Admin): Agrega enlace de acceso a la página desde el menu de configuración de WP
            function addPluginAccessLinkToSettingsMenu() {

                # Agrega enlace al submenú del menu de Configuración y vincula con un FrontEnd en el ADMIN.
                add_options_page(
                    esc_html__( 'Word Count Settings', 'wcpdomain' ),          # $page_title   / Título de la página que se despliega en la pestaña del navegador
                    esc_html__( 'Word Count', 'wcpdomain' ),      # $menu_title   / Nombre del item de menú que se desplegará en el FrontEnd (Admin)
                    'manage_options',               # $capability   / Permiso que permite ver, editar y guardar opciones para el sitio web (Admin)
                    'wcp-settings-page',            # $menu_slug    / Nombre del Slug de página (URL: Admin)
                    [ $this, 'settingsPage_html' ]  # $function     / Callback a ejecutar
                );
            }

            # FrontEnd (Admin): Despliega pagina de Configuracion del Plugin dentro del ADMIN
            function settingsPage_html() {
                ?>
                    <div class="wrap">
                        <h1><?php esc_html_e( 'Word Count Settings', 'wcpdomain' ); ?></h1>
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


    

    