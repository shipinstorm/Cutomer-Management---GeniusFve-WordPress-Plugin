<?php
add_action('init', 'register_script');
function register_script() {
    wp_register_script( 'customer_zakaznici_js', plugins_url('/js/zakaznici.js', __DIR__), null, null, false);
    // wp_register_script( 'my_jQuery', 'https://code.jquery.com/jquery-3.5.1.js', null, null, false );
    wp_register_script( 'dataTables', 'https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js', null, null, false );
    wp_register_script( 'dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.js', null, null, false );

    wp_register_style( 'cm_nabidky_style', plugins_url('/css/nabidky.css', __DIR__), false, '1.0.0', 'all');
    wp_register_style( 'my_style', plugins_url('/css/custom.css', __DIR__), false, '1.0.0', 'all');
    // wp_register_style( 'bootstrap_style', plugins_url('/css/customer-management.css', __DIR__), false, '1.0.0', 'all');
    wp_register_style( 'dataTables', 'https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css', null, null, false );
    wp_register_style( 'dropzone', 'https://unpkg.com/dropzone@5/dist/min/dropzone.min.css', null, null, false );
}

add_action('admin_enqueue_scripts', 'enqueue_style');
function enqueue_style(){
    wp_enqueue_script('custom_jquery_script', "https://code.jquery.com/jquery-3.2.1.min.js");
	wp_enqueue_script('custom_popper_script', "https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js");
    wp_enqueue_script('customer_zakaznici_js');
    // wp_enqueue_script('my_jQuery');
    wp_enqueue_script('dataTables');
    wp_enqueue_script('dropzone');
    wp_enqueue_script('dropzone', 'path/to/dropzone', array('jquery'));
    wp_enqueue_script('my-script', 'path/to/script', array('jquery', 'dropzone'));
    wp_enqueue_script('custom_bootstrap_script', "https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js");
    $drop_param = array(
        'upload'=>admin_url( 'admin-ajax.php?action=handle_dropped_media' ),
        'delete'=>admin_url( 'admin-ajax.php?action=handle_deleted_media' ),
    );
    wp_localize_script('my-script','dropParam', $drop_param);
    
    wp_enqueue_style( 'cm_nabidky_style' );
    wp_enqueue_style( 'my_style' );
    // wp_enqueue_style( 'bootstrap_style' );
    wp_enqueue_style( 'dataTables' );
    wp_enqueue_style( 'dropzone' );
}

add_action( 'admin_menu', 'customer_management_info_menu' );
function customer_management_info_menu() {
    $parent_slug = 'my-top-level-slug';
    $page_title  = 'WordPress Customer Management Info';
    $menu_title  = 'Zákazníci';
    $capability  = 'customer_manage_options';
    $menu_slug   = 'customer-management';
    $function    = 'customer_management_info_page';
    $position    = 1;
    add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $position );

    $parent_slug = 'my-top-level-slug';
    $page_title  = 'Formulář poptávky manage';
    $menu_title  = 'Formulář_poptávky';
    $capability  = 'customer_manage_options';
    $menu_slug   = 'formular_poptavky';
    $function    = 'formular_poptavky_info_page';
    $position    = 2;
    add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $position );

    $parent_slug = 'my-top-level-slug';
    $page_title  = 'Obhlídka manage';
    $menu_title  = 'Obhlídka';
    $capability  = 'customer_manage_options';
    $menu_slug   = 'obhlidka';
    $function    = 'obhlidka_info_page';
    add_submenu_page( null, $page_title, $menu_title, $capability, $menu_slug, $function );
}

register_activation_hook( __FILE__, 'psp_add_project_management_role' );
function psp_add_project_management_role() {
    add_role(
        'psp_project_manager',
        'Customer Manager',
        array(
            'read'          => true,
            'edit_posts'    => false,
            'delete_posts'  => false,
            'publish_posts' => false,
            'upload_files'  => true,
        )
    );

    $roles = array('psp_project_manager', 'administrator');
    foreach ($roles as $the_role) { 
        if (wp_roles()->is_role($the_role)) {
            $role = get_role($the_role);

            $role->add_cap('customer_manage_options');
            $role->add_cap( 'read_psp_project');
            $role->add_cap( 'read_private_psp_projects' );
            $role->add_cap( 'edit_psp_project' );
            $role->add_cap( 'edit_psp_projects' );
            $role->add_cap( 'edit_others_psp_projects' );
            $role->add_cap( 'edit_published_psp_projects' );
            $role->add_cap( 'publish_psp_projects' );
            $role->add_cap( 'delete_others_psp_projects' );
            $role->add_cap( 'delete_private_psp_projects' );
            $role->add_cap( 'delete_published_psp_projects' );
        }
    }
}

register_deactivation_hook( __FILE__, 'psp_remove_project_management_role' );
function psp_remove_project_management_role() {
    $wp_roles = new WP_Roles();
    $wp_roles->remove_role('psp_project_manager');
}

add_filter( 'register_post_type_args', 'change_capabilities_of_the_zakaznici' , 10, 2 );
function change_capabilities_of_the_zakaznici( $args, $post_type ){
    if ( 'zakaznik' !== $post_type ) {
        return $args;
    }

    $args['capability_type'] = array('psp_project','psp_projects');
    $args['map_meta_cap'] = true;
    
    return $args;
}

function add_customers_automatically() {
    set_time_limit( 5000 );

    global $wpdb;
    $postmetaTable = $wpdb->prefix.'postmeta';

    $args = array(
        'post_type' => 'zakaznik',
        'post_status' => 'publish',
        'numberposts' => -1,
    );

    $posts = get_posts($args);
    foreach ($posts as $post) {
        if ($post->post_title == 'Kamil Nejedlý') continue;
        if ($post->post_title == 'ROMAN ŠČUKA') continue;
        if ($post->post_title == 'Jiří Dvořák') continue;
        
        
        $formular = $wpdb->get_results ( "SELECT meta_value FROM $postmetaTable WHERE `post_id` = $post->ID AND `meta_key` = 'formular'" )[0]->meta_value;
        $formular_post = get_post($formular);
        $args = array(
            'ID' => $post->ID,
            'post_date_gmt' => $formular_post->post_date_gmt,
            'post_date' => $formular_post->post_date,
        );
        wp_update_post($args);
    }
}

$osobaDefaultString = ['', 'Dorota', 'Polda', 'Dorota', 'Elektrikář', 'Lia', 'Dorota', 'Lia', 'Věra', 'Věra', 'Věra', 'Věra', 'Věra', 'Jirka', 'Dorota'];

if( !function_exists("customer_management_info_page") ) {
    function customer_management_info_page() {
//	add_customers_automatically();

        ?>
        <!-- Modal -->
        <div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="customerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content form-style-5">
                    <div class="modal-header">
                        <h5 id="c_m_title" class="modal-title"></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="c_m_body">
                        <legend><span class="number">1</span> Informace o zákazníkovi</legend>
                        <div class="form-group row">
                            <label id="c_m_number" class="col-sm-6 col-form-label">Číslo zákazníka: </label>
                            <label id="c_m_name" class="col-sm-6 col-form-label">Název: </label>
                        </div>
                        <div class="form-group row">
                            <label id="c_m_status" class="col-sm-6 col-form-label">Stav: </label>
                            <label id="c_m_responsible_person" class="col-sm-6 col-form-label">Odpovědná Osoba: </label>
                        </div>
                        <div class="form-group row">
                            <label id="c_m_email" class="col-sm-6 col-form-label">E-mail: </label>
                            <label id="c_m_telephone" class="col-sm-6 col-form-label">Telefon: </label>
                        </div>
                        <div class="form-group row">
                            <label id="c_m_region" class="col-sm-6 col-form-label">Kraj: </label>
                            <label id="c_m_address" class="col-sm-6 col-form-label">Adresa realizace: </label>
                        </div>
                        <div class="form-group row">
                            <label id="c_m_end_date" class="col-sm-6 col-form-label">Termín: </label>
                            <label id="c_m_created_date" class="col-sm-6 col-form-label">Datum vytvoření: </label>
                        </div>
                        <legend><span class="number">2</span> Aktivita</legend>
                        <div class="form-group row" style="display: flex; justify-content: space-around;">
                            <a id="c_m_zakaznici" href="#">Zákazníci</a>
                            <a id="c_m_formular_poptavky" href="#">Formulář poptávky</a>
                            <a id="c_m_obhlidka" href="#">Obhlídka</a>
                            <a id="c_m_nabidky" href="#">Nabídky</a>
                        </div>
                        <legend><span class="number">3</span> Komentář</legend>
                        <div class="form-group row" id="c_m_comment">
                            <textarea class="col-sm-9" oninput="auto_grow(this)" placeholder="Napsat komentář..."></textarea>
                            <button class="offset-sm-1 col-sm-2 btn btn-primary comment-save" style="height: 50px;" data-id="">Uložit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h1>Customer Info</h1>
        <table class="table" style="text-align: center;" id="myTable">
            <thead>
                <tr>
                    <th style="display: none;">Číslo zákazníka</th>
                    <th>Termín</th>
                    <th>Název</th>
                    <th>Stav</th>
                    <th style="display: none;"></th>
                    <th>Odpovědná Osoba</th>
                    <th style="display: none;"></th>
                    <th style="display: none;">E-mail</th>
                    <th style="display: none;">Telefon</th>
                    <th>Kraj</th>
                    <th style="display: none;">Adresa realizace</th>
                    <th style="display: none;">Datum vytvoření</th>
                    <th style="display: none;">Termín</th>
                    <th>Zákazníci</th>
                    <th>Formulář poptávky</th>
                    <th>Obhlídka</th>
                    <th>Nabídky</th>
                    <th style="display: none;">Poznámka</th>
                    <th>Poznámka</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $paged = ( filter_input( INPUT_GET, "paged", FILTER_SANITIZE_STRING ) ) ? filter_input( INPUT_GET, "paged", FILTER_SANITIZE_STRING ) : 0;

                $args = array(
                    'post_type'     => 'zakaznik',
                    'post_status'   => 'publish',
                    'numberposts'   => -1,
                    'paged'         => $paged,
                    'orderby'       => 'date',
                    'order'         => 'DESC',
                );
                $posts = get_posts($args);

                $args = array(
                    'post_type' => 'stav',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'id',
                    'order' => 'ASC'
                );
                $stavs = get_posts($args);
                // echo $stavs[0]->ID . '   ' . $stavs[0]->post_title;

                $stavTitles = array();
                foreach ($stavs as $stav) {
                    array_push($stavTitles, $stav->post_title);
                }

                $args = array(
                    'post_type' => 'osoba',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'id',
                    'order' => 'ASC'
                );
                $osobas = get_posts($args);

                $osobaTitles = array();
                foreach ($osobas as $osoba) {
                    array_push($osobaTitles, $osoba->post_title);
                }

                $i = 1;
                foreach($posts as $post) {
                    list($date, $time) = explode(" ", $post->post_date_gmt);
                    $date = str_replace('-', '', $date);
                    $customer_number = $date . $post->ID;

                    $meta = get_post_meta($post->ID);
                    $title = $post->post_title;
                    $email = get_post_meta($post->ID, 'e-mail', true);
                    $telefon = get_post_meta($post->ID, 'telefon', true);
                    $adresa_realizace = get_post_meta($post->ID, 'adresa_realizace', true);
                    $status = get_post_meta($post->ID, 'status', true);

                    if (!$status || $status < 20)
                        $status = $stavs[0]->ID;
                    // if ($status < 20)
                    //     update_post_meta($post->ID, 'status', 2813 + $status);
                    
                    $style = '';
                    $htmlStatus = '<select class="form-select status-select" data-id="' . $post->ID . '" value="' . $status . '">';
                    $stavAAA = $stavs[0]->post_title;
                    for ($j = 0; $j < count($stavs); $j++) {
                        $htmlStatus .= $status == $stavs[$j]->ID ?
                                '<option value="' . $stavs[$j]->ID . '" selected>' . $stavs[$j]->post_title . '</option>' :
                                '<option value="' . $stavs[$j]->ID . '">' . $stavs[$j]->post_title . '</option>';

                        if ($stavs[$j]->ID == $status)
                            $stavAAA = $stavs[$j]->post_title;

                        $barva = get_field('barva', $stavs[$j]->ID);
                        if ($status == $stavs[$j]->ID)
                            $style = ' style="background: ' . $barva . '"';
                    }
                    if ($style == '')
                        $style = ' style="background: ' . get_field('barva', $stavs[0]->ID) . '"';
                    $htmlStatus .= '</select>';

                    if (empty($meta['odpovedna_osoba'])) {
                        $odpovedna = get_field('odpovedna_osoba', $stavs[0]->ID);
                        $odpovednaOsoba = $odpovedna->post_title;
                    } else {
                        $odpovednaOsoba = $meta['odpovedna_osoba'][0];
                    }
                    $htmlOsoba = '<select class="form-select osoba" data-id="' . $post->ID . '" status-id="' . $status . '" value="' . $odpovednaOsoba . '">';
                    for ($j = 0; $j < count($osobas); $j++) {
                        $htmlOsoba .= $osobas[$j]->post_title == $odpovednaOsoba ?
                                '<option value="' . $osobas[$j]->post_title . '" selected>' . $osobas[$j]->post_title . '</option>' :
                                '<option value="' . $osobas[$j]->post_title . '">' . $osobas[$j]->post_title . '</option>';
                    }
                    $htmlOsoba .= '</select>';

                    $formular = get_post_meta($post->ID, 'formular', true);
                    $obhlidka = get_post_meta($post->ID, 'obhlidka', true);
                    $termin   = get_post_meta($post->ID, 'nabidky_created_date', true);
                    $nabidky_date = get_post_meta($status, 'nabidky_date', true);
                    $termin_style = '';
                    if ($termin && $nabidky_date) {
                        $termin = date('Y-m-d', strtotime($termin . '+ ' . $nabidky_date . ' days'));

                        $today = date('Y-m-d');
                        if (strtotime($termin) < strtotime($today)) {
                            $termin_style = 'color: #ED1C24';
                        }
                    }
                    $poznamka = get_post_meta($post->ID, 'poznamka', true);

                    if ($meta['poznamka'][0]) {
                        $current_user = wp_get_current_user();
                        $comment_data = array(
                            'comment_post_ID'      => $post->ID,
                            'comment_content'      => $meta['poznamka'][0],
                            'user_id'              => $current_user->ID,
                            'comment_author'       => $current_user->user_login,
                            'comment_author_email' => $current_user->user_email,
                            'comment_author_url'   => $current_user->user_url,
                        );
                        wp_insert_comment($comment_data);
                        delete_post_meta($post->ID, 'poznamka');
                    }

                    $comments = get_comments( array( 'post_id' => $post->ID ) );

                    $formular_meta = get_post_meta($formular);
                    $kraj = $formular_meta['_field_63'][0];
                    $kraj = $kraj != null ? $kraj : 'Hlavní město Praha';

                    for ($j = 1; $j <= count($GLOBALS["kraj_arr"]); $j++) {
                        if ($kraj == $GLOBALS["kraj_arr"][$j - 1]['wrong'])
                            $kraj = $GLOBALS["kraj_arr"][$j - 1]['right'];
                    }

                    echo '<tr data-id="' . $post->ID . '">' .
                            '<td style="display: none;">' . $customer_number . '</td>' .
                            '<td style="' . $termin_style . '">' . $termin . '</td>' .
                            '<td><a href="#customerModal" data-toggle="modal" data-target="#customerModal">' . $title . '</a></td>' .
                            '<td' . $style . '>' . $htmlStatus . '</td>' .
                            '<td style="display: none;">' . $stavAAA . '</td>' .
                            '<td>' . $htmlOsoba . '</td>' .
                            '<td style="display: none;">' . $odpovednaOsoba . '</td>' .
                            '<td style="display: none;">' . $email . '</td>' .
                            '<td style="display: none;">' . $telefon . '</td>' .
                            '<td>' . $kraj . '</td>' .
                            '<td style="display: none;">' . $adresa_realizace . '</td>' .
                            '<td style="display: none;">' . $post->post_date_gmt . '</td>' .
                            '<td style="display: none;">' . $termin . '</td>' .
                            '<td><a href="' . get_edit_post_link($post->ID) . '">Upravit</a>' . '</td>' .
                            '<td>' .
                                '<a href="' . add_query_arg( array(
                                                'customer_id' => $post->ID,
                                                'post_id' => $formular,
                                            ), admin_url('admin.php?page=formular_poptavky') ) . '">Zobrazit</a>' .
                            '</td>' .
                            '<td>' .
                                '<a href="' . add_query_arg( array(
                                                'customer_id' => $post->ID,
                                                'post_id' => $obhlidka,
                                                'new_form_flag' => 0,
                                            ), admin_url('admin.php?page=obhlidka') ) . '">Zobrazit</a>' .
                            '</td>' .
                            '<td>' .
                                '<a href="' . add_query_arg( array(
                                                'customer_id' => $post->ID,
                                            ), admin_url('post-new.php?post_type=nabidky') ) . '">Vytvořit</a>' .
                            '</td>' .
                            '<td style="display: none;" class="poznamka" data-id="' . $post->ID . '">';
                    foreach ( $comments as $comment ) {
                        echo    '<div>' . $comment->comment_author . '</div>';
                        echo    '<div>' . $comment->comment_date . '</div>';
                        echo    '<div>' . $comment->comment_content . '</div>';
                    }
                    echo    '</td>' .
                            '<td class="last-poznamka">' . strip_tags($comments[0]->comment_content) . '</td>' .
                    '</tr>';

                    $i++;
                }
                ?>
            </tbody>
        </table>

        <script>
        $ = jQuery;

        jQuery(document).on('click', '.comment-save', function() {
            let commentDOM = jQuery(event.target).closest('div').find('textarea');
            let comment = jQuery(event.target).closest('div').find('textarea').val();
            let postID = jQuery(this).attr('data-id');
            let lastTD = jQuery("tr[data-id='" + postID + "'] .last-poznamka");
            let secondLastTD = jQuery("tr[data-id='" + postID + "'] .poznamka");
            
            jQuery.ajax({
                url : '<?php echo admin_url('admin-ajax.php'); ?>',
                type : 'post',
                data : {
                    action : 'send_comment',
                    postID : postID,
                    comment : comment,
                },
                dataType: 'JSON',
                success : function( response ) {
                    commentDOM.val(null);

                    var tmp = $(this).find('div');
                    var string = '';
                    string += '<div class="row comment-div">' + response.result.comment_author + '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' + response.comment_date + '</div>';
                    const originalString = response.result.comment_content;
                    var strippedString = originalString.replace(/(<([^>]+)>)/gi, "");
                    string += '<div class="form-group row comment-div"><textarea class="col-sm-12" oninput="auto_grow(this)" readonly>' + strippedString + '</textarea></div>';
                    $(string).insertAfter("#c_m_comment");

                    lastTD[0].innerHTML = response.result.comment_content;

                    secondLastTD.prepend('<div>' + response.result.comment_author + '</div><div>' + response.comment_date + '</div><div>' + response.result.comment_content + '</div>');
                },
                error: function (error) {
                    console.log(error);
                }
            });
        });
        
        jQuery(document).on('change', '.status-select', function() {
            let mytd = jQuery(this).parent();
            let status = jQuery(this).val();
            let postID = jQuery(this).attr('data-id');

            jQuery.ajax({
                url : '<?php echo admin_url('admin-ajax.php'); ?>',
                type : 'post',
                data : {
                    action : 'send_email',
                    postID : postID,
                    status : status,
                },
                success : function( response ) {
                    window.location.href = '<?php echo admin_url('admin.php?page=customer-management'); ?>';
                },
                error: function (error) {
                    console.log(error);
                }
            });
        });

        jQuery(document).on('change', '.osoba', function() {
            let mytd = jQuery(this).parent();
            let odpovednaOsoba = jQuery(this).val();
            let postID = jQuery(this).attr('data-id');

            jQuery.ajax({
                url : '<?php echo admin_url('admin-ajax.php'); ?>',
                type : 'post',
                data : {
                    action : 'send_osoba',
                    postID : postID,
                    odpovednaOsoba : odpovednaOsoba,
                },
                success : function( response ) {
                    window.location.href = '<?php echo admin_url('admin.php?page=customer-management'); ?>';
                },
                error: function (error) {
                    console.log(error);
                }
            });
        });

        jQuery(document).ready( function () {
            select1ContentArray = <?php echo json_encode($stavTitles); ?>;
            select3ContentArray = <?php echo json_encode($osobaTitles); ?>;

            // get saved datatable length
            var datatable_length = sessionStorage.getItem('datatable_length');
            datatable_length = datatable_length ? parseInt(datatable_length) : 10;

            var myTable = $('#myTable').DataTable( {
				'iDisplayLength': datatable_length,
                'order': [0, 'desc'],
                initComplete: function () {
                    this.api().columns().eq(0).each( function (index) {
                        const columnStatus = this.column(4);
                        const columnOsoba  = this.column(6);
                        const columnKraj   = this.column(9);

                        if (index === 5) {
                            var div1 = $(`<div id='my_filter' style='display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;'></div>`);
                            $('#myTable_wrapper').prepend(div1);

                            // Status filter
                            var label1 = $(`<label style="margin-left: 20px; margin-bottom: 0px;">Stav:</label>`); $('#my_filter').append(label1);
                            var select1 = $(`
                                <select class="form-control" id="mySelect1" style="width: 180px !important; margin-left: 10px;">
                                    <option value="">Please choose</option>
                                </select>
                            `)
                            .on( 'change', function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                sessionStorage.setItem('status_filter',val);
                                columnStatus.search( val ? '^'+val+'$' : '', true, false ).draw();
                            });
                            $('#my_filter').append(select1);
                            for (var i = 0; i < select1ContentArray.length; i++)
                                select1.append( '<option value="' + select1ContentArray[i] + '">' + select1ContentArray[i] + '</option>' );

                            // Odpovědná Osoba filter
                            var label2 = $(`<label style="margin-left: 20px; margin-bottom: 0px;">Osoba:</label>`); $('#my_filter').append(label2);
                            var select3 = $(`
                                <select class="form-control" id="mySelect2" style="width: 180px !important; margin-left: 10px;">
                                    <option value="">Please choose</option>
                                </select>
                            `)
                            .on( 'change', function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                sessionStorage.setItem('osoba_filter',val);
                                columnOsoba.search( val ? '^'+val+'$' : '', true, false ).draw();
                            });
                            $('#my_filter').append(select3);
                            for (var i = 0; i < select3ContentArray.length; i++)
                                select3.append( '<option value="' + select3ContentArray[i] + '">' + select3ContentArray[i] + '</option>' );

                            // Kraj filter
                            var label3 = $(`<label style="margin-left: 20px; margin-bottom: 0px;">Kraj:</label>`); $('#my_filter').append(label3);
                            var select2 = $(`
                                <select class="form-control" id="mySelect3" style="width: 180px !important; margin-left: 10px;">
                                    <option value="">Please choose</option>
                                </select>
                            `)
                            .on( 'change', function () {
                                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                sessionStorage.setItem('kraj_filter',val);
                                columnKraj.search( val ? '^'+val+'$' : '', true, false ).draw();
                            });
                            $('#my_filter').append(select2);
                            var select2ContentArray = ['Hlavní město Praha', 'Jihočeský kraj', 'Jihomoravský kraj', 'Karlovarský kraj', 'Královehradecký kraj', 'Liberecký kraj',
                            'Moravskoslezský kraj', 'Olomoucký kraj', 'Pardubický kraj', 'Plzeňský kraj', 'Středočeský kraj', 'Ústecký kraj', 'Vysočina', 'Zlínský kraj'];
                            for (var i = 0; i < select2ContentArray.length; i++)
                                select2.append( '<option value="' + select2ContentArray[i] + '">' + select2ContentArray[i] + '</option>' );

                            // Datum vytvoření - From filter
                            var label4 = $(`<label style="margin-left: 20px; margin-bottom: 0px;">From:</label>`); $('#my_filter').append(label4);
                            var min = $(`<input type="date" style="width: 170px !important; margin-left: 10px;" id="datepicker_from">`)
                            .on( 'change', function() {
                                sessionStorage.setItem('date_from',$('#datepicker_from').val());
                                myTable.draw();
                            } ) ; $('#my_filter').append(min);

                            // Datum vytvoření - To filter
                            var label5 = $(`<label style="margin-left: 10px; margin-bottom: 0px;">To:</label>`); $('#my_filter').append(label5);
                            var max = $(`<input type="date" style="width: 170px !important; margin-left: 10px;" id="datepicker_to">`)
                            .on( 'change', function () {
                                sessionStorage.setItem('date_to', $('#datepicker_to').val());
                                myTable.draw();
                            } ); $('#my_filter').append(max);
                        }
                    });

                    var statusFilter = sessionStorage.getItem('status_filter');
                    if (statusFilter) {
                        $('#mySelect1').val(statusFilter).change();
                    }
                    var osobaFilter = sessionStorage.getItem('osoba_filter');
                    if (osobaFilter) {
                        $('#mySelect2').val(osobaFilter).change();
                    }
                    var krajFilter = sessionStorage.getItem('kraj_filter');
                    if (krajFilter) {
                        $('#mySelect3').val(krajFilter).change();
                    }

                    // get saved datatable page
                    var datatable_page = sessionStorage.getItem('datatable_page');
                    if (datatable_page) {
                        this.api().page(parseInt(datatable_page)).draw(false);
                    }
                }
            } );

            // Datatable - pagination change event
            $('#myTable').on( 'page.dt', function () {
                var info = myTable.page.info();
                sessionStorage.setItem('datatable_page', info.page);
            } );

            // Datatable - length change event
            $('#myTable').on( 'length.dt', function () {
                var len = myTable.page.len();
                var info = myTable.page.info();
                sessionStorage.setItem('datatable_length', len);
                sessionStorage.setItem('datatable_page', info.page);
            } );

            var dateFrom = sessionStorage.getItem('date_from');
            if (dateFrom) {
                $('#datepicker_from').val(dateFrom).change();
            }
            var dateTo = sessionStorage.getItem('date_to');
            if (dateTo) {
                $('#datepicker_to').val(dateTo).change();
            }
        } );

        jQuery.fn.dataTable.ext.search.push(
            function( settings, data, dataIndex ) {
                var min = new Date($('#datepicker_from').val());
                var max = new Date($('#datepicker_to').val());
                var date = new Date( data[11] );

                min.setHours(0, 0, 0, 0);
                max.setHours(23, 59, 59, 59);
        
                if (
                    ( ($('#datepicker_from').val() == null || $('#datepicker_from').val() == '') && ($('#datepicker_to').val() == null || $('#datepicker_to').val() == '') ) ||
                    ( ($('#datepicker_from').val() == null || $('#datepicker_from').val() == '') && date <= max ) ||
                    ( min <= date && ($('#datepicker_to').val() == null || $('#datepicker_to').val() == '') ) ||
                    ( min <= date && date <= max )
                ) {
                    return true;
                }
                return false;
            }
        );

        var poznamka = document.getElementsByClassName('poznamka');
        for( var i = 0; i < poznamka.length; i++ ) {
            poznamka[i].addEventListener('input', function() {
                let poznamka = this.innerHTML;
                let postID = this.dataset['id'];

                jQuery.ajax({
                    url : '<?php echo admin_url('admin-ajax.php'); ?>',
                    type : 'post',
                    data : {
                        action : 'send_poznamka',
                        postID : postID,
                        poznamka : poznamka,
                    },
                    success : function( response ) {
                        console.log('success');
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            })
        }
        </script>
        <?php
    }

    function formular_poptavky_info_page() {
        require_once plugin_dir_path(__DIR__) . 'zobrazit/formular_poptavky.php';
    }

    function checkWhetherImage($url) {
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        if($ext == 'jpg' || $ext == 'png')
            return 1;
        return 0;
    }

    function obhlidka_info_page() {
        require_once plugin_dir_path(__DIR__) . 'zobrazit/obhlidka.php';
    }
}

add_action( 'ninja_forms_after_submission', 'create_post_from_ninjaform' );
function create_post_from_ninjaform( $form_data ){
    $form_id = $form_data[ 'form_id' ];
    if( $form_id != 2 ) return;
    
    $form_fields = $form_data[ 'fields' ];

    $title = $form_fields[ 9 ][ 'value' ] . ' ' . $form_fields[ 10 ][ 'value' ];
    $name = $form_fields[ 64 ][ 'value' ];
    $email = $form_fields[ 18 ][ 'value' ];
    $telefon = $form_fields[ 11 ][ 'value' ];
    $trvaly_pobyt = $form_fields[ 65 ][ 'value' ];
    $adresa_realizace = $form_fields[ 12 ][ 'value' ];
    $date = date('d.m.Y H:i');

    $post_id = $form_fields[ 62 ][ 'value' ];
    if (empty($post_id)) {
        $new_post = array(
            'post_type'		=> 'zakaznik',
            'post_status'	=> 'publish',
            'post_title'    => $title,
            'post_name'     => $name,
            'meta_input'	=> array(
                'telefon'								=> $telefon,
                'e-mail'								=> $email,
                'trvaly_pobyt'                          => $trvaly_pobyt,
                'adresa_realizace' 						=> $adresa_realizace,
                'termin_realizace' 						=> $date,
                'kontaktni_osoba_jmeno' 				=> '',
                'cislo_na_kontaktni_osobu' 				=> '',
                'cn' 									=> '',
                'zalohova_faktura' 						=> '',
                'faktura' 								=> '',
                'predavaci_protokol' 					=> '',
                'revize_+_uvedeni_do_trvaleho_provozu'	=> '',
                'provozni_manual'						=> '',
                'plna_moc_distribuce'					=> '',
                'plna_moc_na_nzu'						=> '',
                'souhlasne_prohlaseni_vlastniku'		=> '',
                'status'                                => 1,
                'nabidky_pdf_nabidku'                   => '',
                'nabidky_smlouvu'                       => '',
                'nabidky_obhlidkovy_formular'           => '',
                'nabidky_rozpoctovou_tabulku'           => '',
            ),
        );

        $post_id = wp_insert_post($new_post);
    }

    $randomstr = $form_fields[ 61 ][ 'value' ];
    global $wpdb; 
    $postsTable = $wpdb->prefix . 'posts';
    $metaTable = $wpdb->prefix.'postmeta';
    $metas = $wpdb->get_results ( "SELECT post_id FROM $metaTable WHERE `meta_value` = '$randomstr'" );
    $result = get_object_vars($metas[0]);
    $result1 = $result["post_id"];

    $zakaznici = $wpdb->get_results ( "SELECT post_name FROM $postsTable WHERE `ID` = '$post_id'" )[0]->post_name;

    update_post_meta($post_id, 'formular', $result1);

    $to = $email;
    $subject = 'Formulář byl úspěšně odeslán.';

    $body = 'Dobrý den,<br><br>
    děkujeme za Vaší registraci. V návaznosti na Vaší poptávku jsme Vám vytvořili zákaznickou kartu, kterou zobrazíte po kliknutí na odkaz https://geniusfve.cz/zakaznik/' . $zakaznici . ' , kam nahrajeme veškeré potřebné dokumenty. Heslo ke stránce je: Geniusfve<br><br>
    V případě potřeby nás neváhejte kontaktovat.<br>
    Tým Genius FVE.';
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: Genius FVE <info@geniusfve.cz>');
    wp_mail($to, $subject, $body, $headers);
}

add_action('forminator_before_form_render', 'customize_select_customer');
function customize_select_customer($id) {
	if ($id != 881) return;
	$args = array(
		'post_type' => 'zakaznik',
		'post_status' => 'publish',
        'numberposts' => -1,
	);

	$posts = get_posts($args);
	foreach($posts as $post) {
		$post_id = $post->ID;
		$post_title = $post->post_title;
		?>
		<script>
			jQuery(document).ready(function() {
				jQuery('#forminator-form-881__field--select-10').append(jQuery('<option>', {
					value: <?php echo $post_id; ?>,
					text: <?php echo json_encode($post_title); ?>
				}));
			});
		</script>
		<?php
	}
}

add_action('forminator_custom_form_submit_before_set_fields', 'custom_form_submit', 11, 3);
function custom_form_submit($entry, $form_id, $field_data_array) {
    global $wpdb;
    $postTable = $wpdb->prefix . 'posts';
    $customer_name = $field_data_array[1]["value"];
    $customer_name = str_replace(' ', '', $customer_name);
    $customer_id = $wpdb->get_results ( "SELECT ID FROM $postTable WHERE REPLACE(`post_title`, ' ', '') = '$customer_name' AND `post_status` = 'publish'" )[0]->ID;
    $adresa_realizace = $field_data_array[2]["value"];
    $entry_id = $entry->entry_id;

    update_post_meta($customer_id, 'obhlidka', $entry_id);
    update_post_meta($customer_id, 'adresa_realizace', $adresa_realizace);
}